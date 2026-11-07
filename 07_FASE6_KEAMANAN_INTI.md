# FASE 6 — KEAMANAN INTI (TANPA CSP)

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 0 selesai (CI + tes smoke). Boleh paralel dengan Fase 1–5 di branch terpisah.
**Tujuan fase:** Menutup celah keamanan yang nyata di kode saat ini: header, sesi, kata sandi, brute-force, otorisasi lintas-user, upload, admin, konfigurasi.
**Dilarang disentuh di fase ini:** tampilan; CSP & penghapusan inline handler (Fase 7).
**Keluaran wajib:** `SecurityHeaders` middleware, `config/security.php`, kebijakan sesi/kata sandi, limiter login, tes IDOR lintas-user untuk SEMUA resource, hardening upload & admin, `docs/KEAMANAN.md`.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-6-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---

## Konteks keamanan saat ini
Yang sudah baik (pertahankan): CSRF aktif, rate limit login (5), `throttle:60,1` pada rute tulis, `session()->regenerate()` setelah login, Policy untuk model milik user, upload avatar dibatasi dan privat, cookie `http_only` + `same_site=lax`, seeder demo hanya di local, middleware akun aktif.

Yang diperbaiki di fase ini: tidak ada security headers; `session.encrypt=false`; `SESSION_SECURE_COOKIE` bergantung env; policy kata sandi hanya default; middleware `auth.session` terdaftar tapi tidak dipakai; operasi sensitif tanpa `password.confirm`; limiter login hanya `email|ip`; perbedaan jalur akun nonaktif; admin tanpa pagar & audit; belum ada tes IDOR otomatis.

## Daftar pekerjaan
1. **Security headers (tanpa CSP — CSP ada di Fase 7)** lewat middleware global baru `SecurityHeaders` (daftarkan di `Kernel::$middleware`):
   - `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()`, `Cross-Origin-Opener-Policy: same-origin`, `Cross-Origin-Resource-Policy: same-origin`.
   - `Strict-Transport-Security: max-age=31536000; includeSubDomains` hanya bila `APP_ENV=production` dan permintaan HTTPS.
   - Halaman terautentikasi: `Cache-Control: private, no-cache` agar data tidak tersimpan di cache bersama; JANGAN memakai `no-store` pada halaman HTML biasa jika itu memutus bfcache tanpa alasan kuat — pertimbangkan dan dokumentasikan keputusannya.
   - Sediakan hook yang sudah siap untuk menambahkan header CSP nanti (konfigurasi `config/security.php` dengan `csp_mode` = `off|report|enforce`, default `off`).
