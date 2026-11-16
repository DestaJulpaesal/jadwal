<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class MonthRange
{
    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end
    ) {}

    public static function fromString(string $month): self
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $month)) {
            throw new InvalidArgumentException('Month must use the YYYY-MM format.');
        }

        $start = CarbonImmutable::createFromFormat('!Y-m', $month);

        if ($start === false || $start->format('Y-m') !== $month) {
            throw new InvalidArgumentException('Month must use the YYYY-MM format.');
        }

        $start = $start->startOfMonth();

        return new self($start, $start->endOfMonth());
    }

    public function previous(): self
    {
        return self::fromString($this->start->subMonthNoOverflow()->format('Y-m'));
    }

    public function next(): self
    {
        return self::fromString($this->start->addMonthNoOverflow()->format('Y-m'));
    }

    public function key(): string
    {
        return $this->start->format('Y-m');
    }
}
