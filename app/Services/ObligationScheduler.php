<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class ObligationScheduler
{
    public function dueDate(int $dueDay, int $year, int $month): CarbonImmutable
    {
        $firstOfMonth = CarbonImmutable::create($year, $month, 1);
        $day = min($dueDay, $firstOfMonth->daysInMonth);

        return $firstOfMonth->day($day)->startOfDay();
    }

    /**
     * @return list<CarbonImmutable>
     */
    public function periods(string $startsOn, int $dueDay, string $throughDate, int $limit = 12): array
    {
        return $this->periodsBetween($startsOn, $dueDay, $startsOn, $throughDate, $limit);
    }

    /**
     * @return list<CarbonImmutable>
     */
    public function periodsBetween(string $startsOn, int $dueDay, string $fromDate, string $throughDate, int $limit = 12): array
    {
        $start = CarbonImmutable::parse($startsOn)->startOfMonth();
        $from = CarbonImmutable::parse($fromDate)->startOfMonth();
        $start = $start->max($from);
        $through = CarbonImmutable::parse($throughDate)->startOfMonth();
        $periods = [];

        for ($month = $start; $month->lte($through) && count($periods) < $limit; $month = $month->addMonthNoOverflow()) {
            $periods[] = $this->dueDate($dueDay, $month->year, $month->month);
        }

        return $periods;
    }
}
