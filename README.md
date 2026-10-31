# Daily Schedule

Aplikasi jadwal multi-user berbasis Laravel. Pengguna hanya dapat mengelola tugas dan kategori miliknya sendiri. Jadwal disimpan dalam UTC dan ditampilkan menurut zona waktu akun (default `Asia/Jakarta`).

## Fitur

- CRUD tugas, pencarian, filter status, dan filter tenggat.
- Kalender bulanan, jam mulai/selesai, serta peringatan jadwal bentrok.
- Dashboard berisi jadwal hari ini, tugas terlambat, dan tugas tujuh hari ke depan.
- Kategori berwarna, verifikasi email opsional, dan panel admin untuk pengelolaan akun.

## Persyaratan

- PHP 8.1 atau lebih baru dengan ekstensi PDO SQLite (`pdo_sqlite`).
- Composer.
- Node.js dan npm.

## Instalasi lokal (PowerShell)

Jalankan dari folder proyek:

```powershell
composer install
npm install
if (-not (Test-Path .\.env)) {
    Copy-Item .env.example .env
}
if (-not (Test-Path .\database\database.sqlite)) {
    New-Item -ItemType File -Path .\database\database.sqlite | Out-Null
}
php artisan key:generate
php artisan migrate --seed
```

Jalankan aplikasi dan Vite pada dua terminal:

```powershell
php artisan serve
```

```powershell
npm run dev
```

Buka `http://localhost:8000`. Seeder menyiapkan status tugas bawaan. Untuk membuat akun admin pertama, jalankan `php artisan admin:create admin@example.com --name=Admin`; perintah akan meminta password secara tersembunyi.

Secara default, verifikasi email dinonaktifkan agar pendaftaran dan login lokal tidak mengirim email. Untuk mengaktifkannya, atur `AUTH_EMAIL_VERIFICATION=true` dan konfigurasi mailer (contohnya `MAIL_MAILER=log` untuk mencatat email verifikasi lokal di `storage/logs/laravel.log`). Untuk penggunaan online, atur provider email yang benar dan HTTPS.

## Catatan performa

Daftar tugas, kalender, dan dashboard memuat relasi kategori serta status secara eager loading. Indeks gabungan pada pemilik dan kolom status/waktu membantu pencarian jadwal per pengguna tanpa mengubah batas isolasi data.

## Tes dan build

```powershell
php artisan test
npm run build
```

## Checklist sebelum online

- Atur `APP_ENV=production` dan `APP_DEBUG=false`; buat `APP_KEY` sekali dan simpan dengan aman.
- Pastikan `.env` dan file database SQLite tidak dimasukkan ke repository publik.
- Aktifkan HTTPS, atur SMTP untuk verifikasi email, dan pastikan izin folder `storage` serta `bootstrap/cache` benar.
- Jalankan `php artisan migrate --force` saat deployment dan build aset dengan `npm run build`.
- Gunakan `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache` setelah konfigurasi produksi siap.
- Jadwalkan backup database secara konsisten, simpan salinan di lokasi terpisah, dan uji proses pemulihannya.

SQLite memadai untuk penggunaan kecil dengan satu server dan sedikit penulisan bersamaan. Pertimbangkan MySQL atau PostgreSQL jika mulai sering menemui database-busy/lock, banyak pengguna menulis bersamaan, aplikasi dijalankan pada beberapa server, atau dibutuhkan replikasi dan pemulihan yang lebih kuat.
