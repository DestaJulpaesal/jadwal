<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_more_page_exposes_all_secondary_destinations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.more'))
            ->assertOk()
            ->assertSee('Kalender')
            ->assertSee('Target')
            ->assertSee('Tagihan & cicilan', false)
            ->assertSee('Utang-piutang')
            ->assertSee('Laporan')
            ->assertSee('Pencarian')
            ->assertSee('Pengaturan')
            ->assertSee('Profil')
            ->assertSee('Bantuan singkat')
            ->assertSee(route('logout'), false);
    }

    public function test_reports_route_renders_the_dedicated_report_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.reports.index'))
            ->assertOk()
            ->assertSee('Laporan bulanan')
            ->assertSee('Bulan laporan');
    }
}
