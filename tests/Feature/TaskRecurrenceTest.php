<?php

namespace Tests\Feature;

use App\Models\TaskStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRecurrenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_completion_preserves_local_time_and_duration_and_is_idempotent(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 20:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $task = $user->tasks()->create([
            'title' => 'Olahraga',
            'task_status_id' => null,
            'starts_at' => '2026-11-01 02:00:00',
            'ends_at' => '2026-11-01 03:30:00',
            'recurrence' => 'daily',
            'recurrence_until' => '2026-11-03',
        ]);

        $this->actingAs($user)
            ->from(route('user.tasks.index'))
            ->patch(route('user.tasks.complete', $task))
            ->assertRedirect(route('user.tasks.index'));

        $occurrence = $user->tasks()->where('recurrence_parent_id', $task->id)->firstOrFail();
        $this->assertSame('2026-11-03', $occurrence->occurrence_date->toDateString());
        $this->assertSame('2026-11-03 02:00:00', $occurrence->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-03 03:30:00', $occurrence->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('daily', $occurrence->recurrence);

        $this->patch(route('user.tasks.complete', $task))
            ->assertSessionHas('feedback.message', 'Tugas ini sudah ditandai selesai.');
        $this->assertSame(1, $user->tasks()->where('recurrence_parent_id', $task->id)->count());

        $this->patch(route('user.tasks.complete', $occurrence));
        $this->assertSame(1, $user->tasks()->where('recurrence_parent_id', $task->id)->count());
    }

    public function test_weekly_multi_day_recurrence_uses_iso_weekdays_when_update_marks_task_complete(): void
    {
        $this->travelTo(Carbon::parse('2026-11-02 00:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jayapura']);
        $completed = TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $task = $user->tasks()->create([
            'title' => 'Kelas',
            'starts_at' => '2026-11-01 23:00:00',
            'recurrence' => 'weekly',
            'recurrence_days' => [1, 3, 5],
        ]);

        $this->actingAs($user)
            ->put(route('user.tasks.update', $task), [
                'title' => $task->title,
                'task_status_id' => $completed->id,
                'recurrence' => 'weekly',
                'recurrence_days' => ['1', '3', '5'],
            ])
            ->assertRedirect(route('user.tasks.index'));

        $occurrence = $user->tasks()->where('recurrence_parent_id', $task->id)->firstOrFail();
        $this->assertSame('2026-11-04', $occurrence->occurrence_date->toDateString());

        $this->put(route('user.tasks.update', $task), [
            'title' => $task->title,
            'task_status_id' => $completed->id,
            'recurrence' => 'weekly',
            'recurrence_days' => ['1', '3', '5'],
        ]);
        $this->assertSame(1, $user->tasks()->where('recurrence_parent_id', $task->id)->count());
    }

    public function test_monthly_recurrence_keeps_the_original_day_after_a_short_month(): void
    {
        $this->travelTo(Carbon::parse('2026-01-31 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $task = $user->tasks()->create([
            'title' => 'Tinjau anggaran',
            'starts_at' => '2026-01-31 09:00:00',
            'recurrence' => 'monthly',
        ]);

        $this->actingAs($user)->patch(route('user.tasks.complete', $task));
        $february = $user->tasks()->where('recurrence_parent_id', $task->id)->firstOrFail();
        $this->assertSame('2026-02-28', $february->occurrence_date->toDateString());

        $this->travelTo(Carbon::parse('2026-02-28 12:00:00', 'UTC'));
        $this->patch(route('user.tasks.complete', $february));
        $march = $user->tasks()
            ->where('recurrence_parent_id', $task->id)
            ->whereDate('occurrence_date', '2026-03-31')
            ->firstOrFail();
        $this->assertSame('2026-03-31', $march->occurrence_date->toDateString());
    }

    public function test_weekly_recurrence_requires_at_least_one_valid_day(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('user.tasks.create'))
            ->post(route('user.tasks.store'), [
                'title' => 'Kegiatan mingguan',
                'recurrence' => 'weekly',
                'recurrence_days' => [],
            ])
            ->assertRedirect(route('user.tasks.create'))
            ->assertSessionHasErrors('recurrence_days');

        $this->from(route('user.tasks.create'))
            ->post(route('user.tasks.store'), [
                'title' => 'Kegiatan mingguan',
                'recurrence' => 'weekly',
                'recurrence_days' => ['8'],
            ])
            ->assertRedirect(route('user.tasks.create'))
            ->assertSessionHasErrors('recurrence_days.0');
    }

    public function test_stopping_a_series_deletes_only_the_selected_task_and_disables_remaining_occurrences(): void
    {
        $user = User::factory()->create();
        $root = $user->tasks()->create([
            'title' => 'Rutinitas',
            'recurrence' => 'daily',
        ]);
        $occurrence = $user->tasks()->create([
            'title' => 'Rutinitas',
            'recurrence' => 'daily',
            'recurrence_parent_id' => $root->id,
            'occurrence_date' => '2026-11-17',
        ]);

        $this->actingAs($user)
            ->delete(route('user.tasks.destroy-series', $root))
            ->assertRedirect(route('user.tasks.index'))
            ->assertSessionHas('feedback.message', 'Pengulangan dihentikan dan tugas ini dihapus.');

        $this->assertDatabaseMissing('tasks', ['id' => $root->id]);
        $this->assertDatabaseHas('tasks', ['id' => $occurrence->id, 'recurrence' => 'none']);
    }

    public function test_deleting_only_the_series_root_keeps_remaining_occurrences_linked_and_idempotent(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $root = $user->tasks()->create([
            'title' => 'Laporan bulanan',
            'starts_at' => '2026-01-31 09:00:00',
            'recurrence' => 'monthly',
            'recurrence_anchor_day' => 31,
        ]);
        $first = $user->tasks()->create([
            'title' => 'Laporan bulanan',
            'starts_at' => '2026-02-28 09:00:00',
            'recurrence' => 'monthly',
            'recurrence_anchor_day' => 31,
            'recurrence_parent_id' => $root->id,
            'occurrence_date' => '2026-02-28',
        ]);
        $second = $user->tasks()->create([
            'title' => 'Laporan bulanan',
            'starts_at' => '2026-03-31 09:00:00',
            'recurrence' => 'monthly',
            'recurrence_anchor_day' => 31,
            'recurrence_parent_id' => $root->id,
            'occurrence_date' => '2026-03-31',
        ]);

        $this->actingAs($user)
            ->delete(route('user.tasks.destroy', $root))
            ->assertRedirect(route('user.tasks.index'));

        $this->assertNull($first->fresh()->recurrence_parent_id);
        $this->assertSame($first->id, $second->fresh()->recurrence_parent_id);

        TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $this->travelTo(Carbon::parse('2026-02-28 12:00:00', 'UTC'));
        $this->patch(route('user.tasks.complete', $first));

        $this->assertSame(1, $user->tasks()->where('recurrence_parent_id', $first->id)->count());
    }
}
