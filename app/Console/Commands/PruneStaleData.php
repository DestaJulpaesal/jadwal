<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class PruneStaleData extends Command
{
    protected $signature = 'app:prune-stale-data';

    protected $description = 'Remove expired password reset tokens and database sessions.';

    public function handle(): int
    {
        $deletedTokens = 0;
        $deletedSessions = 0;
        $deletedFailedJobs = 0;
        $deletedTemporaryFiles = 0;
        $deletedAuditLogs = 0;

        try {
            $resetTable = config('auth.passwords.users.table');
            if (is_string($resetTable) && Schema::hasTable($resetTable)) {
                $deletedTokens = DB::table($resetTable)
                    ->where('created_at', '<', now()->subMinutes((int) config('auth.passwords.users.expire')))
                    ->delete();
            }

            $sessionTable = config('session.table');
            if (config('session.driver') === 'database' && is_string($sessionTable) && Schema::hasTable($sessionTable)) {
                $deletedSessions = DB::table($sessionTable)
                    ->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime'))->timestamp)
                    ->delete();
            }

            if (Schema::hasTable('failed_jobs')) {
                $deletedFailedJobs = DB::table('failed_jobs')
                    ->where('failed_at', '<', now()->subDays(30))
                    ->delete();
            }

            if (Schema::hasTable('admin_audit_logs')) {
                $deletedAuditLogs = DB::table('admin_audit_logs')
                    ->where('created_at', '<', now()->subDays(365))
                    ->delete();
            }

            $temporaryDirectory = storage_path('app/tmp');
            if (is_dir($temporaryDirectory)) {
                $cutoff = now()->subDay()->timestamp;
                foreach (File::allFiles($temporaryDirectory) as $file) {
                    if ($file->isLink() || $file->getMTime() >= $cutoff) {
                        continue;
                    }
                    if (! unlink($file->getPathname())) {
                        throw new RuntimeException('Unable to remove a stale temporary file.');
                    }
                    $deletedTemporaryFiles++;
                }
            }
        } catch (Throwable $exception) {
            Log::error('Stale-data housekeeping failed.', ['exception' => $exception]);
            $this->error('Housekeeping gagal. Periksa log aplikasi untuk detail.');

            return self::FAILURE;
        }

        $this->info(
            "Housekeeping selesai: {$deletedTokens} token reset, {$deletedSessions} sesi, "
            ."{$deletedFailedJobs} failed job, {$deletedAuditLogs} catatan audit, dan "
            ."{$deletedTemporaryFiles} file sementara dihapus."
        );

        return self::SUCCESS;
    }
}
