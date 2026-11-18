<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_amounts_are_hidden_before_frontend_initializes(): void
    {
        $this->actingAs(User::factory()->create(['hide_financial_amounts' => true]))
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('data-private-default="true"', false)
            ->assertSee('data-privacy-hidden="true"', false);

        $this->assertStringContainsString(
            'html[data-privacy-hidden="true"]:not([data-privacy-ready="true"]) [data-private]',
            (string) file_get_contents(resource_path('css/base.css'))
        );
    }

    public function test_dashboard_renders_localized_optimistic_feedback_and_live_habit_progress(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('data-session-expired-message="Sesi kamu sudah berakhir. Masuk lagi untuk melanjutkan."', false)
            ->assertSee('data-login-again-label="Masuk lagi"', false)
            ->assertSee('data-login-url="', false)
            ->assertSee('data-habit-completion-progress', false)
            ->assertSee('data-habit-completion-count', false)
            ->assertSee('aria-live="polite"', false);
    }
}
