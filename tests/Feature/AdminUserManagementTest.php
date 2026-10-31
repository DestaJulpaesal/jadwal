<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_accounts_without_viewing_their_schedule_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Private Schedule Owner']);
        $user->tasks()->create(['title' => 'Confidential appointment']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Private Schedule Owner')
            ->assertDontSee('Confidential appointment')
            ->assertSee('Manajemen Akun');
    }

    public function test_regular_user_cannot_access_admin_account_management(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_deactivate_and_reactivate_an_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $user))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);

        $this->patch(route('admin.users.status', $user))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    public function test_deactivated_users_cannot_log_in_or_continue_using_an_existing_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($admin)->patch(route('admin.users.status', $user));
        $this->post(route('logout'));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->actingAs($user->fresh())
            ->get(route('user.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_delete_an_account_and_its_personal_schedule_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'Pribadi',
        ]);
        $task = $user->tasks()->create([
            'category_id' => $category->id,
            'title' => 'Task to delete with owner',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_admin_cannot_deactivate_or_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.users.status', $admin))
            ->assertSessionHasErrors('account');

        $this->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('account');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_admin_can_only_be_created_through_the_artisan_command(): void
    {
        $this->artisan('admin:create', [
            'email' => 'first-admin@example.test',
            '--name' => 'First Admin',
        ])
            ->expectsQuestion('Password', 'SecureAdminPass1!')
            ->expectsQuestion('Confirm password', 'SecureAdminPass1!')
            ->assertExitCode(0);

        $admin = User::where('email', 'first-admin@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue(Hash::check('SecureAdminPass1!', $admin->password));
    }
}
