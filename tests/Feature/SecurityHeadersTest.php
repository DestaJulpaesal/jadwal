<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_added_to_responses(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
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
}
