# Cadangan dan pemulihan

Perintah cadangan hanya mendukung SQLite. Setiap arsip berisi snapshot SQLite yang dibuat dengan `VACUUM INTO` dan foto profil dari `storage/app/profile-photos`. Format baru JDBAK2 mengompresi arsip dengan gzip lalu mengenkripsi potongan 1 MiB menggunakan AES-256-GCM, kunci khusus arsip dari HKDF-SHA256, dan autentikasi untuk header serta urutan potongan. Restore tetap mendukung arsip JDBAK1 yang dibuat versi lama. Perubahan data dan foto selama restore hanya boleh dilakukan saat aplikasi berada dalam maintenance mode.

## Konfigurasi

Buat kunci acak 32-byte dan simpan di secret manager atau lokasi terpisah dari server aplikasi:

```powershell
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
```

```dotenv
BACKUP_DIRECTORY=/srv/jadwal-private/backups
BACKUP_ENCRYPTION_KEY=<base64 key from secret manager>
```

Di Windows, gunakan path absolut yang tidak berada di bawah `public`. Batasi akses direktori cadangan ke akun layanan aplikasi. Ukuran ZIP dibatasi `BACKUP_MAX_ARCHIVE_BYTES` (default 512 MiB) saat membuat maupun memulihkan arsip. JDBAK2 memproses kompresi, enkripsi, dekripsi, dan dekompresi secara streaming sehingga konsumsi memori tidak meningkat mengikuti ukuran backup; arsip JDBAK1 lama masih memakai jalur kompatibilitas yang membaca payload ke memori.

Pengukuran lokal pada SQLite sintetis 64,1 MiB dengan data acak: JDBAK1 memakai puncak 270 MiB saat membuat arsip; JDBAK2 memakai puncak 10 MiB untuk create (4,56 detik) dan restore (2,34 detik). Pengukuran `memory_get_peak_usage(true)` ini berasal dari satu mesin Windows/PHP, bukan jaminan kapasitas produksi. Arsip JDBAK1 berukuran besar masih dapat membutuhkan beberapa kali ukuran payload dalam `memory_limit`; pulihkan arsip lama pada host dengan memori memadai atau buat rilis JDBAK2 terlebih dahulu. Jangan gunakan `APP_KEY` sebagai kunci cadangan. Kehilangan kunci ini membuat arsip tidak dapat dipulihkan.

Koneksi SQLite mengaktifkan foreign keys melalui konfigurasi Laravel serta `journal_mode=WAL`, `busy_timeout=5000`, dan `synchronous=NORMAL` ketika koneksi dibuat. Perubahan PRAGMA ini khusus SQLite; koneksi selain SQLite tidak terpengaruh. Housekeeping harian juga menghapus token reset kedaluwarsa, sesi database yang idle, failed job lebih dari 30 hari, dan file di `storage/app/tmp` yang lebih tua dari satu hari.

## Membuat dan memeriksa cadangan

```powershell
php artisan app:backup-database
php artisan app:backup-status
```

Penjadwal membuat cadangan setiap hari pukul 02:30 dan memeriksa statusnya pukul 03:30. `app:backup-status` menampilkan usia cadangan terbaru, jumlah arsip terenkripsi, dan total ukurannya; perintah berakhir gagal dan mencatat `critical` bila tidak ada cadangan atau cadangan terbaru berusia lebih dari 36 jam. Rotasi mempertahankan 14 cadangan harian terbaru dan satu cadangan per minggu untuk delapan minggu terakhir. Kegagalan backup juga dicatat pada level `critical`. Cadangan lokal bukan pengganti salinan di luar server: salin arsip terenkripsi ke lokasi terpisah (misalnya bucket S3 privat, penyimpanan Google Drive privat, atau server cadangan melalui rclone) dan jangan pernah menyalin kunci enkripsi bersama arsip.

## Salinan offsite S3

Pasang AWS CLI pada server aplikasi dan konfigurasikan identitas tanpa admin (IAM) yang hanya memiliki izin `s3:PutObject` pada bucket tujuan:

```dotenv
BACKUP_OFFSITE_S3_BUCKET=nama-bucket-pribadi
BACKUP_OFFSITE_S3_PREFIX=jadwal-backups
BACKUP_OFFSITE_STATUS_FILE=/srv/jadwal-private/backups/.s3-status.json
```

Setelah `php artisan app:backup-database` berhasil membuat arsip lokal, aplikasi menjalankan `aws s3 cp` untuk mengirim arsip terenkripsi ke `s3://nama-bucket-pribadi/jadwal-backups/<nama-arsip>`. Kegagalan penyalinan dicatat sebagai `critical`, membuat perintah backup gagal, dan tidak mengubah atau menyalin `BACKUP_ENCRYPTION_KEY`. File status hanya berisi nama arsip dan waktu berhasil, bukan kunci.

`php artisan app:backup-status` memeriksa status lokal dan, bila bucket S3 dikonfigurasi, memeriksa usia status offsite; status lebih lama dari 36 jam atau file status hilang membuat pemeriksaan gagal. Pastikan pemeliharaan IAM, AWS CLI, dan file status diawasi.

Contoh sinkronisasi manual bila AWS CLI sudah dikonfigurasi:

```sh
aws s3 cp /srv/jadwal-private/backups/<nama-arsip> s3://nama-bucket-pribadi/jadwal-backups/ --only-show-errors
aws s3 ls s3://nama-bucket-pribadi/jadwal-backups/
```

## Memulihkan cadangan

Pemulihan mengganti seluruh database SQLite dan direktori foto profil. Perintah memverifikasi autentikasi GCM, struktur arsip, dan `PRAGMA integrity_check`, lalu membuat cadangan terenkripsi otomatis sebelum mengganti data. Sebelum mengganti file, restore menjalankan `wal_checkpoint(TRUNCATE)`, memvalidasi sidecar `database.sqlite-wal`/`-shm` sebagai file biasa, dan menghapusnya secara eksplisit; proses berhenti sebelum mengganti database jika checkpoint atau pembersihan gagal. Jalankan pada maintenance window, hentikan scheduler/worker dan tulis aplikasi:

```powershell
php artisan down --secret="<temporary-secret>"
php artisan app:restore-database jadwal-YYYYmmdd-HHMMSS-xxxxxxxx.sqlite.gz.enc
php artisan up
```

Secara default perintah meminta konfirmasi. Opsi `--force` melewati prompt interaktif, tetapi **tidak** melewati pemeriksaan maintenance mode atau validasi arsip. `file` harus merupakan nama file di direktori cadangan yang dikonfigurasi; path di luar direktori tersebut ditolak.

Setelah pemulihan, periksa dashboard, profil pengguna dan fotonya, serta beberapa catatan yang diketahui. Simpan file cadangan otomatis yang dicetak oleh perintah sampai aplikasi diverifikasi. Jika perintah gagal, baca `storage/logs/laravel.log` sebelum menonaktifkan maintenance mode.

## Uji pemulihan berkala

Feature test `tests/Feature/EncryptedSqliteBackupTest.php` membuat database sementara, mengubah data dan foto setelah cadangan, lalu memulihkan dan membuktikan keduanya kembali ke nilai semula. Test tidak menimpa database kerja. Jalankan juga simulasi pemulihan produksi minimal per kuartal pada server/database sementara, memakai kunci dan arsip salinan luar server, bukan pada database aktif.
