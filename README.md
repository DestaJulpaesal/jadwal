# Jadwal Harian

Aplikasi jadwal multi-user berbasis Laravel. Pengguna hanya dapat mengelola tugas dan kategori miliknya sendiri. Jadwal disimpan dalam UTC dan ditampilkan menurut zona waktu akun (default `Asia/Jakarta`).

## Fitur

- CRUD tugas, pencarian, filter status, dan filter tenggat.
- Kalender bulanan, jam mulai/selesai, serta peringatan jadwal bentrok.
- Dashboard dan kalender terpadu untuk tugas, kebiasaan, target, serta agenda harian.
- Catatan kebiasaan dengan jadwal, check-in yang bisa dibatalkan, dan statistik beruntun.
- Keuangan pribadi: dompet, kategori, transaksi, transfer, tagihan, cicilan, utang, dan piutang.
- Laporan keuangan bulanan dengan kategori pengeluaran terbesar dan perbandingan bulan sebelumnya.
- Target angka/tabungan/kebiasaan, mode privasi nominal, dan ringkasan harian email opsional.
- Pengaturan zona waktu, awal minggu, tema, jadwal ringkasan email, privasi nominal, dan kartu dashboard.
- Kategori berwarna, verifikasi email opsional, foto profil privat yang dapat dikelola dari halaman Profil, dan panel admin untuk mencari/filter akun, melihat ringkasan akun, mengelola status dan role, serta mengirim tautan reset password.

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

Pada environment `local`, `php artisan db:seed` juga menyiapkan data demo yang aman diulang. Akun demo: `demo@example.test` dengan password `password`; ganti atau hapus akun ini sebelum aplikasi dapat diakses pihak lain. Seeder demo tidak berjalan di testing atau production.

Secara default, verifikasi email dinonaktifkan agar pendaftaran dan login lokal tidak mengirim email. Untuk mengaktifkannya, atur `AUTH_EMAIL_VERIFICATION=true` dan konfigurasi mailer (contohnya `MAIL_MAILER=log` untuk mencatat email verifikasi lokal di `storage/logs/laravel.log`). Untuk penggunaan online, atur provider email yang benar dan HTTPS.

Ringkasan harian bersifat opsional dan mati secara default. Pengguna mengaktifkannya dari Profil. Perintah `php artisan app:send-daily-digest` dijalankan oleh scheduler Laravel setiap jam; untuk menjalankan scheduler, panggil `php artisan schedule:run` setiap menit. Contoh cron Linux:

```cron
* * * * * cd /path/to/jadwal-harian && php artisan schedule:run >> /dev/null 2>&1
```

Di Windows, buat tugas terjadwal yang menjalankan `php artisan schedule:run` setiap menit dengan direktori kerja proyek. Pastikan PHP CLI yang dipakai tugas sama dengan versi PHP aplikasi.

## Catatan performa

Daftar tugas, kalender, dan dashboard memuat relasi kategori serta status secara eager loading. Indeks gabungan pada pemilik dan kolom status/waktu membantu pencarian jadwal per pengguna tanpa mengubah batas isolasi data.

## Tes dan build

```powershell
php artisan test
npm run build
```

PHPUnit memakai SQLite `:memory:` yang terpisah dari database aplikasi, sehingga migrasi ulang saat test tidak menghapus akun atau data lokal.

## Sistem desain

Lihat [docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md) untuk token visual, komponen Blade, dan panduan membuat halaman baru. Halaman pratinjau komponen tersedia di `/styleguide` saat lingkungan aplikasi `local`.

## Dokumentasi

- [Kemajuan fase polish](docs/PROGRESS.md)
- [Audit polish](docs/AUDIT_POLISH.md)
- [Arsitektur](docs/ARSITEKTUR.md)
- [Panduan deployment](docs/DEPLOY.md)
- [Panduan backup dan restore](docs/BACKUP.md)

## Checklist sebelum online

- Atur `APP_ENV=production` dan `APP_DEBUG=false`; buat `APP_KEY` sekali dan simpan dengan aman.
- Pastikan `.env` dan file database SQLite tidak dimasukkan ke repository publik.
- Aktifkan HTTPS, atur SMTP untuk verifikasi email, dan pastikan izin folder `storage` serta `bootstrap/cache` benar.
- Jalankan `php artisan migrate --force` saat deployment dan build aset dengan `npm run build`.
- Gunakan `php artisan config:cache`, `php artisan route:cache`, dan `php artisan view:cache` setelah konfigurasi produksi siap.
- Jalankan `php artisan db:seed --force` untuk menyiapkan status tugas bawaan; seeder data demo hanya berjalan pada environment `local`.
- Jadwalkan cron `schedule:run` setiap menit agar ringkasan email opsional berjalan.
- Jadwalkan backup database secara konsisten, simpan salinan terenkripsi di lokasi terpisah, dan uji proses pemulihannya. Untuk SQLite, gunakan backup konsisten seperti `sqlite3 database/database.sqlite ".backup 'database-backup.sqlite'"`; jangan menyalin file database aktif secara biasa ketika ada penulisan.
- Pastikan semua akun demo dihapus/nonaktif, akun admin dibuat terpisah, dan konfigurasi email diuji sebelum membuka akses publik.

SQLite memadai untuk penggunaan kecil dengan satu server dan sedikit penulisan bersamaan. Pertimbangkan MySQL atau PostgreSQL jika mulai sering menemui database-busy/lock, banyak pengguna menulis bersamaan, aplikasi dijalankan pada beberapa server, atau dibutuhkan replikasi dan pemulihan yang lebih kuat.
