<?php

namespace App\Http\Controllers;

use App\Support\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const DASHBOARD_SECTIONS = [
        'attention' => 'Perlu perhatian',
        'habits' => 'Kebiasaan hari ini',
        'today_tasks' => 'Jadwal hari ini',
        'upcoming' => '7 hari mendatang',
        'finance' => 'Ringkasan uang bulan ini',
    ];

    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('settings.edit', [
            'user' => $user,
            'dashboardSections' => $user->dashboard_sections ?? array_keys(self::DASHBOARD_SECTIONS),
            'dashboardSectionLabels' => self::DASHBOARD_SECTIONS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'timezone'],
            'week_starts_on' => ['required', Rule::in(['monday', 'sunday'])],
            'theme' => ['required', Rule::in(['light', 'dark', 'system'])],
            'digest_enabled' => ['required', 'boolean'],
            'digest_time' => ['required', 'date_format:H:i'],
            'digest_days' => ['required', 'array', 'min:1'],
            'digest_days.*' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5, 6, 7])],
            'hide_financial_amounts' => ['required', 'boolean'],
            'dashboard_sections' => ['required', 'array:attention,habits,today_tasks,upcoming,finance'],
            'dashboard_sections.*' => ['required', 'boolean'],
        ], __('validation.catalog.settings'));

        $dashboardSections = array_keys(array_filter($validated['dashboard_sections']));

        $request->user()->update([
            'timezone' => $validated['timezone'],
            'week_starts_on' => $validated['week_starts_on'],
            'theme' => $validated['theme'],
            'digest_enabled' => $validated['digest_enabled'],
            'digest_time' => $validated['digest_time'],
            'digest_days' => array_map('intval', $validated['digest_days']),
            'hide_financial_amounts' => $validated['hide_financial_amounts'],
            'dashboard_sections' => $dashboardSections,
        ]);

        return Feedback::success(redirect()->route('settings.edit'), __('ui.feedback.settings_saved'));
    }
}
