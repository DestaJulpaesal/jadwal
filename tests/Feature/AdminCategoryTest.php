<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_global_category_preserves_tasks_owned_by_different_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::query()->create(['name' => 'Rutin', 'color' => '#2563EB']);
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();

        $firstTask = $firstUser->tasks()->create([
            'category_id' => $category->id,
            'title' => 'Tugas pengguna pertama',
        ]);
        $secondTask = $secondUser->tasks()->create([
            'category_id' => $category->id,
            'title' => 'Tugas pengguna kedua',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee(__('ui.category.usage_count', ['count' => 2]))
            ->assertSee(__('ui.confirmations.category_delete_message', ['name' => 'Rutin', 'count' => 2]));

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect()
            ->assertSessionHas('feedback.message', __('ui.feedback.category_deleted_with_items', ['count' => 2]));

        $this->assertDatabaseHas('tasks', [
            'id' => $firstTask->id,
            'user_id' => $firstUser->id,
            'category_id' => null,
        ]);
        $this->assertDatabaseHas('tasks', [
            'id' => $secondTask->id,
            'user_id' => $secondUser->id,
            'category_id' => null,
        ]);
    }
}
