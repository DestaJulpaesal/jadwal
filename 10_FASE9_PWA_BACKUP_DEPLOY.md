# FASE 9 — PWA, BACKUP & DATABASE, DEPLOY, MONITORING

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 6–8 selesai.
**Tujuan fase:** Membuat app terasa seperti aplikasi HP, datanya aman dari kehilangan, dan siap dijalankan stabil di produksi.
**Dilarang disentuh di fase ini:** logika bisnis; jangan menyimpan halaman terautentikasi/data uang di cache service worker.
**Keluaran wajib:** manifest + ikon + service worker aman, perintah backup SQLite terjadwal + uji restore, panduan keputusan DB, panduan deploy, health check, logging/monitoring, `docs/DEPLOY.md`, `docs/BACKUP.md`.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-9-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---

## 9A. PWA (installable, tanpa mengorbankan keamanan)
Pengguna kebanyakan di HP, jadi app harus bisa dipasang di layar utama dan terasa seperti app.
1. `public/manifest.webmanifest`: `name`, `short_name` ("Jadwal"), `description`, `lang: "id"`, `start_url: "/dashboard?source=pwa"`, `scope: "/"`, `display: "standalone"`, `orientation: "portrait"`, `theme_color` & `background_color` dari token, ikon.
2. Ikon: 192×192 dan 512×512 (PNG), versi **maskable** 512×512, `apple-touch-icon` 180×180, favicon sudah ada. Buat dari `favicon.svg` (script `scripts/make-icons.mjs` memakai `sharp` bila tersedia; jika tidak, dokumentasikan langkah manual).
3. Tag di `<head>` layout dasar: `<link rel="manifest">`, `<meta name="theme-color">` (terang & gelap via `media`), `apple-mobile-web-app-capable`, `apple-mobile-web-app-status-bar-style`, `viewport-fit=cover`. Hormati safe-area di header/bottom-nav.
4. **Service worker** (`public/sw.js`, didaftarkan hanya di HTTPS/localhost):
   - Pra-cache **hanya aset statis berhash** (CSS/JS/font/ikon/sprite) dan halaman `/offline` statis.
   - Strategi: aset = cache-first berversi; navigasi (HTML) = **network-first**, fallback ke `/offline` saat gagal. **JANGAN meng-cache HTML terautentikasi, respons API/JSON, request non-GET, foto profil/lampiran, atau apa pun yang berisi data pengguna.**
   - Versi cache (`CACHE_VERSION`), hapus cache lama saat `activate`.
   - Pembaruan: saat versi baru siap tampilkan toast "Versi baru tersedia — Muat ulang" (jangan reload paksa di tengah pengisian form).
   - Saat logout, kirim pesan ke SW untuk membersihkan cache dinamis (jika ada).
5. Halaman `/offline`: ramah, ikon, penjelasan ("Kamu sedang offline. Data tersimpan di server aman. Coba lagi saat tersambung."), tombol "Coba lagi".
6. UX pemasangan: tombol "Pasang aplikasi" di Pengaturan/menu Lainnya (tangkap `beforeinstallprompt`; di iOS tampilkan petunjuk "Bagikan → Tambah ke Layar Utama"). Jangan memunculkan prompt mengganggu saat pertama buka.
7. Header: `Service-Worker-Allowed`, `manifest` dilayani `application/manifest+json`. Pastikan CSP (Fase 7) mengizinkan `worker-src 'self'` dan `manifest-src 'self'`.
8. Tes: Lighthouse PWA (installable), uji install di Chrome Android, uji offline (DevTools), uji bahwa setelah logout tidak ada data pengguna di Cache Storage.

## 9B. Backup & ketahanan data (SQLite)
Ini data keuangan orang; kehilangan data tidak bisa diterima.
1. Mode SQLite aman untuk web: aktifkan **WAL** (`PRAGMA journal_mode=WAL`), `busy_timeout` (mis. 5000 ms), `foreign_keys=ON`, `synchronous=NORMAL` lewat konfigurasi koneksi `config/database.php` (opsi `journal_mode`, `busy_timeout`, `foreign_key_constraints` didukung Laravel 10; verifikasi).
2. Perintah artisan `app:backup-database`: salin konsisten memakai `VACUUM INTO 'path'` (atau API `.backup`), simpan ke `storage/app/backups/jadwal-YYYYmmdd-HHMM.sqlite`, jalankan `PRAGMA integrity_check` pada salinan, **kompres** (gzip), dan **enkripsi** (mis. `Crypt`/openssl dengan kunci terpisah dari `APP_KEY` jika memungkinkan). Retensi: simpan 14 harian + 8 mingguan, hapus sisanya.
3. Jadwalkan di `app/Console/Kernel.php` (`->dailyAt('02:30')->onOneServer()->withoutOverlapping()`), pastikan scheduler berjalan (cron/Task Scheduler; sudah ada di README).
4. Salinan di luar server: dokumentasikan opsi (rclone/S3/Google Drive/folder berbeda). Jangan menyimpan cadangan di dalam `public/`.
5. **Uji restore** (wajib): perintah `app:restore-database {file}` (dengan konfirmasi dan backup otomatis sebelum menimpa) + tes yang membuktikan data identik setelah restore. Tulis prosedur di `docs/BACKUP.md` dan jadwalkan uji restore berkala (catatan kalender).
6. Penyimpanan berkas pengguna (foto profil, nanti lampiran): ikut dibackup (`storage/app/private`).
7. Notifikasi gagal backup: log `critical` + (opsional) email ke admin.

