<?php

namespace App\Http\Controllers\User;

use App\Enums\HabitFrequency;
use App\Enums\HabitLogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveHabitRequest;
use App\Models\Category;
use App\Models\Habit;
use App\Support\Feedback;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HabitController extends Controller
{
    public function index(Request $request, UserClock $clock)
    {
        $this->authorize('viewAny', Habit::class);
        $today = $clock->date($request->user());
        $habits = $request->user()->habits()
            ->whereNull('archived_at')
            ->where('starts_on', '<=', $today)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->with('logs')
            ->orderBy('title')
            ->get()
            ->map(function (Habit $habit) use ($today, $request) {
                $logs = $habit->logs->mapWithKeys(fn ($log) => [
                    $log->log_date->toDateString() => $log->status->value,
                ])->all();
                $habit->setAttribute('statistics', app(\App\Services\HabitStreakCalculator::class)->statistics(
                    $habit->frequency,
                    $habit->starts_on->toDateString(),
                    $habit->weekdays,
                    $habit->weekly_target,
                    $logs,
                    \Illuminate\Support\Carbon::parse($today),
                    $request->user()->week_starts_on === 'sunday'
                        ? \Carbon\CarbonImmutable::SUNDAY
                        : \Carbon\CarbonImmutable::MONDAY
                ));
                $habit->setAttribute('today_log', $habit->logs->first(
                    fn ($log) => $log->log_date->toDateString() === $today
                ));

                return $habit;
            });

        return view('user.habits.index', compact('habits', 'today'));
    }

    public function create(Request $request, UserClock $clock)
    {
        $this->authorize('create', Habit::class);

        $presets = config('presets.habits');
        $preset = $request->query('preset');
        $values = is_numeric($preset) && array_key_exists((int) $preset, $presets)
            ? $presets[(int) $preset]
            : ['frequency' => HabitFrequency::Daily->value, 'starts_on' => $clock->date($request->user())];

        return view('user.habits.create', [
            'habit' => new Habit($values),
            'categories' => Category::query()->orderBy('name')->get(),
            'today' => $clock->date($request->user()),
        ]);
    }

    public function store(SaveHabitRequest $request)
    {
        $data = $this->normalizeFrequencyData($request->validated());
        $request->user()->habits()->create($data);

        return Feedback::success(redirect()->route('user.habits.index'), __('ui.feedback.habit_created'));
    }

    public function edit(Request $request, Habit $habit, UserClock $clock)
    {
        $this->authorize('update', $habit);

        return view('user.habits.edit', [
            'habit' => $habit,
            'categories' => Category::query()->orderBy('name')->get(),
            'today' => $clock->date($request->user()),
        ]);
    }

    public function update(SaveHabitRequest $request, Habit $habit)
    {
        $this->authorize('update', $habit);
        $data = $this->normalizeFrequencyData($request->validated());
        $habit->update($data);

        return Feedback::success(redirect()->route('user.habits.index'), __('ui.feedback.habit_updated'));
    }

    public function checkIn(Request $request, Habit $habit, UserClock $clock)
    {
        $this->authorize('update', $habit);
        $data = $request->validate([
            'log_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(array_column(HabitLogStatus::cases(), 'value'))],
            'value' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'performed_at' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $today = $clock->date($request->user());
        $date = $data['log_date'] ?? $today;
        abort_if($date > $today, 422, __('ui.feedback.habit_date_future'));
        abort_if($date < \Illuminate\Support\Carbon::parse($today)->subDays(7)->toDateString(), 422, __('ui.feedback.habit_date_too_old'));
        abort_if($date < $habit->starts_on->toDateString(), 422, __('ui.feedback.habit_date_before_start'));
        abort_if($habit->ends_on && $date > $habit->ends_on->toDateString(), 422, __('ui.feedback.habit_already_ended'));
        if ($habit->frequency === HabitFrequency::Weekdays) {
            abort_unless(in_array(\Illuminate\Support\Carbon::parse($date)->dayOfWeekIso, array_map('intval', $habit->weekdays ?? []), true), 422, __('ui.feedback.habit_not_scheduled'));
        }

        $request->user()->habitLogs()->updateOrCreate(
            ['habit_id' => $habit->id, 'log_date' => $date],
            [
                'status' => $data['status'],
                'value' => $data['value'] ?? ($data['status'] === HabitLogStatus::Done->value ? $habit->goal_value : null),
                'duration_minutes' => $data['status'] === HabitLogStatus::Done->value ? ($data['duration_minutes'] ?? null) : null,
                'performed_at' => $data['status'] === HabitLogStatus::Done->value ? ($data['performed_at'] ?? null) : null,
                'note' => $data['note'] ?? null,
            ]
        );

        return Feedback::success(back(), __('ui.feedback.habit_logged'));
    }

    public function cancelCheckIn(Request $request, Habit $habit, UserClock $clock)
    {
        $this->authorize('update', $habit);
        $date = $request->validate(['log_date' => ['required', 'date_format:Y-m-d']])['log_date'];
        $today = $clock->date($request->user());
        abort_if(
            $date < \Illuminate\Support\Carbon::parse($today)->subDays(7)->toDateString(),
            422,
            __('ui.feedback.habit_date_too_old')
        );
        abort_if($date > $today, 422, __('ui.feedback.habit_date_future'));
        abort_if($date < $habit->starts_on->toDateString(), 422, __('ui.feedback.habit_date_before_start'));
        $request->user()->habitLogs()->where('habit_id', $habit->id)->where('log_date', $date)->delete();

        return Feedback::success(back(), __('ui.feedback.habit_log_cancelled'));
    }

    public function archive(Habit $habit)
    {
        $this->authorize('update', $habit);
        $habit->update(['archived_at' => now()]);

        return Feedback::success(redirect()->route('user.habits.index'), __('ui.feedback.habit_archived'));
    }

    private function normalizeFrequencyData(array $data): array
    {
        $frequency = HabitFrequency::from($data['frequency']);
        $data['weekdays'] = $frequency === HabitFrequency::Weekdays ? array_map('intval', $data['weekdays']) : null;
        $data['weekly_target'] = $frequency === HabitFrequency::WeeklyCount ? (int) $data['weekly_target'] : null;
        $data['schedule_times'] = $frequency === HabitFrequency::Weekdays
            ? collect($data['schedule_times'] ?? [])->filter()->mapWithKeys(fn ($time, $day) => [(int) $day => $time])->all()
            : null;
        if (($data['goal_unit'] ?? null) === 'other') {
            $data['goal_unit'] = trim($data['goal_unit_other'] ?? '');
        }
        unset($data['goal_unit_other']);

        return $data;
    }
}
