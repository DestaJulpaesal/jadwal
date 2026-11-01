<?php

namespace App\Enums;

enum WalletType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Ewallet = 'ewallet';
    case Savings = 'savings';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Bank => 'Bank',
            self::Ewallet => 'Dompet digital',
            self::Savings => 'Tabungan',
            self::Other => 'Lainnya',
        };
    }
}
