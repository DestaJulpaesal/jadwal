<?php

use App\Support\Rupiah;

if (! function_exists('rupiah')) {
    function rupiah(int $amount): string
    {
        return Rupiah::format($amount);
    }
}
