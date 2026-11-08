<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertSee(route('settings.edit'))
            ->assertSee(route('profile.edit'))
            ->assertSee('Buka pengaturan')
            ->assertSee('Buka profil')
            ->assertSee('Sesi akun')
            ->assertSee('Foto profil');
    }

    public function test_user_can_upload_and_replace_their_profile_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $firstPath = $user->fresh()->profile_photo_path;
        $this->assertNotNull($firstPath);
        Storage::disk('local')->assertExists($firstPath);
        $this->get(route('profile.photo'))->assertOk();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
        ])->assertSessionHasNoErrors();

        $secondPath = $user->fresh()->profile_photo_path;
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('avatar.png')->store('profile-photos');
        $user->profile_photo_path = $path;
        $user->save();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'remove_avatar' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_profile_photo_must_be_an_image_with_a_supported_format(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_profile_photo_is_private_and_removed_when_the_account_is_deleted(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $path = UploadedFile::fake()->image('avatar.png')->store('profile-photos');
        $owner->profile_photo_path = $path;
        $owner->save();

        $this->actingAs($owner)->get(route('profile.photo'))->assertOk();
        $this->actingAs($otherUser)->get(route('profile.photo'))->assertNotFound();

        $owner->delete();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_profile_information_can_be_updated(): void
    {
        config(['auth.email_verification' => true]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'timezone' => 'Asia/Jakarta',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('Asia/Jakarta', $user->timezone);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
                'timezone' => 'America/New_York',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
        $this->assertSame('America/New_York', $user->timezone);
    }

    public function test_profile_rejects_an_unknown_timezone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'timezone' => 'Not/A_Timezone',
            ])
            ->assertSessionHasErrors('timezone');

        $this->assertSame('Asia/Jakarta', $user->refresh()->timezone);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'confirmation' => 'HAPUS',
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'confirmation' => 'HAPUS',
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_account_deletion_requires_typed_confirmation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'confirmation' => 'HAPU',
                'password' => 'password',
            ]);

        $response
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('userDeletion', 'confirmation');
        $messages = __('validation.catalog.profile_deletion');
        $this->assertSame(
            $messages['confirmation.in'],
            $response->getSession()->get('errors')->getBag('userDeletion')->first('confirmation')
        );

        $this->assertNotNull($user->fresh());
    }

    public function test_admin_cannot_delete_the_last_active_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/profile')
            ->delete('/profile', ['confirmation' => 'HAPUS', 'password' => 'password'])
            ->assertRedirect('/profile')
            ->assertSessionHasErrorsIn('userDeletion', 'account');

        $this->assertNotNull($admin->fresh());
    }

    public function test_profile_can_enable_the_optional_daily_digest(): void
    {
        $user = User::factory()->create(['digest_enabled' => false]);

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'timezone' => 'Asia/Jakarta',
            'digest_enabled' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->digest_enabled);
    }
}
