<?php

namespace App\Support;

use InvalidArgumentException;

class Rupiah
{
    public const MAX_AMOUNT = 999_999_999_999;

    public static function format(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    public static function parse(string|int $value): int
    {
        if (is_int($value)) {
            $amount = $value;
        } elseif (preg_match('/\A-?(?:0|[1-9]\d*|[1-9]\d{0,2}(?:\.\d{3})+)\z/', $value) === 1) {
            $amount = (int) str_replace('.', '', $value);
        } else {
            throw new InvalidArgumentException('Masukkan nominal dalam Rupiah tanpa tanda mata uang.');
        }

        if (abs($amount) > self::MAX_AMOUNT) {
            throw new InvalidArgumentException('Nominal harus antara -Rp 999.999.999.999 dan Rp 999.999.999.999.');
        }

        return $amount;
    }

    public static function normalize(mixed $value): mixed
    {
        if (! is_string($value) && ! is_int($value)) {
            return $value;
        }

        try {
            return self::parse($value);
        } catch (InvalidArgumentException) {
            return $value;
        }
    }
}