## 9C. Keputusan database (dokumentasikan, belum tentu pindah)
Di `docs/ARSITEKTUR.md` tambahkan bagian "Kapan pindah dari SQLite":
- Tetap SQLite jika: satu server, pengguna aktif < ±200, tulis tidak sering bersamaan, backup rutin ada.
- Pindah ke MySQL/MariaDB/PostgreSQL jika: >1 server/instance, lonjakan error `database is locked`, butuh replikasi/hot backup, pengguna aktif banyak, atau butuh pencarian teks lanjutan.
- Siapkan jalur migrasi: kode memakai Eloquent/Query Builder (hindari SQL khusus SQLite); tulis daftar potensi masalah (tipe boolean/tanggal, `doctrine/dbal` rename kolom, collation, case-sensitivity unique email). Sediakan tes yang bisa dijalankan terhadap MySQL via env tes (opsional di CI).

## 9D. Deploy & operasional
Buat `docs/DEPLOY.md` (langkah demi langkah, untuk hosting umum/VPS):
- Prasyarat PHP 8.1+, ekstensi (`pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `gd`/`imagick`), izin folder (`storage`, `bootstrap/cache`, `database`).
- Root web = `public/`; HTTPS wajib; redirect HTTP→HTTPS; `.env` produksi (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `SESSION_DRIVER=database`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `CACHE_DRIVER`, `QUEUE_CONNECTION`, mailer sungguhan, `LOG_CHANNEL=daily`, `LOG_LEVEL=warning`).
- Perintah rilis: `composer install --no-dev --optimize-autoloader`, `npm ci && npm run build`, `php artisan migrate --force`, `config:cache route:cache view:cache event:cache`, `storage:link` bila perlu. Urutan, rollback (backup dulu), dan mode maintenance (`php artisan down --secret=...`).
- Cache aset berhash `immutable` + kompresi (contoh `.htaccess` dan blok nginx).
- Skrip `scripts/deploy.sh` idempotent opsional.
- Queue/scheduler: cron `schedule:run` tiap menit; jika `QUEUE_CONNECTION` bukan `sync`, jalankan worker (supervisor/systemd) — contoh konfigurasi.
- **Health check**: rute `GET /up` (Laravel 10: tambahkan sendiri) mengembalikan 200 + cek koneksi DB singkat tanpa membocorkan detail; dipakai uptime monitor.

## 9E. Logging & monitoring
- `LOG_CHANNEL=daily` (retensi 14 hari). Jangan mencatat kata sandi/token/nominal/catatan pribadi. Tambahkan context ringan (user_id, request_id) lewat middleware `RequestId` dan tambahkan `X-Request-Id` di respons agar laporan bug mudah dilacak.
- Halaman 500 menampilkan kode referensi (request id) untuk dilaporkan pengguna.
- Opsional: integrasi pelacak error (Sentry/Flare) lewat env, dimatikan secara default; pastikan scrubbing data pribadi.
- Uptime monitor eksternal ke `/up` (dokumentasikan).

## 9F. Housekeeping pemeliharaan
- Perintah `app:prune-stale-data` (jadwalkan harian): hapus token reset kedaluwarsa, sesi kedaluwarsa (jika driver database), `failed_jobs` lama, file `storage/app/tmp`.
- `composer audit` + `npm audit` terjadwal di CI mingguan (workflow `schedule`).
- Dependabot/Renovate untuk patch/minor (tanpa mayor otomatis).

## Checklist selesai Fase 9
- [ ] App bisa dipasang (installable) di Android Chrome & iOS (petunjuk); `/offline` berfungsi; tidak ada data pengguna di cache SW.
- [ ] Backup terjadwal berjalan, terenkripsi, retensi bekerja; **restore berhasil diuji** dan terdokumentasi.
- [ ] WAL + busy_timeout aktif; uji dua penulisan bersamaan tidak menghasilkan error kunci.
- [ ] `docs/DEPLOY.md`, `docs/BACKUP.md`, bagian "Kapan pindah dari SQLite" selesai.
- [ ] `/up` berfungsi; log harian; request id di respons & halaman 500.
- [ ] CI mingguan untuk audit dependensi aktif.
