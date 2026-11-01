<?php

namespace App\Enums;

enum LoanDirection: string
{
    case Lent = 'lent';
    case Borrowed = 'borrowed';

    public function label(): string
    {
        return match ($this) {
            self::Lent => 'Piutang',
            self::Borrowed => 'Utang',
        };
    }
}
