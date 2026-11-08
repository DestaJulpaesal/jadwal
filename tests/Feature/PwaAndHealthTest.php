<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Tests\TestCase;

class PwaAndHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_page_and_pwa_metadata_are_available(): void
    {
        $this->get('/offline')
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8');

        $offlinePage = file_get_contents(public_path('offline.html'));
        $this->assertIsString($offlinePage);
        $this->assertStringContainsString('Kamu sedang offline', $offlinePage);
        $this->assertStringContainsString('Data tersimpan di server aman.', $offlinePage);

        $this->get('/')
            ->assertOk()
            ->assertSee('manifest.webmanifest')
            ->assertSee('apple-mobile-web-app-capable');

        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertIsArray($manifest);
        $this->assertSame('/dashboard?source=pwa', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);

        $this->actingAs(User::factory()->create())
            ->get(route('user.more'))
            ->assertOk()
            ->assertSee('data-pwa-install', false)
            ->assertSee('Pasang aplikasi');
    }

    public function test_health_check_reports_database_availability_without_details(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertExactJson(['status' => 'ok'])
            ->assertHeader('X-Request-Id');
    }
}
