<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_added_to_responses(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }

    public function test_csp_report_mode_uses_a_request_nonce_on_policy_and_inline_script(): void
    {
        config(['security.csp_mode' => 'report']);

        $response = $this->get('/')->assertOk();
        $policy = $response->headers->get('Content-Security-Policy-Report-Only');

        $this->assertNotNull($policy);
        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertMatchesRegularExpression('/nonce-([A-Za-z0-9+\\/_=-]+)/', $policy);
        preg_match('/nonce-([A-Za-z0-9+\\/_=-]+)/', $policy, $matches);
        $this->assertStringContainsString('nonce="'.$matches[1].'"', $response->getContent());
        $this->assertStringContainsString("style-src-attr 'unsafe-inline'", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
    }

    public function test_environment_example_starts_csp_in_report_mode(): void
    {
        $environmentExample = (string) file_get_contents(base_path('.env.example'));

        $this->assertStringContainsString('CSP_MODE=report', $environmentExample);
        $this->assertStringContainsString('CSP_REPORT_URI=/csp-report', $environmentExample);
    }

    public function test_csp_enforce_mode_sets_enforcing_header_and_off_mode_omits_it(): void
    {
        config(['security.csp_mode' => 'enforce']);
        $this->get('/')
            ->assertOk()
            ->assertHeader('Content-Security-Policy');

        config(['security.csp_mode' => 'off']);
        $this->get('/')
            ->assertOk()
            ->assertHeaderMissing('Content-Security-Policy')
            ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    }

    public function test_error_page_renders_when_security_middleware_fails_before_sharing_nonce(): void
    {
        config(['security.csp_mode' => 'invalid']);

        $this->assertArrayNotHasKey('cspNonce', view()->getShared());

        $response = $this->get('/')
            ->assertInternalServerError()
            ->assertSee('Ada kendala saat memuat halaman')
            ->assertSee('Kembali ke beranda');

        $this->assertStringContainsString('<script>', $response->getContent());
        $this->assertStringNotContainsString('<script nonce=', $response->getContent());
    }

    public function test_csp_local_policy_allows_only_the_known_vite_development_origins(): void
    {
        $this->app->detectEnvironment(fn () => 'local');
        config(['security.csp_mode' => 'report']);

        $policy = $this->get('/')
            ->assertOk()
            ->headers->get('Content-Security-Policy-Report-Only');

        $this->assertStringContainsString('http://127.0.0.1:5173', $policy);
        $this->assertStringContainsString('ws://127.0.0.1:5173', $policy);
        $this->assertStringContainsString('http://localhost:5173', $policy);
        $this->assertStringContainsString('ws://localhost:5173', $policy);
    }

    public function test_authenticated_pages_are_not_cached_by_shared_caches(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('user.dashboard'))
            ->assertOk();

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_hsts_is_only_added_for_production_https_requests(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $middleware = app(\App\Http\Middleware\SecurityHeaders::class);
        $secureResponse = $middleware->handle(
            Request::create('https://localhost/'),
            fn () => response('OK')
        );
        $this->assertSame(
            'max-age=31536000; includeSubDomains',
            $secureResponse->headers->get('Strict-Transport-Security')
        );

        $insecureResponse = $middleware->handle(
            Request::create('http://localhost/'),
            fn () => response('OK')
        );
        $this->assertNull($insecureResponse->headers->get('Strict-Transport-Security'));
    }

    public function test_production_boot_warns_when_app_url_is_a_loopback_host(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.debug' => false, 'app.url' => 'http://localhost']);
        Log::spy();

        (new AppServiceProvider($this->app))->boot();

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('APP_URL points to a loopback host in production.');
    }
}