2. **Sesi & cookie**: `SESSION_ENCRYPT=true`, `SESSION_SECURE_COOKIE=true` di produksi (dan paksa HTTPS via `URL::forceScheme('https')` bila `production`), `same_site=lax` (pertahankan), `http_only=true`, `SESSION_LIFETIME` wajar (mis. 120 mnt, perpanjang untuk "Ingat saya" lewat remember token), `SESSION_DRIVER=database`. Pakai middleware `auth.session` pada grup rute terautentikasi agar ganti kata sandi mengakhiri sesi di perangkat lain; tambahkan fitur "Keluar dari perangkat lain" (`Auth::logoutOtherDevices`) di Profil dengan konfirmasi kata sandi.
3. **Kata sandi**: konfigurasi `Password::defaults()` di `AppServiceProvider::boot()`: min 10 karakter (atau 8 dengan kompleksitas), `mixedCase()`, `numbers()`, dan di produksi `->uncompromised()`. Tolak kata sandi yang sama dengan email/nama. Hash default bcrypt (cost ≥ 12) atau argon2id; verifikasi `config/hashing.php`.
4. **Login anti-brute-force**: pertahankan limiter `email|ip` (5/menit) DAN tambahkan limiter per-IP (mis. 20/menit) dengan `RateLimiter::for('login', …)`; kunci berbasis email dinormalisasi; jangan membocorkan eksistensi akun (pesan seragam, waktu respons relatif seragam — lakukan `Hash::check` dummy bila user tak ada). Akun nonaktif: perilaku sesuai tabel 9.3. Catat log keamanan (login gagal beruntun, lockout) TANPA menyimpan kata sandi atau token; sertakan IP dan user-agent ringkas.
5. **Otorisasi (IDOR)**: audit SETIAP controller aksi yang memakai route-model-binding — pastikan ada `authorize()`/Policy atau scoping `where('user_id', auth()->id())` untuk Task, Habit, Goal, Wallet, Transaction, Obligation, Loan, Category, MoneyCategory, TaskStatus. Pastikan foreign key dari input (`wallet_id`, `to_wallet_id`, `money_category_id`, `category_id`, `habit_id`) divalidasi `Rule::exists(...)->where('user_id', auth()->id())` (Tugas sudah, cek yang lain). Respons untuk data milik orang lain: 404 (bukan 403 yang membocorkan). **Tulis tes otomatis**: user A mencoba GET/PUT/PATCH/DELETE resource user B untuk setiap rute → harus 404/403 dan data tidak berubah.
6. **Mass assignment & input**: semua model pakai `$fillable` eksplisit (tidak ada `$guarded = []`); `role`, `is_active`, `email_verified_at` tidak pernah dapat diisi dari request user. Validasi tipe & rentang ketat (nominal ≤ batas wajar, tanggal rentang masuk akal, string `max`). `TrimStrings` sudah ada; tambahkan normalisasi (hapus karakter kontrol).
7. **Operasi sensitif**: bungkus dengan `password.confirm` (hapus akun, ganti email, ganti kata sandi bila sesi lama, keluar dari semua perangkat). Ganti email → kirim verifikasi ke email baru dan beri tahu ke email lama.
8. **Upload (avatar; lampiran baru ada di Fase 10 Modul C, pakai aturan yang sama)**: validasi `image` + `mimes` + ukuran + dimensi (`dimensions:max_width=4000,max_height=4000`), cek MIME sebenarnya, **re-encode** lewat GD/Imagick (buang EXIF & payload tersembunyi), nama file acak (UUID), simpan di disk privat dan sajikan lewat route yang memeriksa kepemilikan (sudah ada untuk `profile.photo`; pertahankan, tambahkan header `X-Content-Type-Options: nosniff` dan `Content-Disposition` aman). Jangan pernah mempercayai nama/ekstensi dari klien.
9. **Panel admin**: cegah admin menghapus/menonaktifkan/menurunkan dirinya sendiri dan menurunkan admin terakhir; aksi destruktif wajib `password.confirm`; tambahkan **audit log** (tabel `admin_audit_logs`: admin_id, aksi, target, IP, waktu) untuk ubah status/role/hapus/reset kata sandi; batasi `throttle` pada aksi admin; jangan tampilkan data finansial pribadi user ke admin (hanya metadata akun).
10. **Konfigurasi & rahasia**: `.env.example` dibuat aman-secara-default untuk produksi di komentar (`APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`), `APP_DEBUG` tidak pernah true di produksi (tambahkan pemeriksaan boot yang gagal keras bila `production && debug`). Pastikan `.env`, `database/*.sqlite`, `storage/` tidak terbuka dari web (`public/.htaccess`/dokumentasi root server ke `public/`). Pastikan `/styleguide` hanya `local` (sudah) dan seeder demo/akun `demo@example.test` tidak ada di produksi (sudah dibatasi; tambahkan tes). Aktifkan `TrustHosts` dan atur `TrustProxies` dengan benar di belakang proxy/HTTPS.
11. **CSRF/XSS/Clickjacking**: semua form POST/PATCH/DELETE memakai `@csrf`/`@method`; semua fetch memakai header `X-CSRF-TOKEN` dari meta. Audit `{!! !!}` (saat ini hanya untuk attribute bag — aman) dan larang penambahan baru kecuali di-escape. Inline event handler dibersihkan di Fase 7 (bersama migrasi CSP); di fase ini cukup pastikan tidak ada yang BARU. `rel="noopener noreferrer"` pada tautan eksternal.
12. **Email**: tautan reset/verifikasi memakai `signed` + `throttle` (sudah); kedaluwarsa wajar (60 mnt untuk reset); pesan email Indonesia; jangan menyertakan data finansial di digest bila `hide_financial_amounts` aktif (periksa `DailyDigestMail`).
13. **Rantai pasok**: jalankan `composer audit` dan `npm audit`, perbarui patch/minor yang aman (jangan mayor), kunci versi lewat lockfile. Keluarkan folder `list/` dari repo/deploy (sarankan di laporan; jangan hapus tanpa konfirmasi pemilik).
14. **Rate limit tulis**: pertahankan `throttle:60,1` pada rute tulis; tambahkan limiter khusus untuk pencarian global dan unggah file; pesan 429 ramah dengan hitung mundur.
15. **Privasi**: mode sembunyikan nominal (`data-private`) pertahankan dan pastikan juga berlaku di halaman cetak/tangkapan layar pratinjau multitugas bila memungkinkan; log tidak memuat nominal atau catatan pribadi. Pastikan penghapusan akun yang SUDAH ada benar-benar menghapus data pengguna dan file fotonya (verifikasi + tes). Fitur "Ekspor data saya" baru ada di Fase 10 (Modul D).

## Tes wajib fase ini
- Tes IDOR parametris: untuk setiap rute resource (Task, Habit, Goal, Wallet, Transaction, Obligation, Loan, Category, MoneyCategory) user A mencoba GET/PUT/PATCH/DELETE milik user B → 404/403 dan data tak berubah. Sertakan juga foreign key dari input (mis. `wallet_id` milik user lain) → ditolak validasi.
- Tes header keamanan hadir pada respons (halaman publik & terautentikasi).
- Tes `Password::defaults` ditegakkan (terlalu pendek/tanpa campuran huruf → ditolak).
- Tes limiter: percobaan login ke-6 diblokir; limiter per-IP bekerja; pesan seragam.
- Tes admin: tidak bisa menghapus/menonaktifkan/menurunkan dirinya sendiri & admin terakhir; aksi tercatat di audit log.
- Tes upload: file bukan gambar bermuatan ekstensi gambar ditolak; hasil re-encode tidak mengandung EXIF.
- Tes bahwa seeder demo/akun demo tidak ada di environment `production` dan pemeriksaan boot menolak `production && APP_DEBUG=true`.

## Checklist selesai Fase 6
- [ ] Semua butir di atas selesai atau diberi alasan tertulis kenapa dilewati.
- [ ] `docs/KEAMANAN.md` berisi: model ancaman singkat, daftar kontrol, konfigurasi produksi yang wajib (HTTPS, `.env`, root web ke `public/`), dan cara melapor kerentanan.
- [ ] `composer audit` dan `npm audit` dicatat; patch/minor aman diperbarui (tanpa upgrade mayor).
