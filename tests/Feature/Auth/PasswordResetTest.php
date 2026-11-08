<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('feedback.message', __('ui.password_reset.sent'));

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_response_is_identical_for_registered_and_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $registered = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
        $registeredMessage = $registered->getSession()->get('feedback.message');
        $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'unknown@example.test']);
        $unknownMessage = $unknown->getSession()->get('feedback.message');

        $registered->assertRedirect('/forgot-password');
        $unknown->assertRedirect('/forgot-password');
        $this->assertSame(__('ui.password_reset.sent'), $registeredMessage);
        $this->assertSame($registeredMessage, $unknownMessage);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }
}
