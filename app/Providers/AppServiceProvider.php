<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password as PasswordRule;

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
        $viteHotFile = config('app.vite_hot_file');
        if (is_string($viteHotFile) && $viteHotFile !== '') {
            Vite::useHotFile($viteHotFile);
        }

        if (app()->isProduction() && config('app.debug')) {
            throw new \LogicException('APP_DEBUG must be disabled in production.');
        }

        if (app()->isProduction()) {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);
            $host = is_string($host) ? strtolower(trim($host, '[]')) : '';
            if (in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.localhost')) {
                Log::warning('APP_URL points to a loopback host in production.');
            }

            URL::forceScheme('https');
        }

        PasswordRule::defaults(static fn (): PasswordRule => PasswordRule::min(8)->mixedCase()->numbers());

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
