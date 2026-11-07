<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
            ->assertSee('Manajemen Akun')
            ->assertSee('Ringkasan')
            ->assertSee('Manajemen')
            ->assertSee($admin->name)
            ->assertSee($admin->email)
            ->assertSee('Buka menu akun')
            ->assertSee(route('settings.edit'))
            ->assertSee(route('profile.edit'));
    }

    public function test_admin_category_editor_shows_named_colors_and_contrast_preview(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::query()->create(['name' => 'Kesehatan', 'color' => '#2563EB']);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('warna bernama')
            ->assertSee('Biru')
            ->assertSee('Contoh teks kategori')
            ->assertSee('kontras');
    }

    public function test_admin_can_search_and_filter_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Primary Admin']);
        $matchingUser = User::factory()->create(['name' => 'Dina Rahma', 'email' => 'dina@example.test']);
        User::factory()->create(['name' => 'Inactive Person', 'is_active' => false]);
        User::factory()->create(['name' => 'Other Admin', 'role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'dina', 'status' => 'active', 'role' => 'user']))
            ->assertOk()
            ->assertSee($matchingUser->name)
            ->assertDontSee('Inactive Person')
            ->assertDontSee('Other Admin');
    }

    public function test_admin_can_view_account_details_without_schedule_contents(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'Schedule Owner']);
        $user->tasks()->create(['title' => 'Private task details']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Schedule Owner')
            ->assertSee('Ringkasan penggunaan')
            ->assertSee('Manajemen Akun')
            ->assertSee('aria-current="page"', false)
            ->assertDontSee('Private task details');
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
        $category = $user->categories()->create([
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

    public function test_admin_can_change_another_accounts_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $user))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);

        $this->patch(route('admin.users.role', $user))
            ->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'user']);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.users.role', $admin))
            ->assertSessionHasErrors('account');

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_can_send_a_password_reset_link_to_a_user(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.password-reset', $user))
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class);
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
