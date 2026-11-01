<?php

namespace App\Enums;

enum HabitFrequency: string
{
    case Daily = 'daily';
    case Weekdays = 'weekdays';
    case WeeklyCount = 'weekly_count';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Setiap hari',
            self::Weekdays => 'Hari tertentu',
            self::WeeklyCount => 'Beberapa kali seminggu',
        };
    }
}
