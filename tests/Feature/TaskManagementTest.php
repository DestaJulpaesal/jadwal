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
