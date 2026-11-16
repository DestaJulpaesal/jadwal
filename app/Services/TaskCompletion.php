<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class TaskCompletion
{
    public function forStatusTransition(
        bool $wasCompleted,
        bool $isCompleted,
        ?DateTimeInterface $currentCompletion
    ): ?CarbonImmutable {
        if (! $isCompleted) {
            return null;
        }

        if ($wasCompleted) {
            return $currentCompletion === null
                ? null
                : CarbonImmutable::instance($currentCompletion)->utc();
        }

        return CarbonImmutable::now('UTC');
    }
}
