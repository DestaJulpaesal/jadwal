<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAuditLog;
use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', PasswordRule::defaults(), 'confirmed'],
        ]);

        Auth::logoutOtherDevices($validated['current_password']);
        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        UserAuditLog::query()->create([
            'user_id' => $request->user()->id,
            'action' => 'password_changed',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return Feedback::success(back(), __('ui.password_reset.changed'));
    }
}
