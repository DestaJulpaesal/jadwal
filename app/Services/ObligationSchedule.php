<?php

namespace App\Services;

use App\Enums\ObligationKind;
use App\Models\Obligation;
use Carbon\CarbonImmutable;

class ObligationSchedule
{
    public function __construct(private readonly ObligationScheduler $scheduler) {}

    /**
     * @return list<CarbonImmutable>
     */
    public function scheduledPeriods(Obligation $obligation, string $from, string $through): array
    {
        $from = CarbonImmutable::parse($from)->toDateString();
        $through = CarbonImmutable::parse($through)->toDateString();
        $startsOn = $obligation->starts_on->toDateString();

        if ($from > $through || $startsOn > $through) {
            return [];
        }

        $obligation->loadMissing('payments');
        $isInstallment = $obligation->kind === ObligationKind::Installment;
        $installmentTotal = (int) $obligation->installments_total;

        if ($isInstallment && ($installmentTotal === 0 || $obligation->payments->count() >= $installmentTotal)) {
            return [];
        }

        $start = $isInstallment ? $startsOn : max($startsOn, $from);
        $firstMonth = CarbonImmutable::parse($start)->startOfMonth();
        $lastMonth = CarbonImmutable::parse($through)->startOfMonth();
        $periodLimit = $firstMonth->diffInMonths($lastMonth) + 1;
        $limit = $isInstallment ? $installmentTotal + 1 : $periodLimit;
        $periods = $this->scheduler->periodsBetween(
            $startsOn,
            $obligation->due_day,
            $start,
            $through,
            $limit
        );
        $periods = array_values(array_filter(
            $periods,
            fn (CarbonImmutable $date) => $date->toDateString() >= $from
                && $date->toDateString() >= $startsOn
                && $date->toDateString() <= $through
        ));

        return $isInstallment ? array_slice($periods, 0, $installmentTotal) : $periods;
    }

    /**
     * @return list<CarbonImmutable>
     */
    public function duePeriods(Obligation $obligation, string $from, string $through): array
    {
        $obligation->loadMissing('payments');
        $paidDates = $obligation->payments
            ->map(fn ($payment) => $payment->due_on->toDateString())
            ->all();

        return array_values(array_filter(
            $this->scheduledPeriods($obligation, $from, $through),
            fn (CarbonImmutable $date) => ! in_array($date->toDateString(), $paidDates, true)
        ));
    }
}
