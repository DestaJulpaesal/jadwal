<?php

namespace Tests\Feature;

use App\Services\EncryptedSqliteBackup;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDO;
use Tests\TestCase;

class EncryptedSqliteBackupTest extends TestCase
{
    public function test_backup_status_counts_only_complete_archives_and_reports_the_latest(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jadwal-backup-status-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($directory);

        try {
            $older = $directory.DIRECTORY_SEPARATOR.'jadwal-20261116-023000-01234567.sqlite.gz.enc';
            $latest = $directory.DIRECTORY_SEPARATOR.'jadwal-20261117-023000-89abcdef.sqlite.gz.enc';
            file_put_contents($older, 'old');
            file_put_contents($latest, 'newest');
            file_put_contents($directory.DIRECTORY_SEPARATOR.'unfinished.tmp', 'ignored');
            file_put_contents($directory.DIRECTORY_SEPARATOR.'notes.txt', 'ignored');
            touch($older, strtotime('2026-11-16 02:30:00 UTC'));
            touch($latest, strtotime('2026-11-17 02:30:00 UTC'));
            config(['backup.directory' => $directory]);

            $status = app(EncryptedSqliteBackup::class)->status();

            $this->assertSame(2, $status['count']);
            $this->assertSame(9, $status['bytes']);
            $this->assertSame(basename($latest), $status['latest']);
            $this->assertSame(strtotime('2026-11-17 02:30:00 UTC'), $status['latest_modified_at']);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_backup_status_does_not_create_a_missing_directory(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jadwal-backup-status-missing-'.bin2hex(random_bytes(6));
        config(['backup.directory' => $directory]);

        $status = app(EncryptedSqliteBackup::class)->status();

        $this->assertSame(0, $status['count']);
        $this->assertNull($status['latest']);
        $this->assertDirectoryDoesNotExist($directory);
    }

    public function test_backup_status_logs_critical_when_the_latest_backup_is_stale(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jadwal-backup-status-stale-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($directory);
        $latest = $directory.DIRECTORY_SEPARATOR.'jadwal-20261117-023000-01234567.sqlite.gz.enc';
        file_put_contents($latest, 'stale');
        $this->travelTo(Carbon::parse('2026-11-18 20:00:00', 'UTC'));
        touch($latest, now()->subHours(37)->timestamp);
        config(['backup.directory' => $directory]);
        Log::spy();

        try {
            $exitCode = Artisan::call('app:backup-status');
            $output = Artisan::output();

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Backup count: 1', $output);
            $this->assertStringContainsString('Total size: 5 bytes', $output);
            $this->assertStringContainsString('Latest backup: '.basename($latest), $output);
            $this->assertStringContainsString('Latest backup age: 37h 0m', $output);
            Log::shouldHaveReceived('critical')->once();
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_backup_status_fails_when_the_latest_local_backup_has_not_reached_s3(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jadwal-backup-status-s3-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($directory);
        $older = 'jadwal-'.now()->subMinute()->format('Ymd-His').'-01234567.sqlite.gz.enc';
        $latest = 'jadwal-'.now()->format('Ymd-His').'-89abcdef.sqlite.gz.enc';
        $statusPath = $directory.DIRECTORY_SEPARATOR.'.s3-status.json';
        file_put_contents($directory.DIRECTORY_SEPARATOR.$older, 'old');
        file_put_contents($directory.DIRECTORY_SEPARATOR.$latest, 'new');
        touch($directory.DIRECTORY_SEPARATOR.$older, now()->subMinute()->timestamp);
        touch($directory.DIRECTORY_SEPARATOR.$latest, now()->timestamp);
        file_put_contents($statusPath, json_encode([
            'filename' => $older,
            'copied_at' => now()->toIso8601String(),
        ], JSON_THROW_ON_ERROR));
        config([
            'backup.directory' => $directory,
            'backup.offsite_s3_bucket' => 'private-test-bucket',
            'backup.offsite_status_file' => $statusPath,
        ]);
        Log::spy();

        try {
            $exitCode = Artisan::call('app:backup-status');

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('Cadangan lokal terbaru belum cocok dengan salinan S3.', Artisan::output());
            Log::shouldHaveReceived('critical')->once();
        } finally {
            File::deleteDirectory($directory);
        }
    }

    public function test_backup_and_restore_preserve_sqlite_data_and_profile_photos(): void
    {
        $testDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jadwal-backup-test-'.bin2hex(random_bytes(6));
        $databasePath = $testDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
        $backupDirectory = $testDirectory.DIRECTORY_SEPARATOR.'backups';
        $photosDirectory = $testDirectory.DIRECTORY_SEPARATOR.'profile-photos';
        File::ensureDirectoryExists($testDirectory);
        File::ensureDirectoryExists($photosDirectory);

        $pdo = new PDO('sqlite:'.$databasePath);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        $pdo->exec('CREATE TABLE migrations (id INTEGER PRIMARY KEY)');
        $pdo->exec('CREATE TABLE backup_test_records (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        $pdo->exec("INSERT INTO backup_test_records (value) VALUES ('before')");
        file_put_contents($photosDirectory.DIRECTORY_SEPARATOR.'avatar.png', 'photo-before');
        $pdo = null;

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $databasePath,
            'backup.directory' => $backupDirectory,
            'backup.profile_photos_directory' => $photosDirectory,
            'backup.encryption_key' => $encryptionKey = base64_encode(random_bytes(32)),
        ]);
        DB::purge('sqlite');

        try {
            $backup = app(EncryptedSqliteBackup::class);
            $pdo = DB::connection('sqlite')->getPdo();
            $this->assertSame('wal', strtolower((string) $pdo->query('PRAGMA journal_mode')->fetchColumn()));
            $this->assertSame(5000, (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn());
            $pdo = null;
            $backupPath = $backup->create();
            $this->assertFileExists($backupPath);
            $this->assertStringStartsWith('JDBAK2', (string) file_get_contents($backupPath));

            DB::connection('sqlite')->table('backup_test_records')->update(['value' => 'after']);
            file_put_contents($photosDirectory.DIRECTORY_SEPARATOR.'avatar.png', 'photo-after');
            $this->assertFileExists($databasePath.'-wal');
            $this->assertFileExists($databasePath.'-shm');

            $automaticBackup = $backup->restore(basename($backupPath));
            $this->assertFileExists($automaticBackup);
            $this->assertFileDoesNotExist($databasePath.'-wal');
            $this->assertFileDoesNotExist($databasePath.'-shm');
            $this->assertSame(
                'before',
                DB::connection('sqlite')->table('backup_test_records')->value('value')
            );
            $this->assertSame(
                'photo-before',
                file_get_contents($photosDirectory.DIRECTORY_SEPARATOR.'avatar.png')
            );

            $encryptedBackup = file_get_contents($backupPath);
            $lastByte = strlen($encryptedBackup) - 1;
            $encryptedBackup[$lastByte] = chr(ord($encryptedBackup[$lastByte]) ^ 1);
            file_put_contents($backupPath, $encryptedBackup);

            try {
                $backup->restore(basename($backupPath));
                $this->fail('A modified backup must not be restored.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('authenticated or decrypted', $exception->getMessage());
            }
            $this->assertSame(
                'before',
                DB::connection('sqlite')->table('backup_test_records')->value('value')
            );
        } finally {
            DB::purge('sqlite');
            File::deleteDirectory($testDirectory);
        }
    }

    public function test_legacy_jdbak1_backup_can_still_be_restored(): void
    {
        $testDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'jadwal-backup-legacy-test-'.bin2hex(random_bytes(6));
        $databasePath = $testDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
        $backupDirectory = $testDirectory.DIRECTORY_SEPARATOR.'backups';
        $photosDirectory = $testDirectory.DIRECTORY_SEPARATOR.'profile-photos';
        $zipPath = $testDirectory.DIRECTORY_SEPARATOR.'legacy.zip';
        File::ensureDirectoryExists($testDirectory);
        File::ensureDirectoryExists($photosDirectory);

        $pdo = new PDO('sqlite:'.$databasePath);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY)');
        $pdo->exec('CREATE TABLE migrations (id INTEGER PRIMARY KEY)');
        $pdo->exec('CREATE TABLE backup_test_records (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
        $pdo->exec("INSERT INTO backup_test_records (value) VALUES ('before')");
        $pdo = null;

        $archive = new \ZipArchive;
        $archive->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $archive->addFile($databasePath, 'database.sqlite');
        $archive->close();
        $compressed = gzencode((string) file_get_contents($zipPath), 6);
        $nonce = random_bytes(12);
        $tag = '';
        $encryptionKey = base64_encode(random_bytes(32));
        $ciphertext = openssl_encrypt(
            $compressed,
            'aes-256-gcm',
            base64_decode($encryptionKey, true),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            'JDBAK1'
        );
        $legacyPath = $backupDirectory.DIRECTORY_SEPARATOR.'jadwal-'.now()->format('Ymd-His').'-01234567.sqlite.gz.enc';
        File::ensureDirectoryExists($backupDirectory);
        file_put_contents($legacyPath, 'JDBAK1'.$nonce.$tag.$ciphertext);
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => $databasePath,
            'backup.directory' => $backupDirectory,
            'backup.profile_photos_directory' => $photosDirectory,
            'backup.encryption_key' => $encryptionKey,
        ]);
        DB::purge('sqlite');

        try {
            DB::connection('sqlite')->table('backup_test_records')->update(['value' => 'after']);

            app(EncryptedSqliteBackup::class)->restore(basename($legacyPath));

            $this->assertSame(
                'before',
                DB::connection('sqlite')->table('backup_test_records')->value('value')
            );
        } finally {
            DB::purge('sqlite');
            File::deleteDirectory($testDirectory);
        }
    }
}
