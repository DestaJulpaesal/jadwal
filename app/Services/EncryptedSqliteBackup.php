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
    private const LEGACY_MAGIC = 'JDBAK1';

    private const MAGIC = 'JDBAK2';

    private const CHUNK_BYTES = 1_048_576;

    private const TAG_BYTES = 16;

    private const SALT_BYTES = 16;

    private const V2_HEADER_BYTES = 6 + self::SALT_BYTES + 8;

    public function create(): string
    {
        $this->databasePath();
        $backupDirectory = $this->backupDirectory();
        $workDirectory = $this->createWorkDirectory();

        try {
            $snapshotPath = $workDirectory.DIRECTORY_SEPARATOR.'database.sqlite';
            $zipPath = $workDirectory.DIRECTORY_SEPARATOR.'backup.zip';
            $compressedPath = $workDirectory.DIRECTORY_SEPARATOR.'backup.gz';
            $pdo = DB::connection('sqlite')->getPdo();
            $pdo->exec('VACUUM INTO '.$pdo->quote($snapshotPath));
            $this->assertIntegrity($snapshotPath);
            $this->createArchive($snapshotPath, $zipPath);

            $archiveSize = filesize($zipPath);
            if ($archiveSize === false || $archiveSize > (int) config('backup.max_archive_bytes')) {
                throw new RuntimeException('The backup archive exceeds BACKUP_MAX_ARCHIVE_BYTES.');
            }

            $this->compressArchive($zipPath, $compressedPath);

            $filename = 'jadwal-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite.gz.enc';
            $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.$filename;
            $temporaryPath = $backupPath.'.'.bin2hex(random_bytes(4)).'.tmp';
            try {
                $this->encryptStream($compressedPath, $temporaryPath);
                if (! rename($temporaryPath, $backupPath)) {
                    throw new RuntimeException('Unable to save the encrypted backup file.');
                }
            } catch (Throwable $exception) {
                if (is_file($temporaryPath) && ! unlink($temporaryPath)) {
                    throw new RuntimeException('Unable to clean up an incomplete encrypted backup.', previous: $exception);
                }

                throw $exception;
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
            $this->checkpointSqliteWal();
            DB::purge('sqlite');
            $this->removeSqliteSidecars($databasePath);

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
            $this->assertNoSqliteSidecars($databasePath);
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

    /**
     * @return array{count: int, bytes: int, latest: ?string, latest_modified_at: ?int, age_seconds: ?int}
     */
    public function status(): array
    {
        $directory = config('backup.directory');
        if (! is_string($directory) || $directory === '') {
            throw new RuntimeException('BACKUP_DIRECTORY must be configured.');
        }

        if (! is_dir($directory)) {
            return [
                'count' => 0,
                'bytes' => 0,
                'latest' => null,
                'latest_modified_at' => null,
                'age_seconds' => null,
            ];
        }

        $backups = [];
        foreach (File::files($directory) as $file) {
            if ($file->isLink()
                || ! preg_match('/\Ajadwal-\d{8}-\d{6}-[a-f0-9]{8}\.sqlite\.gz\.enc\z/i', $file->getFilename())) {
                continue;
            }

            $backups[] = [
                'name' => $file->getFilename(),
                'bytes' => $file->getSize(),
                'modified_at' => $file->getMTime(),
            ];
        }

        usort($backups, fn (array $left, array $right) => $right['modified_at'] <=> $left['modified_at']);
        $latest = $backups[0] ?? null;
        $modifiedAt = $latest['modified_at'] ?? null;

        return [
            'count' => count($backups),
            'bytes' => array_sum(array_column($backups, 'bytes')),
            'latest' => $latest['name'] ?? null,
            'latest_modified_at' => $modifiedAt,
            'age_seconds' => $modifiedAt === null ? null : max(0, now()->timestamp - $modifiedAt),
        ];
    }

    private function checkpointSqliteWal(): void
    {
        $checkpoint = DB::connection('sqlite')->selectOne('PRAGMA wal_checkpoint(TRUNCATE)');
        if (! $checkpoint
            || ! isset($checkpoint->busy, $checkpoint->log, $checkpoint->checkpointed)
            || (int) $checkpoint->busy !== 0
            || ((int) $checkpoint->log >= 0 && (int) $checkpoint->checkpointed < (int) $checkpoint->log)) {
            throw new RuntimeException('Unable to checkpoint the SQLite WAL before restore.');
        }
    }

    private function removeSqliteSidecars(string $databasePath): void
    {
        foreach ([$databasePath.'-wal', $databasePath.'-shm'] as $sidecarPath) {
            if (is_link($sidecarPath) || (file_exists($sidecarPath) && ! is_file($sidecarPath))) {
                throw new RuntimeException('SQLite restore sidecars must be regular files.');
            }

            if (is_file($sidecarPath) && ! unlink($sidecarPath)) {
                throw new RuntimeException('Unable to remove a SQLite restore sidecar.');
            }
        }
    }

    private function assertNoSqliteSidecars(string $databasePath): void
    {
        foreach ([$databasePath.'-wal', $databasePath.'-shm'] as $sidecarPath) {
            if (file_exists($sidecarPath) || is_link($sidecarPath)) {
                throw new RuntimeException('Unexpected SQLite WAL or SHM sidecar exists after restore.');
            }
        }
    }

    private function compressArchive(string $archivePath, string $compressedPath): void
    {
        $input = fopen($archivePath, 'rb');
        if ($input === false) {
            throw new RuntimeException('Unable to read the completed backup archive.');
        }

        $gzip = gzopen($compressedPath, 'wb6');
        if ($gzip === false) {
            fclose($input);
            throw new RuntimeException('Unable to create the compressed backup stream.');
        }

        try {
            while (! feof($input)) {
                $chunk = fread($input, self::CHUNK_BYTES);
                if ($chunk === false || ($chunk === '' && ! feof($input))) {
                    throw new RuntimeException('Unable to read the backup archive stream.');
                }
                if ($chunk === '') {
                    break;
                }

                if (gzwrite($gzip, $chunk) !== strlen($chunk)) {
                    throw new RuntimeException('Unable to compress the backup archive stream.');
                }
            }

            if (! gzclose($gzip)) {
                $gzip = false;
                throw new RuntimeException('Unable to finish compressing the backup archive.');
            }
            $gzip = false;
        } finally {
            fclose($input);
            if ($gzip !== false) {
                gzclose($gzip);
            }
        }
    }

    private function encryptStream(string $compressedPath, string $encryptedPath): void
    {
        $compressedSize = filesize($compressedPath);
        if ($compressedSize === false || $compressedSize < 1) {
            throw new RuntimeException('The compressed backup stream is empty or unavailable.');
        }

        $chunkCount = (int) ceil($compressedSize / self::CHUNK_BYTES);
        if ($chunkCount > 0xFFFFFFFF) {
            throw new RuntimeException('The backup stream exceeds the supported chunk count.');
        }
        if (self::V2_HEADER_BYTES + $compressedSize + $chunkCount * self::TAG_BYTES > $this->maxEncryptedBackupBytes()) {
            throw new RuntimeException('The encrypted backup exceeds BACKUP_MAX_ARCHIVE_BYTES.');
        }

        $salt = random_bytes(self::SALT_BYTES);
        $header = self::MAGIC.$salt.pack('J', $compressedSize);
        $encryptionKey = hash_hkdf(
            'sha256',
            $this->encryptionKey(),
            32,
            'jadwal-backup-jdbak2',
            $salt
        );
        $input = fopen($compressedPath, 'rb');
        $output = fopen($encryptedPath, 'xb');
        if ($input === false || $output === false) {
            if (is_resource($input)) {
                fclose($input);
            }
            if (is_resource($output)) {
                fclose($output);
            }
            throw new RuntimeException('Unable to open the encrypted backup streams.');
        }

        try {
            $this->writeAll($output, $header);
            $remaining = $compressedSize;
            for ($index = 0; $remaining > 0; $index++) {
                $chunkLength = min(self::CHUNK_BYTES, $remaining);
                $chunk = $this->readExact($input, $chunkLength);
                $nonce = str_repeat("\0", 8).pack('N', $index);
                $tag = '';
                $ciphertext = openssl_encrypt(
                    $chunk,
                    'aes-256-gcm',
                    $encryptionKey,
                    OPENSSL_RAW_DATA,
                    $nonce,
                    $tag,
                    $header.pack('N', $index),
                    self::TAG_BYTES
                );
                if ($ciphertext === false || strlen($tag) !== self::TAG_BYTES) {
                    throw new RuntimeException('Unable to encrypt a backup stream chunk.');
                }

                $this->writeAll($output, $tag.$ciphertext);
                $remaining -= $chunkLength;
            }

            $trailing = fread($input, 1);
            if ($trailing === false || $trailing !== '') {
                throw new RuntimeException('The compressed backup stream changed while being encrypted.');
            }
        } finally {
            fclose($input);
            fclose($output);
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
        if ($backupSize === false || $backupSize > $this->maxEncryptedBackupBytes()) {
            throw new RuntimeException('The encrypted backup exceeds BACKUP_MAX_ARCHIVE_BYTES.');
        }

        if ($backupSize < strlen(self::LEGACY_MAGIC) + 12 + self::TAG_BYTES + 1) {
            throw new RuntimeException('The backup file is empty or incomplete.');
        }

        $input = fopen($backupPath, 'rb');
        if ($input === false) {
            throw new RuntimeException('Unable to open the encrypted backup.');
        }

        try {
            $magic = $this->readExact($input, strlen(self::LEGACY_MAGIC));
        } finally {
            fclose($input);
        }

        $archivePath = $workDirectory.DIRECTORY_SEPARATOR.'restore.zip';
        $maxArchiveBytes = (int) config('backup.max_archive_bytes');
        if ($magic === self::MAGIC) {
            $compressedPath = $workDirectory.DIRECTORY_SEPARATOR.'restore.gz';
            $this->decryptStream($backupPath, $compressedPath);
            $this->decompressArchive($compressedPath, $archivePath, $maxArchiveBytes);
        } elseif ($magic === self::LEGACY_MAGIC) {
            $this->unpackLegacyV1($backupPath, $archivePath, $maxArchiveBytes);
        } else {
            throw new RuntimeException('The backup file format is not supported.');
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

    private function decryptStream(string $encryptedPath, string $compressedPath): void
    {
        $input = fopen($encryptedPath, 'rb');
        if ($input === false) {
            throw new RuntimeException('Unable to open the encrypted backup.');
        }

        $output = false;
        try {
            $header = $this->readExact($input, self::V2_HEADER_BYTES);
            if (substr($header, 0, strlen(self::MAGIC)) !== self::MAGIC) {
                throw new RuntimeException('The backup file format is not supported.');
            }

            $salt = substr($header, strlen(self::MAGIC), self::SALT_BYTES);
            $lengthBytes = substr($header, strlen(self::MAGIC) + self::SALT_BYTES, 8);
            $length = unpack('Jlength', $lengthBytes)['length'] ?? null;
            if (! is_int($length) || $length < 1 || $length > $this->maxEncryptedBackupBytes()) {
                throw new RuntimeException('The encrypted backup has an invalid stream length.');
            }

            $chunkCount = (int) ceil($length / self::CHUNK_BYTES);
            $expectedSize = self::V2_HEADER_BYTES + $length + $chunkCount * self::TAG_BYTES;
            if (filesize($encryptedPath) !== $expectedSize) {
                throw new RuntimeException('The encrypted backup is incomplete or contains trailing data.');
            }

            $encryptionKey = hash_hkdf(
                'sha256',
                $this->encryptionKey(),
                32,
                'jadwal-backup-jdbak2',
                $salt
            );
            $output = fopen($compressedPath, 'xb');
            if ($output === false) {
                throw new RuntimeException('Unable to stage the decrypted backup stream.');
            }

            $remaining = $length;
            for ($index = 0; $remaining > 0; $index++) {
                $chunkLength = min(self::CHUNK_BYTES, $remaining);
                $tag = $this->readExact($input, self::TAG_BYTES);
                $ciphertext = $this->readExact($input, $chunkLength);
                $nonce = str_repeat("\0", 8).pack('N', $index);
                $chunk = openssl_decrypt(
                    $ciphertext,
                    'aes-256-gcm',
                    $encryptionKey,
                    OPENSSL_RAW_DATA,
                    $nonce,
                    $tag,
                    $header.pack('N', $index)
                );
                if ($chunk === false) {
                    throw new RuntimeException('The backup could not be authenticated or decrypted.');
                }

                $this->writeAll($output, $chunk);
                $remaining -= $chunkLength;
            }
        } finally {
            fclose($input);
            if (is_resource($output)) {
                fclose($output);
            }
        }
    }

    private function decompressArchive(string $compressedPath, string $archivePath, int $maxArchiveBytes): void
    {
        $gzip = gzopen($compressedPath, 'rb');
        if ($gzip === false) {
            throw new RuntimeException('The encrypted backup is not a valid gzip stream.');
        }

        $output = fopen($archivePath, 'xb');
        if ($output === false) {
            gzclose($gzip);
            throw new RuntimeException('Unable to stage the decrypted backup archive.');
        }

        $archiveBytes = 0;
        try {
            while (true) {
                $chunk = gzread($gzip, self::CHUNK_BYTES);
                if ($chunk === false) {
                    throw new RuntimeException('The backup archive is damaged or could not be decompressed.');
                }
                if ($chunk === '') {
                    if (! gzeof($gzip)) {
                        throw new RuntimeException('The backup archive ended unexpectedly.');
                    }

                    break;
                }

                $archiveBytes += strlen($chunk);
                if ($archiveBytes > $maxArchiveBytes) {
                    throw new RuntimeException('The backup archive exceeds BACKUP_MAX_ARCHIVE_BYTES.');
                }
                $this->writeAll($output, $chunk);
            }

            $closed = gzclose($gzip);
            $gzip = false;
            if ($archiveBytes === 0 || ! $closed) {
                throw new RuntimeException('The backup archive is damaged or could not be decompressed.');
            }
        } finally {
            fclose($output);
            if ($gzip !== false) {
                gzclose($gzip);
            }
        }
    }

    private function unpackLegacyV1(string $backupPath, string $archivePath, int $maxArchiveBytes): void
    {
        $contents = file_get_contents($backupPath);
        $minimumLength = strlen(self::LEGACY_MAGIC) + 12 + self::TAG_BYTES + 1;
        if ($contents === false || strlen($contents) < $minimumLength) {
            throw new RuntimeException('The backup file is empty or incomplete.');
        }

        $headerLength = strlen(self::LEGACY_MAGIC);
        if (substr($contents, 0, $headerLength) !== self::LEGACY_MAGIC) {
            throw new RuntimeException('The backup file format is not supported.');
        }

        $nonce = substr($contents, $headerLength, 12);
        $tag = substr($contents, $headerLength + 12, self::TAG_BYTES);
        $ciphertext = substr($contents, $headerLength + 12 + self::TAG_BYTES);
        $compressed = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $this->encryptionKey(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            self::LEGACY_MAGIC
        );
        if ($compressed === false) {
            throw new RuntimeException('The backup could not be authenticated or decrypted.');
        }

        $archiveBytes = gzdecode($compressed, $maxArchiveBytes);
        if ($archiveBytes === false || strlen($archiveBytes) > $maxArchiveBytes) {
            throw new RuntimeException('The backup archive is damaged or could not be decompressed.');
        }

        if (file_put_contents($archivePath, $archiveBytes, LOCK_EX) === false) {
            throw new RuntimeException('Unable to stage the decrypted backup archive.');
        }
    }

    private function maxEncryptedBackupBytes(): int
    {
        $maxArchiveBytes = (int) config('backup.max_archive_bytes');

        return $maxArchiveBytes + intdiv($maxArchiveBytes, 100) + 2 * 1024 * 1024;
    }

    /**
     * @param  resource  $stream
     */
    private function readExact($stream, int $length): string
    {
        $contents = '';
        while (strlen($contents) < $length) {
            $chunk = fread($stream, $length - strlen($contents));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('The backup stream is incomplete or could not be read.');
            }

            $contents .= $chunk;
        }

        return $contents;
    }

    /**
     * @param  resource  $stream
     */
    private function writeAll($stream, string $contents): void
    {
        $offset = 0;
        $length = strlen($contents);
        while ($offset < $length) {
            $written = fwrite($stream, substr($contents, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Unable to write the backup stream.');
            }

            $offset += $written;
        }
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
        $integrityCheck = $pdo->query('PRAGMA integrity_check');
        if ($integrityCheck === false || $integrityCheck->fetchColumn() !== 'ok') {
            throw new RuntimeException('SQLite integrity check failed for the backup snapshot.');
        }

        $usersTableCheck = $pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'users'");
        if ($usersTableCheck === false || $usersTableCheck->fetchColumn() === false) {
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
            if ($date !== null) {
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
