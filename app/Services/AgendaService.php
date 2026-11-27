<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Enums\HabitFrequency;
use App\Enums\LoanDirection;
use App\Models\User;
use App\Support\UserClock;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * @phpstan-type AgendaItem array{type:string,title:string,time:?string,status:string,url:string,quick_action:?string}
 */
class AgendaService
{
    public function __construct(
        private readonly ObligationSchedule $obligationSchedule,
        private readonly UserClock $clock,
        private readonly LoanBalance $loanBalance
    ) {}

    private const MAX_OVERDUE_PERIODS = 12;

    public function forDate(User $user, CarbonInterface|string $date): Collection
    {
        $date = CarbonImmutable::parse($date)->toDateString();

        return $this->forRange($user, $date, $date)->get($date, collect());
    }

    /**
     * @return Collection<string, Collection<int, array{type:string,title:string,time:?string,status:string,url:string,quick_action:?string}>>
     */
    public function forRange(User $user, CarbonInterface|string $startsOn, CarbonInterface|string $endsOn): Collection
    {
        $timezone = $user->timezone;
        $weekStartsAt = $user->week_starts_on === 'sunday' ? CarbonImmutable::SUNDAY : CarbonImmutable::MONDAY;
        $startKey = CarbonImmutable::parse($startsOn)->toDateString();
        $endKey = CarbonImmutable::parse($endsOn)->toDateString();
        $start = CarbonImmutable::parse($startKey, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($endKey, $timezone)->startOfDay();
        $dates = $this->emptyAgendaRange();

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dates->put($date->toDateString(), $this->emptyAgendaDay());
        }
        if ($dates->isEmpty()) {
            return $dates;
        }

        $startUtc = $start->copy()->utc();
        $endUtc = $end->endOfDay()->utc();
        $tasks = $user->tasks()
            ->with('status')
            ->where(function ($query) use ($startUtc, $endUtc) {
                $query->whereBetween('starts_at', [$startUtc, $endUtc])
                    ->orWhere(function ($query) use ($startUtc, $endUtc) {
                        $query->whereNull('starts_at')->whereBetween('due_date', [$startUtc, $endUtc]);
                    });
            })->get();
        foreach ($tasks as $task) {
            $when = $task->starts_at ?? $task->due_date;
            $local = $when->copy()->timezone($timezone);
            $dates->get($local->toDateString())->push($this->item(
                'task',
                $task->title,
                $task->starts_at ? $local->format('H:i') : null,
                $task->status === null ? __('ui.task_status.no_status') : $task->status->name,
                route('user.tasks.edit', $task),
                null
            ));
        }

        $habits = $user->habits()->whereNull('archived_at')
            ->where('starts_on', '<=', $endKey)
            ->where(function ($query) use ($startKey) {
                $query->whereNull('ends_on')->orWhere('ends_on', '>=', $startKey);
            })
            ->with(['logs' => fn ($query) => $query->whereBetween('log_date', [$start->subDays(6)->toDateString(), $endKey])])
            ->get();
        foreach ($habits as $habit) {
            $logs = $habit->logs->keyBy(fn ($log) => $log->log_date->toDateString());
            for ($date = $start; $date->lte($end); $date = $date->addDay()) {
                if ($date->lt($habit->starts_on) || ($habit->ends_on && $date->gt($habit->ends_on))) {
                    continue;
                }
                $scheduled = match ($habit->frequency) {
                    HabitFrequency::Daily => true,
                    HabitFrequency::Weekdays => in_array($date->dayOfWeekIso, array_map('intval', $habit->weekdays ?? []), true),
                    HabitFrequency::WeeklyCount => $logs->filter(fn ($log) => $log->status->value === 'done'
                        && $log->log_date->startOfWeek($weekStartsAt)->toDateString() === $date->startOfWeek($weekStartsAt)->toDateString())->count() < $habit->weekly_target,
                };
                if (! $scheduled) {
                    continue;
                }
                $status = $logs->get($date->toDateString())?->status->label() ?? __('ui.agenda.habit_not_marked');
                $dates->get($date->toDateString())->push($this->item(
                    'habit',
                    $habit->title,
                    $habit->preferred_time,
                    $status,
                    route('user.habits.index'),
                    route('user.habits.check-in', $habit)
                ));
            }
        }

        $obligations = $user->obligations()->whereNull('archived_at')
            ->where('starts_on', '<=', $endKey)
            ->with('payments')
            ->get();
        foreach ($obligations as $obligation) {
            $periods = $this->obligationSchedule->duePeriods($obligation, $startKey, $endKey);
            foreach ($periods as $due) {
                $key = $due->toDateString();
                if (! $dates->has($key)) {
                    continue;
                }
                $daysUntil = (int) CarbonImmutable::parse($this->clock->date($user), $timezone)
                    ->diffInDays(CarbonImmutable::parse($key, $timezone), false);
                $status = match (true) {
                    $daysUntil < 0 => __('ui.agenda.late_days', ['days' => abs($daysUntil)]),
                    $daysUntil === 0 => __('ui.agenda.due_today'),
                    default => __('ui.agenda.due_in_days', ['days' => $daysUntil]),
                };
                $dates->get($key)->push($this->item(
                    $obligation->kind->value,
                    $obligation->title,
                    null,
                    $status,
                    route('user.obligations.index'),
                    route('user.obligations.pay', $obligation)
                ));
            }
        }

        $loans = $user->loans()->whereNull('archived_at')
            ->whereBetween('due_on', [$startKey, $endKey])
            ->with('repayments')
            ->get();
        foreach ($loans as $loan) {
            if ($loan->amount <= (int) $loan->repayments->sum('amount')) {
                continue;
            }
            $dates->get($loan->due_on->toDateString())->push($this->item(
                'loan_due',
                $loan->direction === LoanDirection::Lent ? 'Piutang · '.$loan->counterparty : 'Utang · '.$loan->counterparty,
                null,
                __('ui.agenda.due'),
                route('user.loans.index'),
                null
            ));
        }

        $goals = $user->goals()->where('status', GoalStatus::Active)
            ->whereBetween('due_on', [$startKey, $endKey])
            ->get();
        foreach ($goals as $goal) {
            $dates->get($goal->due_on->toDateString())->push($this->item(
                'goal_due',
                $goal->title,
                null,
                __('ui.agenda.target_date'),
                route('user.goals.index'),
                null
            ));
        }

        return $dates->map(fn (Collection $items) => $items->sortBy('time')->values());
    }

