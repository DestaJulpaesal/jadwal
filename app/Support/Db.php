<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB as Database;

class Db
{
    /**
     * Run a callback inside an IMMEDIATE transaction.
     *
     * Since Laravel 13 + PHP 8.4, the sqlite connection's transaction_mode
     * config handles BEGIN IMMEDIATE automatically for all DB::transaction
     * calls, so this method now delegates directly. It is kept as a
     * semantic alias that documents write-intent at the call site.
     */
    public static function immediate(callable $callback): mixed
    {
        return Database::connection()->transaction(Closure::fromCallable($callback));
    }
}
