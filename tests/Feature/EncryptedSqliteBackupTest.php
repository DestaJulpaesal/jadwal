<?php

namespace Tests\Feature;

use App\Services\EncryptedSqliteBackup;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class EncryptedSqliteBackupTest extends TestCase
{
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
            'backup.encryption_key' => base64_encode(random_bytes(32)),
        ]);
        DB::purge('sqlite');

        try {
            $backup = app(EncryptedSqliteBackup::class);
            $pdo = DB::connection('sqlite')->getPdo();
            $this->assertSame('wal', strtolower((string) $pdo->query('PRAGMA journal_mode')->fetchColumn()));
            $this->assertSame(5000, (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn());
            $backupPath = $backup->create();
            $this->assertFileExists($backupPath);
            $this->assertStringStartsWith('JDBAK1', (string) file_get_contents($backupPath));

            DB::connection('sqlite')->table('backup_test_records')->update(['value' => 'after']);
            file_put_contents($photosDirectory.DIRECTORY_SEPARATOR.'avatar.png', 'photo-after');

            $automaticBackup = $backup->restore(basename($backupPath));
            $this->assertFileExists($automaticBackup);
            $this->assertSame(
                'before',
                DB::connection('sqlite')->table('backup_test_records')->value('value')
            );
            $this->assertSame(
                'photo-before',
                file_get_contents($photosDirectory.DIRECTORY_SEPARATOR.'avatar.png')
            );
        } finally {
            DB::purge('sqlite');
            File::deleteDirectory($testDirectory);
        }
    }
}
