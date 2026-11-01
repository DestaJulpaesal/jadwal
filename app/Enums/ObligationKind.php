<?php

namespace App\Enums;

enum ObligationKind: string
{
    case Bill = 'bill';
    case Installment = 'installment';

    public function label(): string
    {
        return match ($this) {
            self::Bill => 'Tagihan',
            self::Installment => 'Cicilan',
        };
    }
}
