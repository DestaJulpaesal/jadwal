<?php

namespace Tests\Feature;

use App\Enums\HabitFrequency;
use App\Enums\HabitLogStatus;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserStreakTest extends TestCase
{
    use RefreshDatabase;

    public function test_streak_combines_completed_habits_and_tasks_by_the_users_local_date(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 16:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $habit = $user->habits()->create([
            'title' => 'Baca buku',
            'frequency' => HabitFrequency::Daily,
            'starts_on' => '2026-11-01',
        ]);

        foreach (['2026-11-07', '2026-11-08'] as $date) {
            $user->habitLogs()->create([
                'habit_id' => $habit->id,
                'log_date' => $date,
                'status' => HabitLogStatus::Done,
            ]);
        }

        $completed = TaskStatus::create(['name' => 'Selesai']);
        $user->tasks()->create([
            'title' => 'Rapikan meja',
            'task_status_id' => $completed->id,
            'completed_at' => '2026-11-09 15:55:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.streak'))
            ->assertOk()
            ->assertViewHas('currentStreak', 3)
            ->assertViewHas('bestStreak', 3)
            ->assertViewHas('activeToday', true)
            ->assertViewHas('streakHistory', fn ($history) => count($history) === 1 && $history[0]['days'] === 3)
            ->assertSee('3 hari berturut-turut')
            ->assertSee('Riwayat api terpanjang')
            ->assertSee('Aktivitas hari ini sudah tercatat.');
    }

    public function test_streak_resets_after_a_missed_local_calendar_day(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 16:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $completed = TaskStatus::create(['name' => 'Selesai']);

        $user->tasks()->create([
            'title' => 'Tugas lama',
            'task_status_id' => $completed->id,
            'completed_at' => '2026-11-07 15:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.streak'))
            ->assertOk()
            ->assertViewHas('currentStreak', 0)
            ->assertViewHas('bestStreak', 1)
            ->assertViewHas('activeToday', false)
            ->assertSee('Api akan menyala lagi');
    }

    public function test_streak_history_keeps_the_highest_record_at_the_top_after_a_longer_streak_has_ended(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 16:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $completed = TaskStatus::create(['name' => 'Selesai']);

        foreach (['01', '02', '03', '06', '07'] as $day) {
            $user->tasks()->create([
                'title' => 'Tugas tanggal '.$day,
                'task_status_id' => $completed->id,
                'completed_at' => '2026-11-'.$day.' 10:00:00',
            ]);
        }

        $this->actingAs($user)
            ->get(route('user.streak'))
            ->assertOk()
            ->assertViewHas('bestStreak', 3)
            ->assertViewHas('streakHistory', fn ($history) => count($history) === 2
                && $history[0]['days'] === 3
                && $history[0]['start']->toDateString() === '2026-11-01'
                && $history[1]['days'] === 2)
            ->assertSee('Api tertinggi sepanjang waktu');
    }

    public function test_completing_a_task_records_its_completion_date_for_the_streak(): void
    {
        $this->travelTo(Carbon::parse('2026-11-09 16:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $task = $user->tasks()->create(['title' => 'Selesaikan laporan']);
        TaskStatus::create(['name' => 'Selesai']);

        $this->actingAs($user)
            ->patch(route('user.tasks.complete', $task))
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed_at' => '2026-11-09 16:00:00',
        ]);
    }
}
