<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Vite;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = config('security.csp_mode', 'off');

        if (! in_array($mode, ['off', 'report', 'enforce'], true)) {
            throw new InvalidArgumentException('CSP_MODE must be off, report, or enforce.');
        }

        $nonce = Vite::useCspNonce();
        view()->share('cspNonce', $nonce);
        $response = $next($request);

        foreach ([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ] as $header => $value) {
            $response->headers->set($header, $value);
        }

        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-cache');
        }

        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($mode !== 'off') {
            $directives = [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'nonce-{$nonce}'",
                "style-src-attr 'unsafe-inline'",
                "img-src 'self' data:",
                "font-src 'self'",
                "connect-src 'self'",
                "form-action 'self'",
                "base-uri 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "worker-src 'self'",
                "manifest-src 'self'",
                'report-uri '.$this->reportUri(),
            ];

            if (app()->environment('local')) {
                $directives[1] .= ' http://localhost:5173 http://127.0.0.1:5173';
                $directives[2] .= ' http://localhost:5173 http://127.0.0.1:5173';
                $directives[6] .= ' http://localhost:5173 ws://localhost:5173 http://127.0.0.1:5173 ws://127.0.0.1:5173';
            }

            $header = implode('; ', $directives);
            $response->headers->set(
                $mode === 'report' ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
                $header
            );
        }

        return $response;
    }

    private function reportUri(): string
    {
        $uri = config('security.csp_report_uri', '/csp-report');

        if (! is_string($uri) || ! preg_match('#\A/(?!/)[A-Za-z0-9/_-]*\z#', $uri)) {
            throw new InvalidArgumentException('CSP_REPORT_URI must be a same-origin path.');
        }

        return $uri;
    }
}
