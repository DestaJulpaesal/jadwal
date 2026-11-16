<?php

namespace Tests\Feature;

use App\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TrustProxiesTest extends TestCase
{
    public function test_configured_proxy_can_forward_the_client_ip(): void
    {
        config(['trustedproxy.proxies' => ['10.0.0.1']]);
        $request = Request::create('https://app.test/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.10',
        ]);

        (new TrustProxies)->handle($request, fn () => new Response);

        $this->assertSame('198.51.100.10', $request->ip());
    }

    public function test_trusted_proxy_configuration_defaults_to_no_trusted_hops(): void
    {
        $this->assertSame([], config('trustedproxy.proxies'));
    }
}
