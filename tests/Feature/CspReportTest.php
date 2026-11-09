<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CspReportTest extends TestCase
{
    public function test_csp_report_accepts_legacy_payload_and_logs_only_sanitized_origins(): void
    {
        $this->withoutExceptionHandling();
        $logger = Mockery::mock();
        $logger->shouldReceive('warning')
            ->once()
            ->with('Content Security Policy violation', Mockery::on(function (array $context): bool {
                $this->assertSame('script-src', $context['directive']);
                $this->assertSame('https://cdn.example.test', $context['blocked_origin']);
                $this->assertSame('https://jadwal.example.test', $context['source_origin']);
                $this->assertArrayNotHasKey('document-uri', $context);

                return true;
            }));
        Log::shouldReceive('channel')->once()->with('csp')->andReturn($logger);
        Log::shouldReceive('withoutContext')->once()->andReturnNull();
        Log::shouldReceive('withContext')->once()->andReturnNull();

        $this->postJson('/csp-report', [
            'csp-report' => [
                'effective-directive' => 'script-src',
                'blocked-uri' => 'https://cdn.example.test/library.js?token=private',
                'source-file' => 'https://jadwal.example.test/user?email=private',
                'document-uri' => 'https://jadwal.example.test/private-page?name=private',
            ],
        ])->assertNoContent();
    }

    public function test_csp_report_rejects_malformed_and_oversized_payloads(): void
    {
        $this->call('POST', '/csp-report', [], [], [], ['CONTENT_TYPE' => 'application/json'], '{')
            ->assertBadRequest();

        $this->call(
            'POST',
            '/csp-report',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/csp-report'],
            str_repeat('x', config('security.csp_report_max_bytes') + 1)
        )->assertStatus(413);
    }

    public function test_csp_report_accepts_reporting_api_batches(): void
    {
        $this->withoutExceptionHandling();
        $logger = Mockery::mock();
        $logger->shouldReceive('warning')->twice();
        Log::shouldReceive('channel')->twice()->with('csp')->andReturn($logger);
        Log::shouldReceive('withoutContext')->once()->andReturnNull();
        Log::shouldReceive('withContext')->once()->andReturnNull();

        $this->postJson('/csp-report', [
            [
                'type' => 'csp-violation',
                'body' => ['effectiveDirective' => 'style-src', 'blockedURL' => 'https://cdn.example.test/a.css'],
            ],
            [
                'type' => 'csp-violation',
                'body' => ['effectiveDirective' => 'img-src', 'blockedURL' => 'data:'],
            ],
        ])->assertNoContent();
    }

    public function test_csp_report_endpoint_is_rate_limited(): void
    {
        $payload = ['effective-directive' => 'script-src'];
        $response = null;

        for ($attempt = 0; $attempt < 31; $attempt++) {
            $response = $this->postJson('/csp-report', $payload);
        }

        $response->assertTooManyRequests();
    }
}
