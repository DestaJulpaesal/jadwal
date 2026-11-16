<?php

namespace Tests\Unit;

use App\Support\MonthRange;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class MonthRangeTest extends TestCase
{
    public function test_month_range_uses_calendar_months_on_month_end_dates(): void
    {
        $this->travelTo(Carbon::parse('2026-12-31 23:59:59', 'UTC'));

        $range = MonthRange::fromString('2026-11');

        $this->assertSame('2026-11-01', $range->start->toDateString());
        $this->assertSame('2026-11-30', $range->end->toDateString());
        $this->assertSame('2026-11', $range->key());
        $this->assertSame('2026-10', $range->previous()->key());
        $this->assertSame('2026-12', $range->next()->key());
        $this->assertSame('2025-12', MonthRange::fromString('2026-01')->previous()->key());
        $this->assertSame('2027-01', MonthRange::fromString('2026-12')->next()->key());
    }

    public function test_month_range_handles_leap_february_and_rejects_invalid_months(): void
    {
        $range = MonthRange::fromString('2024-02');

        $this->assertSame('2024-02-01', $range->start->toDateString());
        $this->assertSame('2024-02-29', $range->end->toDateString());
        $this->expectException(InvalidArgumentException::class);

        MonthRange::fromString('2026-13');
    }
}
