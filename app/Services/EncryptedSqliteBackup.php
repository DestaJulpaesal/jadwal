<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;
use Throwable;
use ZipArchive;

class EncryptedSqliteBackup
{
    private const MAGIC = 'JDBAK1';

    public function create(): string
    {
        $this->databasePath();
        $backupDirectory = $this->backupDirectory();
        $workDirectory = $this->createWorkDirectory();

        try {
            $snapshotPath = $workDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
            $zipPath = $workDirectory.DIRECTORY_SEPARATOR.'backup.zip';
            $pdo = DB::connection('sqlite')->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($snapshotPath));
            $this->assertIntegrity($snapshotPath);
            $this->createArchive($snapshotPath, $zipPath);

            $archiveSize = filesize($zipPath);
            if ($archiveSize === false || $archiveSize > (int) config('backup.max_archive_bytes')) {
                throw new RuntimeException('The backup archive exceeds BACKUP_MAX_ARCHIVE_BYTES.');
            }

            $archive = file_get_contents($zipPath);
            if ($archive === false) {
                throw new RuntimeException('Unable to read the completed backup archive.');
            }

            $compressed = gzencode($archive, 6);
            if ($compressed === false) {
                throw new RuntimeException('Unable to compress the backup archive.');
            }

            $nonce = random_bytes(12);
            $tag = '';
            $ciphertext = openssl_encrypt(
                $compressed,
                'aes-256-gcm',
                $this->encryptionKey(),
                OPENSSL_RAW_DATA,
                $nonce,
                $tag,
                self::MAGIC
            );
            if ($ciphertext === false || strlen($tag) !== 16) {
                throw new RuntimeException('Unable to encrypt the backup archive.');
            }

            $filename = 'jadwal-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite.gz.enc';
            $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;
            $temporaryPath = $backupPath.'.tmp';
            $bytes = file_put_contents($temporaryPath, self::MAGIC.$nonce.$tag.$ciphertext, LOCK_EX);
            if ($bytes === false || ! rename($temporaryPath, $backupPath)) {
                @unlink($temporaryPath);
                throw new RuntimeException('Unable to save the encrypted backup file.');
            }

            @chmod($backupPath, 0600);
            $this->prune();

            return $backupPath;
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    public function restore(string $inputPath): string
    {
        $backupPath = $this->resolveBackupPath($inputPath);
        $databasePath = $this->databasePath();
        $profilePhotosPath = $this->profilePhotosPath();
        $workDirectory = $this->createWorkDirectory();
        $oldDatabasePath = $databasePath.'.restore-'.bin2hex(random_bytes(8));
        $oldPhotosPath = $profilePhotosPath.'.restore-'.bin2hex(random_bytes(8));
        $databaseMoved = false;
        $photosMoved = false;
        $newDatabaseInstalled = false;
        $newPhotosInstalled = false;
        $rollbackFailures = [];

        try {
            [$archivePath, $databaseStage, $photosStage] = $this->unpackBackup($backupPath, $workDirectory);
            unset($archivePath);
            $this->assertIntegrity($databaseStage);

            $automaticBackup = $this->create();
            DB::purge('sqlite');

            if (! rename($databasePath, $oldDatabasePath)) {
                throw new RuntimeException('Unable to move the current SQLite database before restore.');
            }
            $databaseMoved = true;

            if (is_dir($profilePhotosPath)) {
                if (! rename($profilePhotosPath, $oldPhotosPath)) {
                    throw new RuntimeException('Unable to move current profile photos before restore.');
                }
                $photosMoved = true;
            }

            if (! rename($databaseStage, $databasePath)) {
                throw new RuntimeException('Unable to install the restored SQLite database.');
            }
            $newDatabaseInstalled = true;

            if (! rename($photosStage, $profilePhotosPath)) {
                throw new RuntimeException('Unable to install restored profile photos.');
            }
            $newPhotosInstalled = true;

            DB::purge('sqlite');
            File::delete($oldDatabasePath);
            if ($photosMoved) {
                File::deleteDirectory($oldPhotosPath);
            }

            return $automaticBackup;
        } catch (Throwable $exception) {
            DB::purge('sqlite');

            if ($newPhotosInstalled && is_dir($profilePhotosPath)) {
                if (! File::deleteDirectory($profilePhotosPath)) {
                    $rollbackFailures[] = 'unable to remove restored profile photos';
                }
            }
            if ($photosMoved && is_dir($oldPhotosPath)) {
                if (! rename($oldPhotosPath, $profilePhotosPath)) {
                    $rollbackFailures[] = 'unable to restore the previous profile photos';
                }
            }
            if ($newDatabaseInstalled && is_file($databasePath)) {
                if (! unlink($databasePath)) {
                    $rollbackFailures[] = 'unable to remove the restored database';
                }
            }
            if ($databaseMoved && is_file($oldDatabasePath)) {
                if (! rename($oldDatabasePath, $databasePath)) {
                    $rollbackFailures[] = 'unable to restore the previous database';
                }
            }
            if ($rollbackFailures !== []) {
                throw new RuntimeException(
                    'Restore failed and rollback requires operator intervention: '.implode('; ', $rollbackFailures),
                    previous: $exception
                );
            }

            throw $exception;
        } finally {
            File::deleteDirectory($workDirectory);
        }
    }

    private function createArchive(string $databasePath, string $archivePath): void
    {
        $archive = new ZipArchive;
        if ($archive->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the backup archive.');
        }

        if (! $archive->addFile($databasePath, 'database.sqlite')) {
            $archive->close();
            throw new RuntimeException('Unable to add the SQLite snapshot to the backup archive.');
        }

        $photosPath = $this->profilePhotosPath();
        if (is_dir($photosPath)) {
            foreach (File::allFiles($photosPath) as $file) {
                if ($file->isLink()) {
                    $archive->close();
                    throw new RuntimeException('Profile photo backups do not support symbolic links.');
                }

                $relativePath = ltrim(
                    str_replace('\\', '/', substr($file->getPathname(), strlen($photosPath))),
                    '/'
                );
                if ($relativePath === '' || ! $this->isSafeArchivePath('profile-photos/'.$relativePath)) {
                    $archive->close();
                    throw new RuntimeException('A profile photo path cannot be safely archived.');
                }

                if (! $archive->addFile($file->getPathname(), 'profile-photos/'.$relativePath)) {
                    $archive->close();
                    throw new RuntimeException('Unable to add a profile photo to the backup archive.');
                }
            }
        }

        if (! $archive->close()) {
            throw new RuntimeException('Unable to finish the backup archive.');
        }
    }

    /**
     * @return array{string, string, string}
     */
    private function unpackBackup(string $backupPath, string $workDirectory): array
    {
        $backupSize = filesize($backupPath);
        $maxArchiveBytes = (int) config('backup.max_archive_bytes');
        if ($backupSize === false || $backupSize > $maxArchiveBytes + 128) {
            throw new RuntimeException('The encrypted backup exceeds BACKUP_MAX_ARCHIVE_BYTES.');
        }

        $contents = file_get_contents($backupPath);
        if ($contents === false || strlen($contents) < strlen(self::MAGIC) + 12 + 16 + 1) {
            throw new RuntimeException('The backup file is empty or incomplete.');
        }

        $headerLength = strlen(self::MAGIC);
        if (substr($contents, 0, $headerLength) !== self::MAGIC) {
            throw new RuntimeException('The backup file format is not supported.');
        }

        $nonce = substr($contents, $headerLength, 12);
        $tag = substr($contents, $headerLength + 12, 16);
        $ciphertext = substr($contents, $headerLength + 28);
        $compressed = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::MAGIC
        );
        if ($compressed === false) {
            throw new RuntimeException('The backup could not be authenticated or decrypted.');
        }

        $archiveBytes = gzdecode($compressed, $maxArchiveBytes);
        if ($archiveBytes === false || strlen($archiveBytes) > $maxArchiveBytes) {
            throw new RuntimeException('The backup archive is damaged or could not be decompressed.');
        }

        $archivePath = $workDirectory.DIRECTORY_SEPARATOR.'restore.zip';
        if (file_put_contents($archivePath, $archiveBytes, LOCK_EX) === false) {
            throw new RuntimeException('Unable to stage the decrypted backup archive.');
        }

        $archive = new ZipArchive;
        if ($archive->open($archivePath) !== true) {
            throw new RuntimeException('The decrypted backup archive is invalid.');
        }

        $databaseStage = $workDirectory.DIRECTORY_SEPARATOR.'restored.sqlite';
        $photosStage = $workDirectory.DIRECTORY_SEPARATOR.'profile-photos';
        if (! mkdir($photosStage, 0700, true)) {
            $archive->close();
            throw new RuntimeException('Unable to stage restored profile photos.');
        }

        $databaseFound = false;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);
            if (! is_string($name) || ! $this->isSafeArchivePath($name)) {
                $archive->close();
                throw new RuntimeException('The backup contains an unsafe archive path.');
            }
            $operatingSystem = 0;
            $attributes = 0;
            if ($archive->getExternalAttributesIndex($index, $operatingSystem, $attributes)
                && $operatingSystem === ZipArchive::OPSYS_UNIX
                && (($attributes >> 16) & 0170000) === 0120000) {
                $archive->close();
                throw new RuntimeException('The backup contains an unsupported symbolic link.');
            }

