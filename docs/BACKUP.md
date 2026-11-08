# Cadangan dan pemulihan

Perintah cadangan hanya mendukung SQLite. Setiap arsip berisi snapshot SQLite yang dibuat dengan `VACUUM INTO` dan foto profil dari `storage/app/profile-photos`. Arsip dikompresi dengan gzip, lalu dienkripsi AES-256-GCM. Perubahan data dan foto selama restore hanya boleh dilakukan saat aplikasi berada dalam maintenance mode.

## Konfigurasi

Buat kunci acak 32-byte dan simpan di secret manager atau lokasi terpisah dari server aplikasi:

```powershell
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

```dotenv
BACKUP_DIRECTORY=/srv/jadwal-private/backups
BACKUP_ENCRYPTION_KEY=<base64 key from secret manager>
```

Di Windows, gunakan path absolut yang tidak berada di bawah `public`. Batasi akses direktori cadangan ke akun layanan aplikasi. Ukuran arsip dibatasi `BACKUP_MAX_ARCHIVE_BYTES` (default 512 MiB) agar proses enkripsi/pemulihan tetap terkendali; sesuaikan dengan kapasitas memori dan ukuran data. Jangan gunakan `APP_KEY` sebagai kunci cadangan. Kehilangan kunci ini membuat arsip tidak dapat dipulihkan.

Koneksi SQLite mengaktifkan foreign keys melalui konfigurasi Laravel serta `journal_mode=WAL`, `busy_timeout=5000`, dan `synchronous=NORMAL` ketika koneksi dibuat. Perubahan PRAGMA ini khusus SQLite; koneksi selain SQLite tidak terpengaruh. Housekeeping harian juga menghapus token reset kedaluwarsa, sesi database yang idle, failed job lebih dari 30 hari, dan file di `storage/app/tmp` yang lebih tua dari satu hari.

## Membuat dan memeriksa cadangan

```powershell
php artisan app:backup-database
```

Penjadwal menjalankannya setiap hari pukul 02:30. Rotasi mempertahankan 14 cadangan harian terbaru dan satu cadangan per minggu untuk delapan minggu terakhir. Kegagalan dicatat pada level `critical` dan perintah berakhir dengan status gagal. Cadangan lokal bukan pengganti salinan di luar server: salin arsip terenkripsi ke lokasi terpisah (misalnya bucket S3 privat, penyimpanan Google Drive privat, atau server cadangan melalui rclone) dan jangan pernah menyalin kunci enkripsi bersama arsip.

## Memulihkan cadangan

Pemulihan mengganti seluruh database SQLite dan direktori foto profil. Perintah memverifikasi autentikasi GCM, struktur arsip, dan `PRAGMA integrity_check`, lalu membuat cadangan terenkripsi otomatis sebelum mengganti data. Jalankan pada maintenance window, hentikan scheduler/worker dan tulis aplikasi:

```powershell
php artisan down --secret="<temporary-secret>"
php artisan app:restore-database jadwal-YYYYmmdd-HHMMSS-xxxxxxxx.sqlite.gz.enc
php artisan up
```

Secara default perintah meminta konfirmasi. Opsi `--force` melewati prompt interaktif, tetapi **tidak** melewati pemeriksaan maintenance mode atau validasi arsip. `file` harus merupakan nama file di direktori cadangan yang dikonfigurasi; path di luar direktori tersebut ditolak.

Setelah pemulihan, periksa dashboard, profil pengguna dan fotonya, serta beberapa catatan yang diketahui. Simpan file cadangan otomatis yang dicetak oleh perintah sampai aplikasi diverifikasi. Jika perintah gagal, baca `storage/logs/laravel.log` sebelum menonaktifkan maintenance mode.

## Uji pemulihan berkala

Feature test `tests/Feature/EncryptedSqliteBackupTest.php` membuat database sementara, mengubah data dan foto setelah cadangan, lalu memulihkan dan membuktikan keduanya kembali ke nilai semula. Test tidak menimpa database kerja. Jalankan juga simulasi pemulihan produksi minimal per kuartal pada server/database sementara, memakai kunci dan arsip salinan luar server, bukan pada database aktif.
