# Keamanan aplikasi

## Header respons

Middleware `SecurityHeaders` menambahkan `nosniff`, perlindungan frame, kebijakan referrer dan permission, serta kebijakan same-origin untuk opener/resource. Halaman setelah autentikasi diberi `Cache-Control: private, no-cache`. HSTS hanya dikirim saat aplikasi berjalan di `production` dan permintaan dikenali sebagai HTTPS; pastikan proxy tepercaya meneruskan skema HTTPS dengan benar.

Konfigurasi CSP disiapkan melalui `CSP_MODE` (`off` secara default) dan `CSP_REPORT_URI`. CSP belum diterapkan di middleware; rollout report/enforce dikerjakan pada Fase 7 setelah semua halaman siap.

## Belum dituntaskan

Header dasar ini bukan pengganti HTTPS di reverse proxy/web server. Kebijakan kata sandi, perlindungan sesi, audit IDOR lintas-resource, operasi sensitif, hardening upload, audit log admin, dan pemeriksaan konfigurasi produksi masih harus diselesaikan serta diuji sebelum aplikasi dianggap siap produksi.
