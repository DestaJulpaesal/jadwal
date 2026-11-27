<?php

namespace App\Console\Commands;

use App\Services\EncryptedSqliteBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
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

        $bucket = config('backup.offsite_s3_bucket');
        if (is_string($bucket) && $bucket !== '') {
            $statusFile = config('backup.offsite_status_file');
            if (! is_string($statusFile) || $statusFile === '') {
                Log::critical('S3 offsite backup is configured but no status file is configured.');
                $this->error('Konfigurasi status cadangan S3 tidak lengkap.');

                return self::FAILURE;
            }

            if (! is_file($statusFile)) {
                Log::critical('S3 offsite backup status is missing.');
                $this->error('Belum ada status salinan cadangan S3.');

                return self::FAILURE;
            }

            $decoded = json_decode((string) file_get_contents($statusFile), true);
            if (! is_array($decoded) || ! isset($decoded['copied_at'], $decoded['filename'])) {
                throw new RuntimeException('S3 offsite backup status is invalid.');
            }

            if ($decoded['filename'] !== $status['latest']) {
                Log::critical('The latest local backup has not been copied to S3.', [
                    'local_filename' => $status['latest'],
                    'offsite_filename' => $decoded['filename'],
                ]);
                $this->error('Cadangan lokal terbaru belum cocok dengan salinan S3.');

                return self::FAILURE;
            }

            $copiedAt = strtotime((string) $decoded['copied_at']);
            if ($copiedAt === false || time() - $copiedAt > 36 * 60 * 60) {
                Log::critical('The latest S3 offsite backup status is stale.', [
                    'filename' => $decoded['filename'],
                ]);
                $this->error('Salinan cadangan S3 terbaru sudah lebih lama dari 36 jam.');

                return self::FAILURE;
            }

            $this->line('Latest S3 offsite backup: '.$decoded['filename']);
        }

        return self::SUCCESS;
    }
}
