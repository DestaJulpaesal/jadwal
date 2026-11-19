# Panduan deployment

Panduan ini menargetkan Laravel 10 dan SQLite pada satu server. Data aplikasi dan konfigurasi harus tetap di luar document root; document root web server hanya `public/`.

## Prasyarat dan izin

- PHP 8.3+ dengan `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd`, `zip`, dan `zlib`.
- Composer 2, Node.js 20 dan npm untuk membuat aset rilis.
- HTTPS aktif dengan sertifikat valid. Redirect HTTP ke HTTPS di load balancer atau web server.
- Akun PHP harus dapat membaca kode dan menulis `storage/`, `bootstrap/cache/`, file SQLite di `database/`, serta direktori cadangan privat. Jangan beri hak tulis kepada pengguna lain.
- Simpan `.env`, database, log, foto privat, dan cadangan di luar root web. Jangan membuat `storage:link` untuk foto profil privat.

## Konfigurasi produksi

Salin `.env.example` menjadi `.env`, isi rahasia dari secret manager, dan atur sedikitnya:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
APP_KEY=<persistent generated Laravel key>
TRUSTED_PROXIES=10.0.0.10,10.0.0.0/24

DB_CONNECTION=sqlite
DB_DATABASE=/srv/jadwal/database/database.sqlite

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=daily
LOG_LEVEL=warning
BACKUP_DIRECTORY=/srv/jadwal-private/backups
BACKUP_ENCRYPTION_KEY=<separate base64 32-byte key>
```

`TRUSTED_PROXIES` must contain the reverse-proxy IP addresses/CIDRs that connect directly to the app, separated by commas. Leave it empty when the app is reached directly. Use `*` only when the network boundary guarantees every request reaches the app through a trusted proxy.

Buat `APP_KEY` satu kali (`php artisan key:generate`) dan pertahankan lintas rilis; jangan pernah membagikannya atau memasukkannya ke repository. Buat kunci cadangan terpisah sesuai [panduan backup](./BACKUP.md). Konfigurasikan SMTP produksi untuk email/verifikasi bila digunakan. Jadwal dan `onOneServer()` perlu cache lock yang sesuai jika aplikasi berjalan di lebih dari satu instance.

## Rilis

1. Masuk maintenance mode, hentikan worker, lalu buat dan salin cadangan terenkripsi.
2. Ambil kode rilis baru tanpa menghapus `.env`, database, `storage`, atau kunci.
3. Jalankan dari direktori aplikasi:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

4. Verifikasi `/up`, login, halaman utama, foto profil, log, dan status proses scheduler/worker.
5. Jalankan `php artisan up` setelah verifikasi. Jika rilis gagal, tahan maintenance mode, kembalikan kode sebelumnya, lalu pulihkan cadangan hanya bila migrasi membuat database tidak kompatibel dengan kode sebelumnya.

Jangan jalankan `db:seed` di setiap rilis. Untuk menyiapkan status bawaan saat instalasi awal gunakan `php artisan db:seed --force`; seeder demo hanya untuk environment `local`.

## Scheduler dan queue

Jalankan scheduler Laravel setiap menit:

```cron
* * * * * cd /srv/jadwal && php artisan schedule:run >> /dev/null 2>&1
```

Pada Windows Server, buat Scheduled Task per menit dengan direktori kerja proyek dan PHP CLI yang benar. Scheduler menjalankan backup harian, pemeriksaan umur/ukuran backup, housekeeping, dan ringkasan email sesuai preferensi pengguna. `app:backup-status` mencatat kejadian `critical` jika tidak ada backup atau backup terbaru berumur lebih dari 36 jam; pantau channel log aplikasi. Bila `QUEUE_CONNECTION` diubah dari `sync`, jalankan worker persisten di bawah Supervisor/systemd atau Windows Service, dan atur restart setelah deployment.

## Web server dan aset statis

Apache harus mengaktifkan `mod_rewrite`, `mod_headers`, `mod_mime`, dan `mod_setenvif`; aturan `public/.htaccess` mengirim MIME manifest dan `Service-Worker-Allowed: /`. Hanya aset Vite di `/build/assets/` dengan hash 8 karakter yang mendapat cache immutable:

```apache
<IfModule mod_setenvif.c>
    SetEnvIf Request_URI "^/build/assets/[^/]+-[A-Za-z0-9_-]{8}\.[^/]+$" immutable_vite_asset
</IfModule>

<IfModule mod_headers.c>
    Header set Cache-Control "public, max-age=31536000, immutable" env=immutable_vite_asset
</IfModule>
```

Aktifkan kompresi gzip/Brotli di web server atau proxy untuk CSS, JavaScript, JSON, SVG, dan font. Contoh Apache gzip:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/css application/javascript application/json image/svg+xml
</IfModule>
```
```

Contoh Nginx (sesuaikan root dan PHP-FPM socket):

```nginx
server {
    listen 443 ssl;
    server_name example.com;
    root /srv/jadwal/public;
    index index.php;

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml font/woff2;

    location /build/assets/ {
        try_files $uri =404;
        add_header Cache-Control "public, max-age=31536000, immutable";
    }

    location = /sw.js {
        add_header Service-Worker-Allowed "/";
        add_header Cache-Control "no-cache";
        try_files $uri =404;
    }

    location = /manifest.webmanifest {
        default_type application/manifest+json;
        try_files $uri =404;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
```

Atur redirect HTTP-ke-HTTPS pada server block port 80 terpisah. Service worker sengaja tidak menyimpan HTML atau data privat; `Cache-Control` untuk respons terautentikasi tetap private.

Ikon PWA yang sudah dihasilkan tersimpan di `public/icons`. Jika perlu dibuat ulang setelah mengubah favicon, jalankan `php scripts/make-icons.php` pada mesin yang memiliki ekstensi PHP GD.

## Health, logs, dan monitoring

`GET /up` returns only `{"status":"ok"}` with HTTP 200 after a short database query, or a generic `{"status":"unavailable"}` with HTTP 503. It never includes connection details. Configure an external uptime monitor for this URL. Responses include `X-Request-Id`; share that ID when reporting a 500 error. Logs use a 14-day daily channel when `LOG_CHANNEL=daily`; never log passwords, tokens, private notes, or financial amounts.

## Memverifikasi migrasi FK kategori tugas

Sebelum rilis yang mengubah skema SQLite, pastikan backup terenkripsi selesai dan dapat dibaca. Catat jumlah tugas sebelum migrasi, lalu bandingkan setelahnya; migration FK kategori tidak boleh mengurangi jumlah baris `tasks`.

```sh
sqlite3 "$DB_DATABASE" "SELECT COUNT(*) FROM tasks;"
php artisan migrate --force
sqlite3 "$DB_DATABASE" "SELECT COUNT(*) FROM tasks;"
sqlite3 "$DB_DATABASE" "PRAGMA foreign_key_check; PRAGMA integrity_check;"
```

`foreign_key_check` harus mengembalikan nol baris dan `integrity_check` harus mengembalikan `ok`. Pastikan jumlah tugas sesudah migrasi sama dengan jumlah sebelum migrasi. Penghapusan kategori sesudah rilis hanya boleh mengosongkan `tasks.category_id`, bukan menghapus tugas.

Catatan historis: migration `2026_11_04_120000_make_categories_global` mengganti tabel kategori. Pada SQLite, pola ini berisiko menjalankan aksi cascade FK ketika tabel lama dihapus. Jika memeriksa database yang telah menjalankan migration tersebut, bandingkan juga dengan snapshot sebelum migration; jumlah saat ini saja tidak dapat mengungkap baris yang sudah hilang.
