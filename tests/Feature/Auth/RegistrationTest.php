<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('Hari baru,')
            ->assertSee('Buat akun')
            ->assertSee('register-form')
            ->assertSee('Hari lebih tertata')
            ->assertSee('Kembali ke beranda')
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee(route('login'));
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertNothingSent();
    }

    public function test_registered_users_can_log_out_and_log_back_in(): void
    {
        $credentials = [
            'name' => 'Persistent User',
            'email' => 'persistent@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->post('/register', $credentials)->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/login', [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticated();
    }

    public function test_registration_is_rate_limited_by_ip(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/register', []);
        }

        $response = $this->post('/register', []);

        $response->assertStatus(429);
    }
}
