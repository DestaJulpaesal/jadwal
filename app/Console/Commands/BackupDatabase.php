<?php

namespace App\Console\Commands;

use App\Services\EncryptedSqliteBackup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;
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

        if (! $this->copyToS3($path)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function copyToS3(string $path): bool
    {
        $bucket = config('backup.offsite_s3_bucket');
        if (! is_string($bucket) || $bucket === '') {
            return true;
        }

        $prefix = trim((string) config('backup.offsite_s3_prefix'), '/');
        $destination = 's3://'.trim($bucket, '/').($prefix !== '' ? '/'.$prefix : '').'/'.basename($path);

        try {
            $statusFile = config('backup.offsite_status_file');
            if (! is_string($statusFile) || $statusFile === '') {
                throw new RuntimeException('BACKUP_OFFSITE_STATUS_FILE must be configured when S3 offsite backup is enabled.');
            }

            $process = new Process(['aws', 's3', 'cp', $path, $destination, '--only-show-errors']);
            $process->setTimeout(600);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException('AWS CLI exited with code '.$process->getExitCode().'.');
            }

            $statusDirectory = dirname($statusFile);
            if (! is_dir($statusDirectory) && ! mkdir($statusDirectory, 0700, true) && ! is_dir($statusDirectory)) {
                throw new RuntimeException('Unable to create the offsite status directory.');
            }
            $statusJson = json_encode([
                'filename' => basename($path),
                'copied_at' => now()->toIso8601String(),
                'destination' => $destination,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            if ($statusJson === false || file_put_contents($statusFile, $statusJson, LOCK_EX) === false) {
                throw new RuntimeException('The S3 backup status file could not be saved.');
            }
        } catch (Throwable $exception) {
            Log::critical('The encrypted SQLite backup could not be copied to S3.', [
                'filename' => basename($path),
                'exception_class' => $exception::class,
            ]);
            $this->error('Backup lokal berhasil, tetapi penyalinan/status S3 gagal.');

            return false;
        }

        $this->info('Cadangan terenkripsi juga disalin ke S3: '.$destination);

        return true;
    }
}
