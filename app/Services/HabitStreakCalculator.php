<?php

namespace App\Services;

use App\Enums\HabitFrequency;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class HabitStreakCalculator
{
    /**
     * @param  array<string, string>  $logs  Map of ISO date to "done" or "skipped".
     * @return array{current: int, longest: int, completion_percent: int}
     */
    public function statistics(
        HabitFrequency|string $frequency,
        string $startsOn,
        ?array $weekdays,
        ?int $weeklyTarget,
        array $logs,
        CarbonInterface $today
    ): array {
        $frequency = $frequency instanceof HabitFrequency ? $frequency : HabitFrequency::from($frequency);
        $today = CarbonImmutable::instance($today)->startOfDay();
        $start = CarbonImmutable::parse($startsOn)->startOfDay();

        $statistics = $frequency === HabitFrequency::WeeklyCount
            ? $this->weeklyStatistics($start, $today, $weeklyTarget ?? 1, $logs)
            : $this->dailyStatistics($frequency, $start, $today, $weekdays ?? [], $logs);

        $statistics['completion_percent'] = $this->completionPercent(
            $frequency,
            $start,
            $today,
            $weekdays ?? [],
            $weeklyTarget ?? 1,
            $logs
        );

        return $statistics;
    }

    private function dailyStatistics(
        HabitFrequency $frequency,
        CarbonImmutable $start,
        CarbonImmutable $today,
        array $weekdays,
        array $logs
    ): array {
        $current = 0;
        $cursor = $today;
        if ($this->isScheduled($frequency, $today, $weekdays) && ! isset($logs[$today->toDateString()])) {
            $cursor = $cursor->subDay();
        }

        while ($cursor->gte($start)) {
            if ($this->isScheduled($frequency, $cursor, $weekdays)) {
                $status = $logs[$cursor->toDateString()] ?? null;
                if ($status === 'skipped') {
                    $cursor = $cursor->subDay();

                    continue;
                }
                if ($status !== 'done') {
                    break;
                }
                $current++;
            }
            $cursor = $cursor->subDay();
        }

        $longest = 0;
        $run = 0;
        for ($date = $start; $date->lte($today); $date = $date->addDay()) {
            if (! $this->isScheduled($frequency, $date, $weekdays)) {
                continue;
            }

            $status = $logs[$date->toDateString()] ?? null;
            if ($status === 'skipped') {
                continue;
            }
            if ($status === 'done') {
                $run++;
                $longest = max($longest, $run);
            } else {
                $run = 0;
            }
        }

        return ['current' => $current, 'longest' => $longest];
    }

    private function weeklyStatistics(CarbonImmutable $start, CarbonImmutable $today, int $target, array $logs): array
    {
        $startWeek = $start->startOfWeek(CarbonImmutable::MONDAY);
        $thisWeek = $today->startOfWeek(CarbonImmutable::MONDAY);
        $completed = [];

        for ($week = $startWeek; $week->lte($thisWeek); $week = $week->addWeek()) {
            $weekEnd = $week->addDays(6)->min($today);
            $done = 0;
            for ($date = $week->max($start); $date->lte($weekEnd); $date = $date->addDay()) {
                if (($logs[$date->toDateString()] ?? null) === 'done') {
                    $done++;
                }
            }
            $completed[] = $done >= $target;
        }

        $current = 0;
        $index = count($completed) - 1;
        if ($index >= 0 && ! $completed[$index]) {
            $index--;
        }
        for (; $index >= 0 && $completed[$index]; $index--) {
            $current++;
        }

        $longest = 0;
        $run = 0;
        foreach ($completed as $isComplete) {
            $run = $isComplete ? $run + 1 : 0;
            $longest = max($longest, $run);
        }

        return ['current' => $current, 'longest' => $longest];
    }

    private function completionPercent(
        HabitFrequency $frequency,
        CarbonImmutable $start,
        CarbonImmutable $today,
        array $weekdays,
        int $weeklyTarget,
        array $logs
    ): int {
        $rangeStart = $today->subDays(29)->max($start);
        $done = 0;
        $scheduled = 0;

        for ($date = $rangeStart; $date->lte($today); $date = $date->addDay()) {
            if ($frequency === HabitFrequency::WeeklyCount || $this->isScheduled($frequency, $date, $weekdays)) {
                $scheduled++;
            }
            if (($logs[$date->toDateString()] ?? null) === 'done') {
                $done++;
            }
        }

        if ($frequency === HabitFrequency::WeeklyCount) {
            $weeks = 0;
            for ($week = $rangeStart->startOfWeek(CarbonImmutable::MONDAY); $week->lte($today); $week = $week->addWeek()) {
                $weeks++;
            }
            $scheduled = max(1, $weeks) * $weeklyTarget;
        }

        return $scheduled === 0 ? 0 : min(100, (int) round($done * 100 / $scheduled));
    }

    private function isScheduled(HabitFrequency $frequency, CarbonImmutable $date, array $weekdays): bool
    {
        return match ($frequency) {
            HabitFrequency::Daily => true,
            HabitFrequency::Weekdays => in_array($date->dayOfWeekIso, array_map('intval', $weekdays), true),
            HabitFrequency::WeeklyCount => false,
        };
    }
}
