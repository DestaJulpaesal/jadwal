<?php

namespace App\Services;

use App\Enums\GoalType;
use App\Models\Goal;
use App\Support\UserClock;

class GoalProgress
{
    public function __construct(
        private readonly WalletBalance $walletBalance,
        private readonly UserClock $clock
    ) {}

    public function current(Goal $goal): int
    {
        return match ($goal->type) {
            GoalType::Number => $goal->start_value + (int) ($goal->relationLoaded('updates')
                ? $goal->updates->sum('amount')
                : $goal->updates()->sum('amount')),
            GoalType::Savings => ($wallet = $goal->relationLoaded('wallet') ? $goal->wallet : $goal->wallet()->first())
                ? $this->walletBalance->forWallet($wallet)
                : 0,
            GoalType::Habit => $this->habitProgress($goal),
        };
    }

    public function percent(Goal $goal, ?int $current = null): int
    {
        $current ??= $this->current($goal);
        $range = $goal->target_value - $goal->start_value;
        if ($range <= 0) {
            return $current >= $goal->target_value ? 100 : 0;
        }

        return max(0, min(100, (int) round(($current - $goal->start_value) * 100 / $range)));
    }

    public function remainingPerDay(Goal $goal, int $current): ?int
    {
        if (! $goal->due_on) {
            return null;
        }
        $remaining = max(0, $goal->target_value - $current);
        $user = $goal->user()->firstOrFail();
        $days = $this->clock->today($user)->startOfDay()->diffInDays($goal->due_on->startOfDay(), false);

        return $days < 0 ? null : (int) ceil($remaining / max(1, $days));
    }

    private function habitProgress(Goal $goal): int
    {
        $habit = $goal->relationLoaded('habit') ? $goal->habit : $goal->habit()->first();
        if (! $habit) {
            return 0;
        }
        $user = $goal->relationLoaded('user') ? $goal->user : $goal->user()->firstOrFail();
        $start = max(
            $habit->starts_on->toDateString(),
            $goal->created_at->timezone($user->timezone)->toDateString()
        );
        $today = $this->clock->date($user);
        $logs = $habit->logs()->where('status', 'done')
            ->whereBetween('log_date', [$start, $today]);

        return $habit->goal_unit
            ? (int) $logs->sum('value')
            : (int) $logs->count();
    }
}
