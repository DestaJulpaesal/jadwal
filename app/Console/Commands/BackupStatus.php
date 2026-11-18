<?php

namespace App\Console\Commands;

use App\Services\EncryptedSqliteBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupStatus extends Command
{
    protected $signature = 'app:backup-status';

    protected $description = 'Report the age, count, and total size of encrypted backups.';

    public function handle(EncryptedSqliteBackup $backup): int
    {
        try {
            $status = $backup->status();
        } catch (Throwable $exception) {
            Log::critical('Unable to inspect encrypted SQLite backups.', [
                'exception_class' => $exception::class,
            ]);
            $this->error('Unable to inspect backups. Check the application log.');

            return self::FAILURE;
        }

        $this->line('Backup count: '.$status['count']);
        $this->line('Total size: '.$status['bytes'].' bytes');

        if ($status['latest'] === null || $status['age_seconds'] === null) {
            Log::critical('No encrypted SQLite backups are available.');
            $this->error('No encrypted SQLite backups are available.');

            return self::FAILURE;
        }

        $hours = intdiv($status['age_seconds'], 3600);
        $minutes = intdiv($status['age_seconds'] % 3600, 60);
        $this->line('Latest backup: '.$status['latest']);
        $this->line("Latest backup age: {$hours}h {$minutes}m");

        if ($status['age_seconds'] > 36 * 60 * 60) {
            Log::critical('The latest SQLite backup is older than 36 hours.', [
                'filename' => $status['latest'],
                'age_seconds' => $status['age_seconds'],
            ]);
            $this->error('The latest backup is older than 36 hours.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
