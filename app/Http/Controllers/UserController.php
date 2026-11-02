<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\MonthlySummary;
use App\Services\WalletBalance;
use App\Support\UserClock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UserController extends Controller
{
    public function index(Request $request, UserClock $clock, AgendaService $agenda, MonthlySummary $summary, WalletBalance $balances)
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);
        $user = $request->user();
        $today = $clock->date($user);
        $todayStartLocal = Carbon::parse($today, $user->timezone)->startOfDay();
        $weekStartsAt = $user->week_starts_on === 'sunday' ? Carbon::SUNDAY : Carbon::MONDAY;
        $tomorrowStartLocal = $todayStartLocal->copy()->addDay();
        $nextWeekStartLocal = $tomorrowStartLocal->copy()->addDays(7);
        $totalTasks = $user->tasks()->count();
        $todayTasks = $this->tasksInRange(
            $user,
            $todayStartLocal->copy()->utc(),
            $tomorrowStartLocal->copy()->utc()
        );
        $upcomingTasks = $this->tasksInRange(
            $user,
            $tomorrowStartLocal->copy()->utc(),
            $nextWeekStartLocal->copy()->utc(),
            true
        );
        $nowUtc = Carbon::now('UTC');
        $overdueTasks = $user->tasks()
            ->with(['category', 'status'])
            ->whereDoesntHave('status', fn ($query) => $query->where('is_completed', true))
            ->where(function ($query) use ($nowUtc) {
                $query->where(function ($query) use ($nowUtc) {
                    $query->whereNotNull('due_date')->where('due_date', '<', $nowUtc);
                })->orWhere(function ($query) use ($nowUtc) {
                    $query->whereNull('due_date')
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '<', $nowUtc);
                });
            })
            ->get()
            ->sortBy(fn ($task) => ($task->due_date ?? $task->ends_at)->timestamp);

        $todayHabits = $user->habits()->whereNull('archived_at')
            ->where('starts_on', '<=', $today)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->with(['logs' => fn ($query) => $query->whereBetween(
                'log_date',
                [$todayStartLocal->copy()->startOfWeek($weekStartsAt)->toDateString(), $today]
            )])
            ->orderBy('title')
            ->get()
            ->filter(function (Habit $habit) use ($todayStartLocal) {
                return match ($habit->frequency) {
                    \App\Enums\HabitFrequency::Daily => true,
                    \App\Enums\HabitFrequency::Weekdays => in_array($todayStartLocal->dayOfWeekIso, array_map('intval', $habit->weekdays ?? []), true),
                    \App\Enums\HabitFrequency::WeeklyCount => $habit->logs->filter(
                        fn ($log) => $log->status === \App\Enums\HabitLogStatus::Done
                    )->count() < $habit->weekly_target,
                };
            })
            ->values()
            ->each(function (Habit $habit) use ($today) {
                $habit->setAttribute('today_log', $habit->logs->first(
                    fn ($log) => $log->log_date->toDateString() === $today
                ));
            });
        $doneHabits = $todayHabits->filter(fn ($habit) => $habit->today_log?->status === \App\Enums\HabitLogStatus::Done)->count();
        $attentionItems = $agenda->attentionFor($user, $today);
        $agendaByDay = $agenda->forRange($user, $today, Carbon::parse($today, $user->timezone)->addDays(7)->toDateString());
        $monthlySummary = $summary->forMonth($user, substr($today, 0, 7));
        $wallets = $balances->forUser($user);
        $totalBalance = (int) $wallets->sum('calculated_balance');
        $showOnboarding = ! $user->onboarding_dismissed_at
            && $totalTasks === 0
            && $user->habits()->exists() === false
            && $user->transactions()->exists() === false;

        $dashboardSections = $user->dashboard_sections ?? ['attention', 'habits', 'today_tasks', 'upcoming', 'finance'];
        $calendarData = $this->calendarData($user, $agenda, $validated['year'] ?? null, $validated['month'] ?? null);

        return view('user.dashboard', compact(
            'todayTasks',
            'upcomingTasks',
            'overdueTasks',
            'totalTasks',
            'today',
            'todayHabits',
            'doneHabits',
            'attentionItems',
            'agendaByDay',
            'monthlySummary',
            'wallets',
            'totalBalance',
            'showOnboarding',
            'dashboardSections'
        ) + $calendarData);
    }

    public function dismissOnboarding(Request $request)
    {
        $request->user()->forceFill(['onboarding_dismissed_at' => now()])->save();

        return back()->with('success', 'Panduan awal ditutup. Anda dapat mulai lagi kapan saja.');
    }

    private function tasksInRange(User $user, Carbon $startUtc, Carbon $endUtc, bool $excludeCompleted = false): Collection
    {
        $query = $user->tasks()
            ->with(['category', 'status'])
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->where('starts_at', '>=', $startUtc)
                    ->where('starts_at', '<', $endUtc)
                    ->orWhere(function ($query) use ($startUtc, $endUtc) {
                        $query->whereNull('starts_at')
                            ->where('due_date', '>=', $startUtc)
                            ->where('due_date', '<', $endUtc);
                    });
            });

        if ($excludeCompleted) {
            $query->whereDoesntHave('status', fn ($query) => $query->where('is_completed', true));
        }

        return $query->get()
            ->sortBy(fn ($task) => ($task->starts_at ?? $task->due_date)->timestamp);
    }

    public function calendar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);

        return redirect()->route('user.dashboard', $validated);
    }

    private function calendarData(User $user, AgendaService $agenda, ?int $year = null, ?int $monthNumber = null): array
    {
        $timezone = $user->timezone;
        $now = Carbon::now($timezone);
        $month = Carbon::createFromDate(
            $year ?? $now->year,
            $monthNumber ?? $now->month,
            1,
            $timezone
        )->startOfMonth();
        $weekStartsAt = $user->week_starts_on === 'sunday' ? Carbon::SUNDAY : Carbon::MONDAY;
        $weekEndsAt = ($weekStartsAt + 6) % 7;
        $calendarStart = $month->copy()->startOfWeek($weekStartsAt)->startOfDay();
        $calendarEnd = $month->copy()->endOfMonth()->endOfWeek($weekEndsAt)->endOfDay();
        $rangeStartUtc = $calendarStart->copy()->utc();
        $rangeEndUtc = $calendarEnd->copy()->utc();
        $tasksByDay = $user->tasks()
            ->with(['category', 'status'])
            ->where(function ($query) use ($rangeStartUtc, $rangeEndUtc) {
                $query->whereBetween('starts_at', [$rangeStartUtc, $rangeEndUtc])
                    ->orWhere(function ($query) use ($rangeStartUtc, $rangeEndUtc) {
                        $query->whereNull('starts_at')
                            ->whereBetween('due_date', [$rangeStartUtc, $rangeEndUtc]);
                    });
            })
            ->get()
            ->sortBy(fn ($task) => ($task->starts_at ?? $task->due_date)->timestamp)
            ->groupBy(fn ($task) => ($task->starts_at ?? $task->due_date)->timezone($timezone)->toDateString());
        $calendarAgendaByDay = $agenda->forRange($user, $calendarStart->toDateString(), $calendarEnd->toDateString());
        $days = collect();

        for ($date = $calendarStart->copy(); $date->lte($calendarEnd); $date->addDay()) {
            $day = $date->copy();
            $days->push([
                'date' => $day,
                'in_month' => $day->month === $month->month,
                'tasks' => $tasksByDay->get($day->toDateString(), collect()),
            ]);
        }

        $weeks = $days->chunk(7);
        $previousMonth = $month->copy()->subMonthNoOverflow();
        $nextMonth = $month->copy()->addMonthNoOverflow();

        return compact('month', 'weeks', 'previousMonth', 'nextMonth', 'calendarAgendaByDay', 'weekStartsAt');
    }
}
