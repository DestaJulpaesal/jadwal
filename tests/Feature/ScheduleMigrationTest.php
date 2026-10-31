<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_jakarta_deadlines_are_preserved_as_utc_times(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create(['title' => 'Legacy deadline']);
        $migration = require database_path('migrations/2026_10_29_130300_add_schedule_times_and_user_timezones.php');

        $migration->down();
        $this->assertSame(1, DB::table('tasks')->count());
        DB::table('tasks')->where('id', $task->id)->update([
            'due_date' => '2026-10-15 09:00:00',
        ]);
        $this->assertSame(
            '2026-10-15 09:00:00',
            DB::table('tasks')->where('id', $task->id)->value('due_date')
        );
        $migration->up();

        $this->assertSame(
            '2026-10-15 02:00:00',
            DB::table('tasks')->where('id', $task->id)->value('due_date')
        );
        $this->assertSame(
            'Asia/Jakarta',
            DB::table('users')->where('id', $user->id)->value('timezone')
        );
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('tasks', 'starts_at'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('tasks', 'ends_at'));
    }
}
