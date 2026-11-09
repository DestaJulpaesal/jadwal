# Keamanan aplikasi

## Kontrol yang diterapkan

- Middleware `SecurityHeaders` menambahkan `nosniff`, `DENY`, Referrer/Permissions Policy, COOP/CORP, dan `Cache-Control: private, no-cache` untuk respons terautentikasi. HSTS hanya aktif pada production melalui HTTPS.
- Middleware `TrustHosts` dan `TrustProxies` digunakan; HTTPS dipaksakan untuk URL yang dibuat di production. Boot aplikasi menolak `APP_DEBUG=true` pada production.
- Sesi dienkripsi secara default. Cookie secure mengikuti konfigurasi dan secara default aktif di production; gunakan HTTPS, `SESSION_SECURE_COOKIE=true`, dan `SESSION_DRIVER=database` di produksi.
- Kebijakan password terpusat: minimal 8 karakter, huruf besar-kecil, dan angka. Pendaftaran menolak kata sandi yang sama dengan nama atau email. Hash bcrypt menggunakan cost 12.
- Login mempertahankan batas 5 percobaan per pasangan email/IP dan memiliki batas tambahan 20 percobaan per IP. Pengguna yang tidak ditemukan menjalani pemeriksaan hash dummy; log lockout hanya menyimpan IP dan user-agent terpotong.
- Grup terautentikasi memakai `auth.session`; perubahan kata sandi meminta kata sandi saat ini dan mengeluarkan sesi perangkat lain. Aksi perubahan status/peran/reset/hapus akun admin meminta konfirmasi kata sandi terbaru.
- Policy dan FormRequest membatasi resource personal dan foreign key ke pemiliknya; kategori global dibolehkan secara eksplisit. Penghapusan akun menghapus kategori personal yang masih terkait.
- Foto profil harus berupa gambar dengan format dan dimensi terbatas; server memproses ulang gambar menggunakan GD, nama file acak, penyimpanan privat, serta header `nosniff`.
- Perubahan status/peran, pengiriman tautan reset, dan penghapusan akun admin dicatat pada `admin_audit_logs` dengan ID target, IP, user-agent terbatas, dan waktu.
- Housekeeping terjadwal menghapus catatan audit yang berusia lebih dari 365 hari.
- CSRF tetap aktif untuk aplikasi; endpoint laporan CSP saja dikecualikan dan dibatasi ukuran/rate. Seeder demo hanya aktif di local.

## CSP bertahap

Konfigurasi CSP menyediakan mode `off|report|enforce`, nonce per respons, endpoint laporan same-origin, serta Alpine build CSP. Default tetap `off`; seluruh route dan interaksi belum dipantau di report mode. Jangan menyalakan `enforce` di production sebelum observasi report lintas halaman selesai. Lihat [CSP.md](./CSP.md).

## Kewajiban sebelum production

- Arahkan document root hanya ke `public/`; aktifkan HTTPS dan redirect HTTP, set secret environment melalui secret manager, dan pastikan `APP_DEBUG=false`.
- Lakukan review IDOR per route terhadap semua resource serta uji dengan data milik pengguna berbeda; suite saat ini tidak membuktikan setiap kombinasi metode/resource.
- Tentukan kebijakan verifikasi kata sandi bocor. Pemeriksaan `uncompromised()` tidak diaktifkan karena akan mengirim sidik jari kata sandi ke layanan eksternal; jangan mengaktifkannya tanpa persetujuan privasi yang jelas.
- Batasi akses operasional untuk audit log, backup, dan foto profil; simpan kunci backup terpisah dari server aplikasi.
- Jalankan audit dependency pada lingkungan deployment dan tinjau perubahan advisory sebelum pembaruan.

Header aplikasi tidak menggantikan konfigurasi reverse proxy/web server. Detail deployment dan restore ada di [DEPLOY.md](./DEPLOY.md) dan [BACKUP.md](./BACKUP.md).