            if ($name === 'database.sqlite') {
                if ($databaseFound || ! $this->copyArchiveEntry($archive, $name, $databaseStage)) {
                    $archive->close();
                    throw new RuntimeException('The backup contains an invalid SQLite snapshot.');
                }
                $databaseFound = true;
                continue;
            }

            if (! str_starts_with($name, 'profile-photos/')) {
                $archive->close();
                throw new RuntimeException('The backup contains an unexpected archive entry.');
            }

            if (str_ends_with($name, '/')) {
                continue;
            }

            $relativePath = substr($name, strlen('profile-photos/'));
            if ($relativePath === '') {
                $archive->close();
                throw new RuntimeException('The backup contains an invalid profile photo path.');
            }

            $destination = $photosStage.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $destinationDirectory = dirname($destination);
            if (! is_dir($destinationDirectory) && ! mkdir($destinationDirectory, 0700, true) && ! is_dir($destinationDirectory)) {
                $archive->close();
                throw new RuntimeException('Unable to create a restored profile photo directory.');
            }
            if (! $this->copyArchiveEntry($archive, $name, $destination)) {
                $archive->close();
                throw new RuntimeException('Unable to restore a profile photo from the backup.');
            }
            @chmod($destination, 0600);
        }

        if (! $archive->close() || ! $databaseFound) {
            throw new RuntimeException('The backup does not contain a complete SQLite snapshot.');
        }

        return [$archivePath, $databaseStage, $photosStage];
    }

    private function copyArchiveEntry(ZipArchive $archive, string $name, string $destination): bool
    {
        $input = $archive->getStream($name);
        if ($input === false) {
            return false;
        }

        $output = fopen($destination, 'wb');
        if ($output === false) {
            fclose($input);
            return false;
        }

        $copied = stream_copy_to_stream($input, $output);
        fclose($input);
        fclose($output);

        return $copied !== false;
    }

    private function isSafeArchivePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0") || str_contains($path, '\\') || str_starts_with($path, '/')) {
            return false;
        }

        foreach (explode('/', rtrim($path, '/')) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }

    private function assertIntegrity(string $databasePath): void
    {
        $pdo = new PDO('sqlite:'.$databasePath);
        $result = $pdo->query('PRAGMA integrity_check')?->fetchColumn();
        if ($result !== 'ok') {
            throw new RuntimeException('SQLite integrity check failed for the backup snapshot.');
        }

        $hasUsersTable = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'users'")?->fetchColumn();
        if ($hasUsersTable === false) {
            throw new RuntimeException('The backup does not contain the application users table.');
        }
    }

    private function resolveBackupPath(string $inputPath): string
    {
        $directory = realpath($this->backupDirectory());
        $candidate = basename($inputPath) === $inputPath
            ? $this->backupDirectory().DIRECTORY_SEPARATOR.$inputPath
            : $inputPath;
        $path = realpath($candidate);

        if ($directory === false || $path === false || dirname($path) !== $directory || ! is_file($path)) {
            throw new RuntimeException('The backup file must be inside the configured backup directory.');
        }

        return $path;
    }

    private function databasePath(): string
    {
        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException('Encrypted backups and restore are available only for SQLite.');
        }

        $path = config('database.connections.sqlite.database');
        if (! is_string($path) || $path === ':memory:' || ! is_file($path)) {
            throw new RuntimeException('The configured SQLite database file is unavailable.');
        }

        return $path;
    }

    private function backupDirectory(): string
    {
        $path = config('backup.directory');
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('BACKUP_DIRECTORY must be configured.');
        }

        if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
            throw new RuntimeException('Unable to create the backup directory.');
        }

        @chmod($path, 0700);

        return $path;
    }

    private function profilePhotosPath(): string
    {
        $path = config('backup.profile_photos_directory');
        if (! is_string($path) || $path === '') {
            throw new RuntimeException('A profile photo storage directory must be configured.');
        }

        return $path;
    }

    private function encryptionKey(): string
    {
        $encodedKey = config('backup.encryption_key');
        $key = is_string($encodedKey) ? base64_decode($encodedKey, true) : false;

        if (! is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY must be a base64-encoded 32-byte key.');
        }

        return $key;
    }

    private function createWorkDirectory(): string
    {
        $directory = storage_path('framework/backup-'.bin2hex(random_bytes(12)));
        if (! mkdir($directory, 0700, true)) {
            throw new RuntimeException('Unable to create a temporary backup workspace.');
        }

        return $directory;
    }

    private function prune(): void
    {
        $backups = [];
        foreach (File::files($this->backupDirectory()) as $file) {
            if (preg_match('/^jadwal-(\d{8})-(\d{6})-[a-f0-9]{8}\.sqlite\.gz\.enc$/', $file->getFilename(), $matches) !== 1) {
                continue;
            }

            $date = \Illuminate\Support\Carbon::createFromFormat('Ymd-His', $matches[1].'-'.$matches[2]);
            if ($date !== false) {
                $backups[] = ['path' => $file->getPathname(), 'date' => $date];
            }
        }

        usort($backups, static fn (array $left, array $right): int => $right['date']->getTimestamp() <=> $left['date']->getTimestamp());
        $dailyCutoff = now()->startOfDay()->subDays(13);
        $weeklyCutoff = now()->startOfWeek()->subWeeks(7);
        $keptWeeks = [];

        foreach ($backups as $backup) {
            $date = $backup['date'];
            $keep = $date->greaterThanOrEqualTo($dailyCutoff);

            if (! $keep && $date->greaterThanOrEqualTo($weeklyCutoff)) {
                $week = $date->format('o-W');
                $keep = ! isset($keptWeeks[$week]);
                $keptWeeks[$week] = true;
            }

            if (! $keep && ! unlink($backup['path'])) {
                throw new RuntimeException('Unable to remove an expired encrypted backup.');
            }
        }
    }
}
