<?php

namespace App\Console\Commands;

use App\Services\EncryptedSqliteBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database';

    protected $description = 'Create an encrypted SQLite and profile photo backup.';

    public function handle(EncryptedSqliteBackup $backup): int
    {
        try {
            $path = $backup->create();
        } catch (Throwable $exception) {
            Log::critical('The scheduled SQLite backup failed.', ['exception' => $exception]);
            $this->error('Backup gagal. Periksa log aplikasi untuk detail.');

            return self::FAILURE;
        }

        $this->info('Backup terenkripsi berhasil dibuat: '.$path);

        return self::SUCCESS;
    }
}
