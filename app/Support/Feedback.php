<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

class Feedback
{
    public static function success(RedirectResponse $response, string $message, ?array $action = null): RedirectResponse
    {
        return self::flash($response, 'success', $message, $action);
    }

    public static function error(RedirectResponse $response, string $message, ?array $action = null): RedirectResponse
    {
        return self::flash($response, 'error', $message, $action);
    }

    public static function warning(RedirectResponse $response, string $message, ?array $action = null): RedirectResponse
    {
        return self::flash($response, 'warning', $message, $action);
    }

    public static function info(RedirectResponse $response, string $message, ?array $action = null): RedirectResponse
    {
        return self::flash($response, 'info', $message, $action);
    }

    private static function flash(RedirectResponse $response, string $type, string $message, ?array $action): RedirectResponse
    {
        return $response->with('feedback', compact('type', 'message', 'action'));
    }
}
