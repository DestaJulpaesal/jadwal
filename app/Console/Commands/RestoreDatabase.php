<?php

namespace App\Console\Commands;

use App\Services\EncryptedSqliteBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RestoreDatabase extends Command
{
    protected $signature = 'app:restore-database {file} {--force : Skip the interactive confirmation}';

    protected $description = 'Restore a verified encrypted SQLite backup.';

    public function handle(EncryptedSqliteBackup $backup): int
    {
        if (! app()->isDownForMaintenance()) {
            $this->error('Aktifkan maintenance mode dan hentikan worker sebelum restore.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Database dan foto profil aktif akan diganti. Lanjutkan?')) {
            return self::FAILURE;
        }

        try {
            $automaticBackup = $backup->restore((string) $this->argument('file'));
        } catch (Throwable $exception) {
            Log::critical('The SQLite restore failed.', ['exception' => $exception]);
            $this->error('Restore gagal. Periksa log aplikasi; data lama dipertahankan bila penggantian tidak selesai.');

            return self::FAILURE;
        }

        $this->info('Restore selesai. Backup otomatis sebelum restore: '.$automaticBackup);

        return self::SUCCESS;
    }
}
