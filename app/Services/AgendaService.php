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

class AgendaService
{
    public function __construct(
        private readonly ObligationScheduler $obligationScheduler,
        private readonly UserClock $clock,
        private readonly LoanBalance $loanBalance
    ) {}

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
        $startKey = CarbonImmutable::parse($startsOn)->toDateString();
        $endKey = CarbonImmutable::parse($endsOn)->toDateString();
        $start = CarbonImmutable::parse($startKey, $timezone)->startOfDay();
        $end = CarbonImmutable::parse($endKey, $timezone)->startOfDay();
        $dates = collect();

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dates->put($date->toDateString(), collect());
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
                $task->status?->name ?? 'Belum berstatus',
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
                        && $log->log_date->startOfWeek()->toDateString() === $date->startOfWeek()->toDateString())->count() < $habit->weekly_target,
                };
                if (! $scheduled) {
                    continue;
                }
                $status = $logs->get($date->toDateString())?->status->label() ?? 'Belum ditandai';
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
            $from = max($obligation->starts_on->toDateString(), $startKey);
            $periods = $this->obligationScheduler->periods($from, $obligation->due_day, $endKey);
            foreach ($periods as $due) {
                $key = $due->toDateString();
                if (! $dates->has($key) || $obligation->payments->contains(
                    fn ($payment) => $payment->due_on->toDateString() === $key
                )) {
                    continue;
                }
                $daysUntil = CarbonImmutable::parse($this->clock->date($user), $timezone)
                    ->diffInDays(CarbonImmutable::parse($key, $timezone), false);
                $status = match (true) {
                    $daysUntil < 0 => 'Terlambat '.abs($daysUntil).' hari',
                    $daysUntil === 0 => 'Jatuh tempo hari ini',
                    default => 'Jatuh tempo '.$daysUntil.' hari lagi',
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
                'Jatuh tempo',
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
                'Target tanggal',
                route('user.goals.index'),
                null
            ));
        }

        return $dates->map(fn (Collection $items) => $items->sortBy('time')->values());
    }

    public function attentionFor(User $user, CarbonInterface|string $today, int $daysAhead = 3): Collection
    {
        $today = CarbonImmutable::parse($today, $user->timezone)->toDateString();
        $through = CarbonImmutable::parse($today, $user->timezone)->addDays($daysAhead)->toDateString();
        $items = $this->forRange($user, $today, $through)->flatten(1)
            ->filter(fn (array $item) => in_array($item['type'], ['bill', 'installment', 'loan_due'], true))
            ->values();
        $yesterday = CarbonImmutable::parse($today, $user->timezone)->subDay()->toDateString();

        if ($yesterday < $today) {
            $obligations = $user->obligations()->whereNull('archived_at')
                ->where('starts_on', '<', $today)->with('payments')->get();
            foreach ($obligations as $obligation) {
                $from = max(
                    $obligation->starts_on->toDateString(),
                    CarbonImmutable::parse($today, $user->timezone)->subMonths(11)->startOfMonth()->toDateString()
                );
                $periods = $this->obligationScheduler->periodsBetween(
                    $obligation->starts_on->toDateString(),
                    $obligation->due_day,
                    $from,
                    $yesterday
                );
                foreach ($periods as $due) {
                    if ($obligation->payments->contains(fn ($payment) => $payment->due_on->toDateString() === $due->toDateString())) {
                        continue;
                    }
                    $daysLate = $due->diffInDays(CarbonImmutable::parse($today, $user->timezone));
                    $items->push($this->item(
                        $obligation->kind->value,
                        $obligation->title,
                        null,
                        'Terlambat '.$daysLate.' hari',
                        route('user.obligations.index'),
                        route('user.obligations.index')
                    ));
                }
            }

            $loans = $user->loans()->whereNull('archived_at')->where('due_on', '<', $today)->with('repayments')->get();
            foreach ($loans as $loan) {
                if ($this->loanBalance->remaining($loan) === 0) {
                    continue;
                }
                $daysLate = $loan->due_on->diffInDays(CarbonImmutable::parse($today, $user->timezone));
                $items->push($this->item(
                    'loan_due',
                    ($loan->direction === LoanDirection::Lent ? 'Piutang · ' : 'Utang · ').$loan->counterparty,
                    null,
                    'Terlambat '.$daysLate.' hari',
                    route('user.loans.index'),
                    null
                ));
            }
        }

        return $items->unique(fn (array $item) => $item['type'].'|'.$item['title'].'|'.$item['status'])->values();
    }

    private function item(string $type, string $title, ?string $time, string $status, string $url, ?string $quickAction): array
    {
        return compact('type', 'title', 'time', 'status', 'url', 'quickAction');
    }
}
