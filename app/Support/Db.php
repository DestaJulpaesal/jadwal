<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB as Database;
use Throwable;

class Db
{
    public static function immediate(callable $callback): mixed
    {
        $callback = Closure::fromCallable($callback);
        $connection = Database::connection();

        if ($connection->getDriverName() !== 'sqlite' || $connection->transactionLevel() > 0) {
            return $connection->transaction($callback);
        }

        $connection->unprepared('BEGIN IMMEDIATE');

        try {
            $result = $callback();
            $connection->unprepared('COMMIT');

            return $result;
        } catch (Throwable $exception) {
            $connection->unprepared('ROLLBACK');

            throw $exception;
        }
    }
}
