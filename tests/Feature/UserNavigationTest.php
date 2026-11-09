<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_more_route_redirects_to_the_profile_menu(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.more'))
            ->assertRedirect(route('profile.edit'));
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

    public function test_notification_link_opens_the_attention_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.dashboard', ['tab' => 'attention']))
            ->assertOk()
            ->assertSee('tab=attention#attention-heading', false)
            ->assertSee('Perlu perhatian')
            ->assertSee('Tidak ada yang perlu dikejar sekarang.');
    }

    public function test_mobile_navigation_has_five_items_and_maps_every_section(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $routes = [
            [route('user.dashboard'), 'Beranda'],
            [route('user.calendar'), 'Beranda'],
            [route('user.search'), 'Beranda'],
            [route('user.tasks.index'), 'Tugas'],
            [route('user.habits.index'), 'Kebiasaan'],
            [route('user.goals.index'), 'Kebiasaan'],
            [route('user.money.index', ['section' => 'overview']), 'Keuangan'],
            [route('user.money.index', ['section' => 'transactions']), 'Keuangan'],
            [route('user.money.index', ['section' => 'wallets']), 'Keuangan'],
            [route('user.obligations.index'), 'Keuangan'],
            [route('user.loans.index'), 'Keuangan'],
            [route('user.reports.index'), 'Keuangan'],
            [route('profile.edit'), 'Profil'],
            [route('settings.edit'), 'Profil'],
            [route('user.application'), 'Profil'],
        ];

        foreach ($routes as [$url, $active]) {
            $response = $this->get($url)->assertOk();
            $this->assertActiveMobileItem($response->getContent(), $active, $url);
        }
    }

    public function test_streak_is_available_from_the_header_without_adding_a_mobile_navigation_item(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee(route('user.streak'), false)
            ->assertSee('Api aktivitas');

        $this->get(route('user.streak'))
            ->assertOk()
            ->assertSee('Aktivitas 14 hari terakhir')
            ->assertSee(route('user.tasks.index'), false)
            ->assertSee(route('user.habits.index'), false);
    }

    public function test_mapped_submenus_render_and_mark_the_correct_tab(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $routes = [
            [route('user.dashboard'), 'Beranda', 'Hari ini'],
            [route('user.calendar'), 'Beranda', 'Kalender'],
            [route('user.dashboard', ['tab' => 'attention']), 'Beranda', 'Perlu perhatian'],
            [route('user.habits.index'), 'Kebiasaan', 'Kebiasaan'],
            [route('user.goals.index'), 'Kebiasaan', 'Target'],
            [route('user.money.index'), 'Keuangan', 'Ringkasan'],
            [route('user.money.index', ['section' => 'transactions']), 'Keuangan', 'Transaksi'],
            [route('user.money.index', ['section' => 'wallets']), 'Keuangan', 'Dompet'],
            [route('user.obligations.index'), 'Keuangan', 'Tagihan'],
            [route('user.loans.index'), 'Keuangan', 'Utang-piutang'],
            [route('user.reports.index'), 'Keuangan', 'Laporan'],
            [route('profile.edit'), 'Profil', 'Profil'],
            [route('settings.edit'), 'Profil', 'Pengaturan'],
            [route('user.application'), 'Profil', 'Aplikasi'],
        ];

        foreach ($routes as [$url, $group, $label]) {
            $response = $this->get($url)->assertOk()->assertSee('Sub-menu '.$group, false);
            $this->assertSubnavTabActive($response->getContent(), $group, $label, $url);
        }
    }

    public function test_admin_profile_submenu_shows_panel_only_for_admins(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Panel Admin');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Panel Admin')
            ->assertSee(route('admin.dashboard'), false);
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

    private function assertActiveMobileItem(string $html, string $expected, string $url): void
    {
        $document = new \DOMDocument();
        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);
        $xpath = new \DOMXPath($document);
        $items = $xpath->query('//nav[@id="mobileNavigationMenu"]/div/a');

        $this->assertCount(5, iterator_to_array($items), "Expected five mobile menu items for {$url}.");

        $activeItems = $xpath->query('//nav[@id="mobileNavigationMenu"]/div/a[contains(@class, "mobile-nav-link-active")]');
        $this->assertCount(1, iterator_to_array($activeItems), "Expected one active mobile menu item for {$url}.");
        $this->assertSame($expected, trim($activeItems->item(0)->lastElementChild->textContent));
        $this->assertStringNotContainsString('Lainnya', $items->item(0)->parentNode->textContent);
    }

    private function assertSubnavTabActive(string $html, string $group, string $expected, string $url): void
    {
        $document = new \DOMDocument();
        $previousLibxmlSetting = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlSetting);
        $xpath = new \DOMXPath($document);
        $tabs = $xpath->query('//nav[@aria-label="Sub-menu '.$group.'"]//a[@aria-current="page"]');

        $this->assertCount(1, iterator_to_array($tabs), "Expected one active sub-menu tab for {$url}.");
        $this->assertSame($expected, trim($tabs->item(0)->textContent));
    }
}
