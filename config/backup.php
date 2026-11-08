<?php

return [
    'directory' => env('BACKUP_DIRECTORY', storage_path('app/backups')),
    'encryption_key' => env('BACKUP_ENCRYPTION_KEY'),
    'max_archive_bytes' => (int) env('BACKUP_MAX_ARCHIVE_BYTES', 536870912),
    'profile_photos_directory' => storage_path('app/profile-photos'),
];
