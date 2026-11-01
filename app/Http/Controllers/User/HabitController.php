<?php

namespace App\Http\Controllers\User;

use App\Enums\HabitFrequency;
use App\Enums\HabitLogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveHabitRequest;
use App\Models\Habit;
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
            ->map(function (Habit $habit) use ($today) {
                $logs = $habit->logs->mapWithKeys(fn ($log) => [
                    $log->log_date->toDateString() => $log->status->value,
                ])->all();
                $habit->setAttribute('statistics', app(\App\Services\HabitStreakCalculator::class)->statistics(
                    $habit->frequency,
                    $habit->starts_on->toDateString(),
                    $habit->weekdays,
                    $habit->weekly_target,
                    $logs,
                    \Illuminate\Support\Carbon::parse($today)
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
            'categories' => $request->user()->categories()->orderBy('name')->get(),
            'today' => $clock->date($request->user()),
        ]);
    }

    public function store(SaveHabitRequest $request)
    {
        $data = $this->normalizeFrequencyData($request->validated());
        $request->user()->habits()->create($data);

        return redirect()->route('user.habits.index')->with('success', 'Kebiasaan berhasil ditambahkan.');
    }

    public function edit(Request $request, Habit $habit, UserClock $clock)
    {
        $this->authorize('update', $habit);

        return view('user.habits.edit', [
            'habit' => $habit,
            'categories' => $request->user()->categories()->orderBy('name')->get(),
            'today' => $clock->date($request->user()),
        ]);
    }

    public function update(SaveHabitRequest $request, Habit $habit)
    {
        $this->authorize('update', $habit);
        $habit->update($this->normalizeFrequencyData($request->validated()));

        return redirect()->route('user.habits.index')->with('success', 'Kebiasaan berhasil diperbarui.');
    }

    public function checkIn(Request $request, Habit $habit, UserClock $clock)
    {
        $this->authorize('update', $habit);
        $data = $request->validate([
            'log_date' => ['nullable', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(array_column(HabitLogStatus::cases(), 'value'))],
            'value' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $today = $clock->date($request->user());
        $date = $data['log_date'] ?? $today;
        abort_if($date > $today, 422, 'Tanggal pencatatan tidak boleh di masa depan.');
        abort_if($date < \Illuminate\Support\Carbon::parse($today)->subDays(7)->toDateString(), 422, 'Catatan hanya dapat diubah sampai 7 hari ke belakang.');
        abort_if($date < $habit->starts_on->toDateString(), 422, 'Tanggal pencatatan sebelum kebiasaan dimulai.');
        abort_if($habit->ends_on && $date > $habit->ends_on->toDateString(), 422, 'Kebiasaan ini sudah berakhir pada tanggal tersebut.');
        if ($habit->frequency === HabitFrequency::Weekdays) {
            abort_unless(in_array(\Illuminate\Support\Carbon::parse($date)->dayOfWeekIso, array_map('intval', $habit->weekdays ?? []), true), 422, 'Kebiasaan ini tidak dijadwalkan pada hari tersebut.');
        }

        $request->user()->habitLogs()->updateOrCreate(
            ['habit_id' => $habit->id, 'log_date' => $date],
            [
                'status' => $data['status'],
                'value' => $data['value'] ?? ($data['status'] === HabitLogStatus::Done->value ? $habit->goal_value : null),
                'note' => $data['note'] ?? null,
            ]
        );

        return back()->with('success', 'Catatan kebiasaan berhasil disimpan.');
    }

    public function cancelCheckIn(Request $request, Habit $habit, UserClock $clock)
    {
        $this->authorize('update', $habit);
        $date = $request->validate(['log_date' => ['required', 'date_format:Y-m-d']])['log_date'];
        $today = $clock->date($request->user());
        abort_if(
            $date < \Illuminate\Support\Carbon::parse($today)->subDays(7)->toDateString(),
            422,
            'Catatan hanya dapat diubah sampai 7 hari ke belakang.'
        );
        abort_if($date > $today, 422, 'Tanggal pencatatan tidak boleh di masa depan.');
        abort_if($date < $habit->starts_on->toDateString(), 422, 'Tanggal pencatatan sebelum kebiasaan dimulai.');
        $request->user()->habitLogs()->where('habit_id', $habit->id)->where('log_date', $date)->delete();

        return back()->with('success', 'Tanda selesai dibatalkan.');
    }

    public function archive(Habit $habit)
    {
        $this->authorize('update', $habit);
        $habit->update(['archived_at' => now()]);

        return redirect()->route('user.habits.index')->with('success', 'Kebiasaan diarsipkan.');
    }

    private function normalizeFrequencyData(array $data): array
    {
        $frequency = HabitFrequency::from($data['frequency']);
        $data['weekdays'] = $frequency === HabitFrequency::Weekdays ? array_map('intval', $data['weekdays']) : null;
        $data['weekly_target'] = $frequency === HabitFrequency::WeeklyCount ? (int) $data['weekly_target'] : null;

        return $data;
    }
}
