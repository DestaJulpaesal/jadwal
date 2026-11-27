# Ringkasan Progres

Dokumen ini menggantikan catatan harian/per-fase yang tidak kronologis. Catatan implementasi historis dapat dilihat pada riwayat Git dan dokumen terarsip.

## Selesai

- Laravel 13/PHP 8.3+, transaksi SQLite aman, pembatasan tanggal, mass assignment role, aktif terakhir admin, dan registrasi toggle.
- Modul tugas, kebiasaan, target, dompet, transaksi, anggaran, tagihan, pinjaman, pencarian, ekspor, riwayat streak, dan pengaturan telah terintegrasi.
- Backup streaming terenkripsi JDBAK2, kompatibilitas restore JDBAK1, rollback, rotasi, status umur, salinan S3, serta dokumentasi backup dan deployment tersedia.
- CI PHP 8.3/8.4, migration fixture, Pint, Larastan, PHPUnit, build frontend, bundle check, npm audit, dan Playwright tersedia.
- OCR struk teruji pada parser/Node dan lazy-load aset Tesseract; aset build tidak lagi dilacak sebagai file git.
- Header keamanan, nonce CSP, mode report/enforce, audit akun, dan sertifikasi test regresi diterapkan.

## Validasi terbaru

- PHPUnit terbaru: 294 test lulus dengan 1.957 assertion.
- Paket JavaScript/receipt: 16 tes lulus.
- Playwright E2E: 4 tes lulus, termasuk pemindaian struk sintetis pada CSP enforce.
- Pint: lulus.
- Vite build: lulus.
- `npm audit` dan `composer audit --locked`: nol advisori.
- Bundle JavaScript: lulus dengan 38,51 KB gzip.
- Bundle CSS: lulus dengan 19,98 KB gzip terhadap budget 20 KB; budget tidak dinaikkan.
- Larastan level 5: 0 diagnosis, run serial dengan batas memori 1 GB.
- Backup sintetis 64,1 MiB: puncak PHP JDBAK2 10 MiB untuk create dan restore; JDBAK1 lama 270 MiB saat create pada mesin uji yang sama.
- Composer audit awal menemukan advisori Symfony; patch Symfony 7.4.24 diterapkan, dan audit locked berikutnya bersih. Guzzle tidak lagi menjadi dependensi langsung, tetapi tetap dibutuhkan Laravel dan minishlink/web-push.
- Navigasi mobile diverifikasi kembali: lima item Beranda, Tugas, Kebiasaan, Keuangan, Profil; Target ada pada subnav Kebiasaan.

## Perlu verifikasi di luar repo

- Run GitHub Actions terbaru yang tersedia saat pemeriksaan (run [#47](https://github.com/DestaJulpaesal/jadwal/actions/runs/45227839453), SHA `52905df`) gagal di `pint --test`: ada import tak terpakai di `app/Services/WebPushSender.php` dan format di `routes/api.php`. Working tree lokal sekarang lulus Pint; CI remote belum dapat dinyatakan hijau sampai perubahan dipublikasikan dan workflow dijalankan ulang.
- Verifikasi checkout bersih dan workflow GitHub Actions setelah perubahan dipublikasikan.
- Konfigurasi Apache, MAIL, cron scheduler, push/VAPID, dan SMTP produksi.
- Izin IAM/S3, salinan offsite nyata, serta drill restore produksi.
- Scan struk pada Chrome Android dan Safari iOS menggunakan struk asli.
- Uji struk nyata pada 5–10 struk tiap perangkat; E2E sintetis pada CSP enforce lulus tanpa alasan untuk menambah `'wasm-unsafe-eval'`.
