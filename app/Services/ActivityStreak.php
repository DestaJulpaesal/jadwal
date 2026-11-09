<?php

namespace App\Services;

use App\Enums\HabitLogStatus;
use App\Models\User;
use Carbon\CarbonImmutable;

class ActivityStreak
{
    public function summary(User $user, string $today): array
    {
        $dates = $user->habitLogs()
            ->where('status', HabitLogStatus::Done->value)
            ->where('log_date', '<=', $today)
            ->pluck('log_date')
            ->map(fn ($date) => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date)
            ->merge(
                $user->tasks()
                    ->whereNotNull('completed_at')
                    ->where(
                        'completed_at',
                        '<',
                        CarbonImmutable::parse($today, $user->timezone)->addDay()->startOfDay()->utc()
                    )
                    ->get(['completed_at'])
                    ->map(fn ($task) => $task->completed_at->timezone($user->timezone)->toDateString())
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        $activeDates = array_fill_keys($dates, true);
        $todayDate = CarbonImmutable::parse($today);
        $anchor = isset($activeDates[$today])
            ? $todayDate
            : $todayDate->subDay();
        $currentStreak = 0;

        while (isset($activeDates[$anchor->toDateString()])) {
            $currentStreak++;
            $anchor = $anchor->subDay();
        }

        $streakHistory = [];
        $consecutiveDays = 0;
        $streakStart = null;
        $previousDate = null;

        foreach ($dates as $date) {
            $currentDate = CarbonImmutable::parse($date);
            if ($previousDate && ! $currentDate->isSameDay($previousDate->addDay())) {
                $streakHistory[] = [
                    'start' => $streakStart,
                    'end' => $previousDate,
                    'days' => $consecutiveDays,
                ];
                $consecutiveDays = 0;
                $streakStart = null;
            }

            $streakStart ??= $currentDate;
            $consecutiveDays++;
            $previousDate = $currentDate;
        }

        if ($previousDate) {
            $streakHistory[] = [
                'start' => $streakStart,
                'end' => $previousDate,
                'days' => $consecutiveDays,
            ];
        }

        foreach ($streakHistory as &$streak) {
            $streak['current'] = $currentStreak > 0
                && in_array($streak['end']->toDateString(), [$today, $todayDate->subDay()->toDateString()], true);
        }
        unset($streak);

        usort($streakHistory, function (array $first, array $second): int {
            return $second['days'] <=> $first['days']
                ?: $second['start']->getTimestamp() <=> $first['start']->getTimestamp();
        });

        $calendar = collect(range(13, 0))
            ->map(function (int $daysAgo) use ($todayDate, $activeDates): array {
                $date = $todayDate->subDays($daysAgo);
                $dateString = $date->toDateString();

                return [
                    'date' => $date,
                    'active' => isset($activeDates[$dateString]),
                    'today' => $daysAgo === 0,
                ];
            });

        return [
            'currentStreak' => $currentStreak,
            'bestStreak' => $streakHistory[0]['days'] ?? 0,
            'activeToday' => isset($activeDates[$today]),
            'calendar' => $calendar,
            'streakHistory' => $streakHistory,
        ];
    }
}