    public function attentionFor(User $user, CarbonInterface|string $today, int $daysAhead = 3): Collection
    {
        $todayDate = CarbonImmutable::parse($today, $user->timezone)->startOfDay();
        $today = $todayDate->toDateString();
        $through = $todayDate->addDays(max(0, $daysAhead))->toDateString();
        $yesterday = $todayDate->subDay()->toDateString();
        $items = collect();
        $loans = $user->loans()->whereNull('archived_at')
            ->whereNotNull('due_on')
            ->where('due_on', '<=', $through)
            ->with('repayments')
            ->get();

        foreach ($loans as $loan) {
            if ($this->loanBalance->remaining($loan) === 0) {
                continue;
            }

            $dueDate = CarbonImmutable::parse($loan->due_on->toDateString(), $user->timezone);
            $status = $dueDate->lt($todayDate)
                ? __('ui.agenda.late_days', ['days' => abs((int) $dueDate->diffInDays($todayDate))])
                : __('ui.agenda.due');
            $items->push($this->item(
                'loan_due',
                __($loan->direction === LoanDirection::Lent ? 'ui.agenda.loan_lent_title' : 'ui.agenda.loan_borrowed_title', [
                    'name' => $loan->counterparty,
                ]),
                null,
                $status,
                route('user.loans.index'),
                null
            ));
        }

        $historyFrom = $todayDate
            ->subMonthsNoOverflow(self::MAX_OVERDUE_PERIODS - 1)
            ->startOfMonth()
            ->toDateString();
        $obligations = $user->obligations()->whereNull('archived_at')
            ->where('starts_on', '<=', $through)
            ->with('payments')
            ->get();

        foreach ($obligations as $obligation) {
            $overdueFrom = max($historyFrom, $obligation->starts_on->toDateString());
            $overdue = $this->obligationSchedule->duePeriods($obligation, $overdueFrom, $yesterday);

            if ($overdue !== []) {
                $periodsLate = count($overdue);
                $periodLabel = $periodsLate === self::MAX_OVERDUE_PERIODS ? $periodsLate.'+' : (string) $periodsLate;
                $items->push($this->item(
                    $obligation->kind->value,
                    $obligation->title,
                    null,
                    __('ui.agenda.late_periods', ['periods' => $periodLabel]),
                    route('user.obligations.index'),
                    route('user.obligations.index')
                ));

                continue;
            }

            $reminderDays = (int) ($obligation->remind_days_before ?? 3);
            $reminderThrough = $todayDate->addDays($reminderDays)->toDateString();
            $upcoming = $this->obligationSchedule->duePeriods($obligation, $today, $reminderThrough);
            $due = $upcoming[0] ?? null;

            if ($due === null) {
                continue;
            }

            $daysUntil = (int) $todayDate->diffInDays($due, false);
            $status = $daysUntil === 0
                ? __('ui.agenda.due_today')
                : __('ui.agenda.due_in_days', ['days' => $daysUntil]);
            $items->push($this->item(
                $obligation->kind->value,
                $obligation->title,
                null,
                $status,
                route('user.obligations.index'),
                route('user.obligations.pay', $obligation)
            ));
        }

        return $items->values();
    }

    /** @return Collection<string, Collection<int, AgendaItem>> */
    private function emptyAgendaRange(): Collection
    {
        return collect();
    }

    /** @return Collection<int, AgendaItem> */
    private function emptyAgendaDay(): Collection
    {
        return collect();
    }

    /** @return AgendaItem */
    private function item(string $type, string $title, ?string $time, string $status, string $url, ?string $quickAction): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'time' => $time,
            'status' => $status,
            'url' => $url,
            'quick_action' => $quickAction,
        ];
    }
}
