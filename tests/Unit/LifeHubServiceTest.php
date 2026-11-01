<?php

namespace Tests\Unit;

use App\Enums\HabitFrequency;
use App\Services\HabitStreakCalculator;
use App\Services\ObligationScheduler;
use Carbon\Carbon;
use Tests\TestCase;

class LifeHubServiceTest extends TestCase
{
    public function test_daily_habit_streak_ignores_today_until_checked_and_skips_rest_days(): void
    {
        $result = app(HabitStreakCalculator::class)->statistics(
            HabitFrequency::Daily,
            '2026-10-25',
            null,
            null,
            [
                '2026-10-25' => 'done',
                '2026-10-26' => 'done',
                '2026-10-27' => 'skipped',
                '2026-10-28' => 'done',
                '2026-10-29' => 'done',
                '2026-10-30' => 'done',
                '2026-10-31' => 'done',
            ],
            Carbon::parse('2026-11-01')
        );

        $this->assertSame(['current' => 6, 'longest' => 6, 'completion_percent' => 75], $result);
    }

    public function test_weekly_habit_streak_is_counted_by_completed_monday_to_sunday_weeks(): void
    {
        $result = app(HabitStreakCalculator::class)->statistics(
            HabitFrequency::WeeklyCount,
            '2026-10-05',
            null,
            2,
            [
                '2026-10-05' => 'done',
                '2026-10-06' => 'done',
                '2026-10-12' => 'done',
                '2026-10-13' => 'done',
                '2026-10-19' => 'done',
            ],
            Carbon::parse('2026-10-21')
        );

        $this->assertSame(2, $result['current']);
        $this->assertSame(2, $result['longest']);
    }

    public function test_monthly_obligation_due_dates_clamp_to_the_last_day_of_the_month(): void
    {
        $scheduler = app(ObligationScheduler::class);

        $this->assertSame('2025-02-28', $scheduler->dueDate(31, 2025, 2)->toDateString());
        $this->assertSame('2024-02-29', $scheduler->dueDate(31, 2024, 2)->toDateString());
        $this->assertSame('2026-04-30', $scheduler->dueDate(31, 2026, 4)->toDateString());
        $this->assertSame('2026-12-31', $scheduler->dueDate(31, 2026, 12)->toDateString());
    }
}
