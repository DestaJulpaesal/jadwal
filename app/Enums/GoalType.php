<?php

namespace App\Enums;

enum GoalType: string
{
    case Number = 'number';
    case Savings = 'savings';
    case Habit = 'habit';

    public function label(): string
    {
        return match ($this) {
            self::Number => 'Target angka',
            self::Savings => 'Target tabungan',
            self::Habit => 'Target kebiasaan',
        };
    }
}
