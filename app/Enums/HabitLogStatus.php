<?php

namespace App\Enums;

enum HabitLogStatus: string
{
    case Done = 'done';
    case Skipped = 'skipped';

    public function label(): string
    {
        return match ($this) {
            self::Done => 'Selesai',
            self::Skipped => 'Libur hari ini',
        };
    }
}
