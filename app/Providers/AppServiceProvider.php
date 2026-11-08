<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Carbon::setLocale('id');
        Model::preventLazyLoading(! app()->isProduction());
        Event::listen(ConnectionEstablished::class, static function (ConnectionEstablished $event): void {
            if ($event->connection->getDriverName() !== 'sqlite') {
                return;
            }

            $journalMode = strtoupper((string) config('database.connections.sqlite.journal_mode', 'WAL'));
            $synchronous = strtoupper((string) config('database.connections.sqlite.synchronous', 'NORMAL'));
            $busyTimeout = (int) config('database.connections.sqlite.busy_timeout', 5000);
            if (! in_array($journalMode, ['DELETE', 'TRUNCATE', 'PERSIST', 'MEMORY', 'WAL', 'OFF'], true)) {
                throw new \InvalidArgumentException('SQLite journal mode is invalid.');
            }
            if (! in_array($synchronous, ['OFF', 'NORMAL', 'FULL', 'EXTRA'], true) || $busyTimeout < 0) {
                throw new \InvalidArgumentException('SQLite connection settings are invalid.');
            }

            $pdo = $event->connection->getPdo();
            $pdo->exec('PRAGMA journal_mode='.$journalMode);
            $pdo->exec('PRAGMA busy_timeout='.$busyTimeout);
            $pdo->exec('PRAGMA synchronous='.$synchronous);
        });
    }
}
