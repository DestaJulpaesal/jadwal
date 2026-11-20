<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Habit;
use App\Models\User;
use App\Services\AgendaService;
use App\Services\BudgetProgress;
use App\Services\GoalProgress;
use App\Services\MonthlySummary;
use App\Services\WalletBalance;
use App\Support\Feedback;
use App\Support\MonthRange;
use App\Support\UserClock;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(
        Request $request,
        UserClock $clock,
        AgendaService $agenda,
        MonthlySummary $summary,
        WalletBalance $balances,
        GoalProgress $goalProgress,
        BudgetProgress $budgetProgress
    ) {
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
        $incompleteTodayTasks = $todayTasks
            ->filter(fn ($task) => ! $task->status?->is_completed)
            ->count();
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
        $dashboardSections = $user->dashboard_sections ?? ['attention', 'habits', 'today_tasks', 'upcoming', 'finance'];
        $attentionItems = $agenda->attentionFor($user, $today);
        if (in_array('finance', $dashboardSections, true)) {
            $monthRange = MonthRange::fromString(substr($today, 0, 7));
            foreach ($budgetProgress->forMonth($user, $monthRange) as $budgetItem) {
                if (! in_array($budgetItem['status'], ['warning', 'over'], true)
                    || $budgetItem['category'] === null
                    || $budgetItem['category']->archived_at !== null) {
                    continue;
                }

                $attentionItems->push([
                    'type' => 'budget_attention',
                    'title' => 'Anggaran: '.$budgetItem['category']->name,
                    'time' => null,
                    'status' => __('ui.budget.attention_'.$budgetItem['status']),
                    'url' => route('user.reports.index', ['month' => $monthRange->key()]),
                    'quick_action' => null,
                ]);
            }
        }
        $agendaByDay = $agenda->forRange($user, $today, Carbon::parse($today, $user->timezone)->addDays(7)->toDateString());
        $monthlySummary = $summary->forMonth($user, substr($today, 0, 7));
        $wallets = $balances->forUser($user);
        $totalBalance = (int) $wallets->sum('calculated_balance');
        $activeGoals = $user->goals()
            ->where('status', \App\Enums\GoalStatus::Active)
            ->with(['updates', 'habit', 'wallet'])
            ->orderBy('due_on')
            ->orderBy('id')
            ->get()
            ->take(3);
        $activeGoals->each(function (Goal $goal) use ($goalProgress) {
            $current = $goalProgress->current($goal);
            $goal->setAttribute('current_value', $current);
            $goal->setAttribute('progress_percent', $goalProgress->percent($goal, $current));
        });
        $onboardingSteps = [
            [
                'label' => 'Tambah tugas pertama',
                'url' => route('user.tasks.create'),
                'completed' => $totalTasks > 0,
            ],
            [
                'label' => 'Pilih kebiasaan',
                'url' => route('user.habits.index'),
                'completed' => $user->habits()->exists(),
            ],
            [
                'label' => 'Catat uang pertama',
                'url' => route('user.money.index', ['section' => 'transactions', 'create' => 'transaction']),
                'completed' => $user->transactions()->exists(),
            ],
        ];
        $showOnboarding = ! $user->onboarding_dismissed_at
            && collect($onboardingSteps)->contains(fn (array $step) => ! $step['completed']);

        return view('user.dashboard', compact(
            'todayTasks',
            'upcomingTasks',
            'overdueTasks',
            'totalTasks',
            'incompleteTodayTasks',
            'today',
            'todayHabits',
            'doneHabits',
            'attentionItems',
            'agendaByDay',
            'monthlySummary',
            'wallets',
            'totalBalance',
            'activeGoals',
            'showOnboarding',
            'onboardingSteps',
            'dashboardSections'
        ));
    }

    public function dismissOnboarding(Request $request)
    {
        $request->user()->forceFill(['onboarding_dismissed_at' => now()])->save();

        return Feedback::success(back(), __('ui.feedback.onboarding_dismissed'));
    }

    public function restartOnboarding(Request $request)
    {
        $request->user()->forceFill(['onboarding_dismissed_at' => null])->save();

        return Feedback::success(
            redirect()->route('user.dashboard'),
            __('ui.feedback.onboarding_restarted')
        );
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

    public function calendar(Request $request, AgendaService $agenda): View
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
        ]);
        $user = $request->user();
        $calendarData = $this->calendarData($user, $agenda, $validated['year'] ?? null, $validated['month'] ?? null);

        return view('user.calendar', $calendarData);
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
