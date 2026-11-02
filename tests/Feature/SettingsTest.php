<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_is_displayed_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('data-theme="light"', false)
            ->assertSee('Preferensi aplikasi')
            ->assertSee('Pengingat & email')
            ->assertSee('Informasi di dashboard');
    }

    public function test_user_can_update_their_application_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('settings.update'), [
            'timezone' => 'Asia/Makassar',
            'week_starts_on' => 'sunday',
            'theme' => 'dark',
            'digest_enabled' => '1',
            'digest_time' => '08:30',
            'digest_days' => ['1', '3', '5'],
            'hide_financial_amounts' => '1',
            'dashboard_sections' => [
                'attention' => '0',
                'habits' => '1',
                'today_tasks' => '0',
                'upcoming' => '1',
                'finance' => '0',
            ],
        ])->assertRedirect(route('settings.edit'))
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('Asia/Makassar', $user->timezone);
        $this->assertSame('sunday', $user->week_starts_on);
        $this->assertSame('dark', $user->theme);
        $this->assertTrue($user->digest_enabled);
        $this->assertSame('08:30', $user->digest_time);
        $this->assertSame([1, 3, 5], $user->digest_days);
        $this->assertTrue($user->hide_financial_amounts);
        $this->assertSame(['habits', 'upcoming'], $user->dashboard_sections);
    }

    public function test_settings_reject_invalid_values_and_require_a_digest_day(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('settings.edit'))->patch(route('settings.update'), [
            'timezone' => 'Not/A_Timezone',
            'week_starts_on' => 'friday',
            'theme' => 'neon',
            'digest_enabled' => '0',
            'digest_time' => '25:90',
            'digest_days' => [],
            'hide_financial_amounts' => '0',
            'dashboard_sections' => [],
        ])->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors([
                'timezone',
                'week_starts_on',
                'theme',
                'digest_time',
                'digest_days',
            ]);

        $this->assertSame('Asia/Jakarta', $user->fresh()->timezone);
        $this->assertSame('monday', $user->fresh()->week_starts_on);
    }

    public function test_dashboard_preferences_control_visible_sections_and_persist_privacy_default(): void
    {
        $user = User::factory()->create([
            'hide_financial_amounts' => true,
            'dashboard_sections' => ['habits'],
        ]);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('data-private-default="true"', false)
            ->assertSee('Kebiasaan hari ini')
            ->assertDontSee('Jadwal hari ini')
            ->assertDontSee('Ringkasan uang bulan ini');
    }
}
