<?php

namespace Tests\Feature;

use App\Models\TaskStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_task_order_prioritizes_incomplete_near_deadlines_and_supports_sorting(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 12:00:00', 'UTC'));
        $user = User::factory()->create();
        $completed = TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $user->tasks()->create(['title' => 'Sorting no due']);
        $user->tasks()->create(['title' => 'Sorting far due', 'due_date' => '2026-11-20 09:00:00']);
        $user->tasks()->create(['title' => 'Sorting near due', 'due_date' => '2026-11-18 09:00:00']);
        $user->tasks()->create([
            'title' => 'Sorting completed',
            'task_status_id' => $completed->id,
            'due_date' => '2026-11-17 09:00:00',
        ]);

        $default = $this->actingAs($user)->get(route('user.tasks.index'))->assertOk()->getContent();
        $this->assertLessThan(strpos($default, 'Sorting far due'), strpos($default, 'Sorting near due'));
        $this->assertLessThan(strpos($default, 'Sorting no due'), strpos($default, 'Sorting near due'));
        $this->assertLessThan(strpos($default, 'Sorting completed'), strpos($default, 'Sorting no due'));

        $byTitle = $this->get(route('user.tasks.index', ['sort' => 'title']))->assertOk()->getContent();
        $this->assertLessThan(strpos($byTitle, 'Sorting no due'), strpos($byTitle, 'Sorting far due'));
        $this->assertLessThan(strpos($byTitle, 'Sorting no due'), strpos($byTitle, 'Sorting near due'));
        $this->assertLessThan(strpos($byTitle, 'Sorting completed'), strpos($byTitle, 'Sorting near due'));
    }

    public function test_filter_counts_match_local_day_and_overdue_boundaries(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'Asia/Jakarta']);
        $completed = TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $user->tasks()->create(['title' => 'Due before today', 'due_date' => '2026-11-19 16:59:00']);
        $user->tasks()->create(['title' => 'Due earlier today', 'due_date' => '2026-11-20 11:00:00']);
        $user->tasks()->create(['title' => 'Due late today', 'due_date' => '2026-11-20 16:59:00']);
        $user->tasks()->create(['title' => 'Due tomorrow boundary', 'due_date' => '2026-11-20 17:00:00']);
        $user->tasks()->create([
            'title' => 'Completed today',
            'task_status_id' => $completed->id,
            'due_date' => '2026-11-20 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('user.tasks.index'))
            ->assertOk()
            ->assertViewHas('taskCounts', fn (array $counts) => $counts === [
                'all' => 5,
                'today' => 3,
                'upcoming' => 1,
                'overdue' => 2,
                'completed' => 1,
            ]);

        $this->get(route('user.tasks.index', ['due' => 'today']))
            ->assertOk()
            ->assertSee('Due earlier today')
            ->assertSee('Due late today')
            ->assertSee('Completed today')
            ->assertDontSee('Due before today')
            ->assertDontSee('Due tomorrow boundary');

        $this->get(route('user.tasks.index', ['due' => 'overdue']))
            ->assertOk()
            ->assertSee('Due before today')
            ->assertSee('Due earlier today')
            ->assertDontSee('Due late today')
            ->assertDontSee('Completed today');
    }

    public function test_category_filter_is_owner_scoped_and_excludes_other_users_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = $user->categories()->create(['name' => 'Pekerjaan', 'color' => '#2255AA']);
        $foreignCategory = $otherUser->categories()->create(['name' => 'Kategori pribadi', 'color' => '#AA5522']);
        $user->tasks()->create(['title' => 'Tugas kerja', 'category_id' => $category->id]);
        $otherUser->tasks()->create(['title' => 'Tugas orang lain', 'category_id' => $foreignCategory->id]);

        $this->actingAs($user)
            ->get(route('user.tasks.index', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee('Tugas kerja')
            ->assertDontSee('Tugas orang lain')
            ->assertDontSee('Kategori pribadi');

        $this->get(route('user.tasks.index', ['category_id' => $foreignCategory->id]))
            ->assertSessionHasErrors('category_id');
    }

    public function test_bulk_completion_and_status_changes_apply_atomically_to_owned_tasks(): void
    {
        $user = User::factory()->create();
        $completed = TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $inProgress = TaskStatus::create(['name' => 'Sedang dikerjakan']);
        $first = $user->tasks()->create(['title' => 'Tugas satu']);
        $second = $user->tasks()->create(['title' => 'Tugas dua']);

        $this->actingAs($user)
            ->patch(route('user.tasks.bulk'), [
                'action' => 'complete',
                'task_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('user.tasks.index'))
            ->assertSessionHas('feedback.message', 'Tugas terpilih berhasil diperbarui.');

        $this->assertDatabaseHas('tasks', ['id' => $first->id, 'task_status_id' => $completed->id]);
        $this->assertNotNull($first->fresh()->completed_at);
        $this->assertNotNull($second->fresh()->completed_at);

        $this->patch(route('user.tasks.bulk'), [
            'action' => 'status',
            'task_status_id' => $inProgress->id,
            'task_ids' => [$first->id, $second->id],
        ])->assertRedirect(route('user.tasks.index'));

        $this->assertDatabaseHas('tasks', ['id' => $first->id, 'task_status_id' => $inProgress->id]);
        $this->assertNull($first->fresh()->completed_at);
    }

    public function test_repeated_bulk_completion_does_not_create_duplicate_recurrences(): void
    {
        $this->travelTo(Carbon::parse('2026-11-17 12:00:00', 'UTC'));
        $user = User::factory()->create(['email_verified_at' => now()]);
        TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $task = $user->tasks()->create([
            'title' => 'Rutinitas harian',
            'recurrence' => 'daily',
        ]);
        $payload = ['action' => 'complete', 'task_ids' => [$task->id]];

        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($user)
            ->from(route('user.tasks.index'))
            ->patch(route('user.tasks.bulk'), $payload)
            ->assertRedirect(route('user.tasks.index'));

        $this->patch(route('user.tasks.bulk'), $payload)
            ->assertRedirect(route('user.tasks.index'));

        $this->assertDatabaseCount('tasks', 2);
        $this->assertSame(1, $user->tasks()->where('recurrence_parent_id', $task->id)->count());
    }

    public function test_bulk_completion_reports_when_a_recurrence_stops(): void
    {
        $user = User::factory()->create();
        TaskStatus::create(['name' => 'Selesai', 'is_completed' => true]);
        $task = $user->tasks()->create([
            'title' => 'Pengingat mingguan',
            'recurrence' => 'weekly',
            'recurrence_days' => null,
        ]);

        $this->actingAs($user)
            ->from(route('user.tasks.index'))
            ->patch(route('user.tasks.bulk'), [
                'action' => 'complete',
                'task_ids' => [$task->id],
            ])
            ->assertRedirect(route('user.tasks.index'))
            ->assertSessionHas(
                'feedback.message',
                __('ui.feedback.task_bulk_updated').' '.__('ui.feedback.task_bulk_recurrence_stopped', ['count' => 1])
            );

        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_bulk_actions_reject_another_users_task_without_mutating_any_selection(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = $user->tasks()->create(['title' => 'Milik saya']);
        $foreignTask = $otherUser->tasks()->create(['title' => 'Milik orang lain']);

        $this->actingAs($user)
            ->patch(route('user.tasks.bulk'), [
                'action' => 'delete',
                'task_ids' => [$task->id, $foreignTask->id],
            ])
            ->assertNotFound();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
        $this->assertDatabaseHas('tasks', ['id' => $foreignTask->id]);
    }

    public function test_bulk_delete_removes_selected_owned_tasks(): void
    {
        $user = User::factory()->create();
        $task = $user->tasks()->create(['title' => 'Hapus pilihan']);

        $this->actingAs($user)
            ->patch(route('user.tasks.bulk'), [
                'action' => 'delete',
                'task_ids' => [$task->id],
            ])
            ->assertRedirect(route('user.tasks.index'))
            ->assertSessionHas('feedback.message', 'Tugas terpilih berhasil dihapus.');

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }
}
