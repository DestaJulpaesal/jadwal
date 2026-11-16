<?php

namespace App\Enums;

enum GoalDirection: string
{
    case Increase = 'increase';
    case Decrease = 'decrease';

    public function label(): string
    {
        return match ($this) {
            self::Increase => 'Meningkatkan',
            self::Decrease => 'Menurunkan',
        };
    }
}
