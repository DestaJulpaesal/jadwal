<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $content = $request->getContent();
        $maxBytes = (int) config('security.csp_report_max_bytes', 16384);

        if (strlen($content) > $maxBytes) {
            return response('', 413);
        }

        try {
            $payload = json_decode($content, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response('', 400);
        }

        if (! is_array($payload)) {
            return response('', 400);
        }

        $reports = array_is_list($payload)
            ? array_slice($payload, 0, 20)
            : [$payload['csp-report'] ?? $payload];
        $logged = 0;

        foreach ($reports as $report) {
            if (is_array($report) && isset($report['body']) && is_array($report['body'])) {
                $report = $report['body'];
            }

            if (! is_array($report)) {
                continue;
            }

            $directive = $this->cleanText(
                $report['effective-directive'] ?? $report['effectiveDirective'] ?? $report['violated-directive'] ?? '',
                100
            );
            if ($directive === '') {
                continue;
            }

            Log::channel('csp')->warning('Content Security Policy violation', [
                'directive' => $directive,
                'blocked_origin' => $this->origin($report['blocked-uri'] ?? $report['blockedURL'] ?? ''),
                'source_origin' => $this->origin($report['source-file'] ?? $report['sourceFile'] ?? ''),
                'disposition' => $this->cleanText($report['disposition'] ?? 'report', 20),
            ]);
            $logged++;
        }

        if ($logged === 0) {
            return response('', 400);
        }

        return response('', 204);
    }

    private function cleanText(mixed $value, int $maxLength): string
    {
        if (! is_string($value)) {
            return '';
        }

        return substr(preg_replace('/[^A-Za-z0-9 _\'-]/', '', $value) ?? '', 0, $maxLength);
    }

    private function origin(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        if (in_array($value, ['self', 'inline', 'eval'], true)) {
            return $value;
        }

        $parts = parse_url($value);
        if (! is_array($parts) || ! isset($parts['host'])) {
            return 'relative';
        }

        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $host = strtolower($parts['host']);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return substr(($scheme !== '' ? $scheme.'://' : '').$host.$port, 0, 200);
    }
}
