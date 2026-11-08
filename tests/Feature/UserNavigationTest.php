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

    public function test_notification_button_opens_an_attention_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('open-modal', false)
            ->assertSee('attention-panel', false)
            ->assertSee('Perlu perhatian')
            ->assertSee('Tidak ada yang perlu dikejar sekarang.');
    }

    public function test_onboarding_shows_three_steps_and_completion_status(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Tambah tugas pertama')
            ->assertSee('Pilih kebiasaan')
            ->assertSee('Catat uang pertama')
            ->assertSee('Belum selesai');
    }

    public function test_onboarding_marks_existing_tasks_as_complete(): void
    {
        $user = User::factory()->create();
        $user->tasks()->create(['title' => 'Tugas pertama']);

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee('Tambah tugas pertama')
            ->assertSee('#icon-selesai', false)
            ->assertSee('Pilih kebiasaan');
    }

    public function test_finance_empty_states_have_clear_create_actions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->get(route('user.money.index', ['section' => 'wallets']))->assertOk();
        $user->moneyCategories()->update(['archived_at' => now()]);

        $this->actingAs($user)
            ->get(route('user.money.index', ['section' => 'wallets']))
            ->assertOk()
            ->assertSee('Belum ada dompet.')
            ->assertSee('Tambah dompet pertama')
            ->assertSee('Belum ada kategori khusus.')
            ->assertSee('Tambah kategori pertama');
    }

    public function test_profile_settings_and_search_pages_use_standard_headers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Sesuaikan aplikasi')
            ->assertSee('Atur tampilan, pengingat');

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Pengaturan akun')
            ->assertSee('Perbarui informasi dan keamanan akun');

        $this->get(route('user.search'))
            ->assertOk()
            ->assertSee('Pencarian pribadi')
            ->assertSee('Temukan tugas, kebiasaan, dan catatan uangmu.');
    }
}
