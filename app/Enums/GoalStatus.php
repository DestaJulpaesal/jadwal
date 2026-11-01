<?php

namespace App\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Achieved = 'achieved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Berjalan',
            self::Achieved => 'Tercapai',
            self::Archived => 'Diarsipkan',
        };
    }
}
