# Prompt perbaikan lanjutan: Jadwal Harian

Cara pakai: tempel ke Claude Code (atau agen coding lain yang bisa menjalankan PHP dan npm di repo).
Tempel **per prioritas**, bukan semuanya sekaligus. Mulai dari Prioritas 1.

Catatan: file ini menggantikan `PROMPT-PERBAIKAN-JADWAL-HARIAN.md`. Butir lama yang sudah selesai di kode
sudah dibuang. Isinya sekarang hanya (a) sisa butir lama yang belum tuntas dan (b) temuan baru dari review kode.
Semua temuan berasal dari **membaca kode**, bukan menjalankan aplikasi. Butir bertanda **[VERIFIKASI]** masih dugaan
dan harus dibuktikan dulu. Butir bertanda **[TANYA DULU]** butuh keputusanku sebelum dikerjakan.

---

## Konteks

Kamu bekerja di repo Laravel 13 "Jadwal Harian" (PHP 8.3, SQLite mode WAL, Vite, Alpine CSP build, PWA,
CSP bernonce dengan mode `off|report|enforce`, `script-src 'self'` + nonce, `connect-src 'self'`, `worker-src 'self'`).
Struktur bootstrap gaya Laravel 10 (Http/Console Kernel) sengaja dipertahankan. Jangan migrasi struktur.

Pola yang sudah ada dan harus diikuti:
- Query data pengguna selalu dimulai dari `$request->user()->relasi()`; FK divalidasi dengan `Rule::exists()->where('user_id', ...)`.
- Operasi multi-tabel memakai `App\Support\Db::immediate` (SQLite `BEGIN IMMEDIATE`).
- Nominal Rupiah integer lewat `App\Support\Rupiah`.
- Teks UI berbahasa Indonesia lewat `lang/id/ui.php`.

## Aturan kerja

- Kerjakan satu butir per commit/PR kecil. Jangan refactor di luar butir.
- Sebelum mengubah, baca kodenya dan buktikan masalahnya (tes yang gagal atau pengukuran).
  Kalau ternyata tidak terjadi, laporkan dan lewati.
- Setelah tiap butir jalankan: `php artisan test`, `vendor/bin/pint --test`, Larastan, `npm run build`,
  `npm run check:bundle`. Jangan bilang "selesai" tanpa hasil nyata dari perintah itu.
- Jangan menaikkan budget bundle dan jangan menghapus tes yang ada.
- Jangan melonggarkan CSP, **kecuali** `'wasm-unsafe-eval'` pada butir 16, dan hanya setelah pelanggaran
  terbukti di mode report/browser, lengkap dengan alasannya.
- Setiap bug fix wajib punya tes regresi.

---

## Prioritas 1: konkurensi dan konsistensi data

1. **Transaksi SQLite belum konsisten.** Jalur berikut masih memakai `DB::transaction` biasa (deferred) padahal
   membaca lalu menulis, sehingga bisa kena `SQLITE_BUSY`, dan `lockForUpdate()` itu no-op di SQLite:
   `TaskController` (`store`, `update`, `destroy`, `destroySeries`), `LoanController` (`store`, `cancelRepayment`),
   `ObligationController::cancelPayment`, `RegisteredUserController`, `CreateAdminUser`.
   Ganti ke `Db::immediate`, atau **[VERIFIKASI]** cek apakah Laravel 13 mendukung opsi `transaction_mode`
   pada koneksi sqlite di `config/database.php`. Kalau ya dan terbukti lewat tes, pilih konfigurasi itu agar
   semua `DB::transaction` seragam, lalu putuskan apakah `Db::immediate` masih perlu dipertahankan.
   Catat juga: `BEGIN` manual membuat `transactionLevel()` tetap 0 dan melewati mekanisme afterCommit Laravel.
   Tes: dua penulisan bersamaan ke jalur yang diubah tidak menghasilkan 500.
2. **Klik ganda pada check-in kebiasaan.** `HabitController::checkIn` memakai `updateOrCreate` padahal ada unique
   `(habit_id, log_date)`; dua request cepat bisa memicu pelanggaran unique (500). Bungkus dengan `Db::immediate`
   atau tangkap `UniqueConstraintViolationException` lalu ulangi sebagai update.
   Tes: dua check-in bersamaan pada hari yang sama menghasilkan satu log dan tidak ada 500.
3. **`TaskController::update` membaca `$wasCompleted` di luar transaksi.** Pindahkan pembacaan status dan
   `completed_at` ke dalam transaksi (dengan `Db::immediate`) agar dua pengiriman bersamaan tidak sama-sama
   menganggap tugas belum selesai. Duplikat tugas berulang sudah dicegah unique index
   `tasks_recurrence_occurrence_unique`; pastikan jalur ini tidak berujung 500 saat unique bentrok.
4. **Cek "admin aktif terakhir" tanpa lock.** `AdminController` (`toggleStatus`, `updateRole`, `destroy`) dan
   `ProfileController::destroy` menghitung admin aktif lalu bertindak tanpa transaksi. Bungkus hitung + aksi
   dalam `Db::immediate`. Tes: dua admin tidak bisa saling menonaktifkan/menghapus hingga tersisa nol admin aktif.
5. **Tanggal transaksi tidak dibatasi.** `MoneyController::validatedTransactionData` hanya memeriksa format
   `occurred_on`. Tambahkan batas wajar (misalnya tidak lebih dari satu tahun ke depan dan tidak sebelum 2000),
   dan **[TANYA DULU]** batas yang kamu mau. Terapkan juga ke penyesuaian lain yang memakai tanggal bebas.
   Tes batas atas dan bawah.

## Prioritas 2: keamanan

6. **`role` ada di `$fillable` model `User`.** Saat ini tidak bisa dieksploitasi karena semua jalur menyebut field
   eksplisit, tetapi ini menyisakan risiko eskalasi hak akses. Keluarkan `role` dari `$fillable`, lalu set eksplisit
   di `CreateAdminUser`, seeder, factory, dan tes yang membutuhkannya. Tes: request registrasi/profil yang
   menyelundupkan `role=admin` tidak mengubah role.
7. **Sisa Sanctum yang mati.** `routes/api.php` masih punya `/api/user` dengan `auth:sanctum`, sementara `User`
   tidak memakai `HasApiTokens` dan middleware stateful Sanctum dikomentari. **[VERIFIKASI]** pastikan tidak ada
   yang memakainya, lalu hapus rute itu. Cek juga apakah `laravel/sanctum` dan `guzzlehttp/guzzle` di
   `composer.json` benar-benar tidak dipakai langsung (guzzle bisa tetap datang sebagai dependensi
   `minishlink/web-push`). Hapus yang tidak dipakai dan jalankan `composer audit`.
8. **Registrasi selalu terbuka.** **[TANYA DULU]** Rute `register` tidak punya saklar. Kalau aplikasi dipakai
   pribadi/keluarga di server publik, usulkan opsi seperti `REGISTRATION_ENABLED` (default sesuai keputusanku)
   yang menutup rute dan menyembunyikan tautan daftar. Jangan implementasikan sebelum aku jawab.

## Prioritas 3: operasional dan repo

9. **Backup memuat seluruh arsip ke memori.** `EncryptedSqliteBackup::create()` memakai `file_get_contents`,
   `gzencode`, lalu `openssl_encrypt` sekaligus (beberapa salinan di RAM), dengan batas default 512 MB.
   Ukur penggunaan memori pada DB sintetis besar. Jika bermasalah, rancang format terenkripsi berversi
   (misalnya `JDBAK2`, GCM per-potongan dengan nonce turunan) **sambil tetap bisa memulihkan `JDBAK1`**.
   Jika tidak, minimal tambahkan pemeriksaan ukuran sebelum membaca dan dokumentasikan kebutuhan `memory_limit`.
   Tes: roundtrip create/restore untuk kedua versi, dan restore menolak arsip yang dirusak.
10. **Salinan backup offsite (dari butir lama #22).** `EncryptedSqliteBackup` sudah baik tetapi hanya lokal;
    `docs/BACKUP.md` baru menyarankan rclone/S3 tanpa perintahnya. Tambahkan perintah atau langkah terjadwal yang
    menyalin arsip terenkripsi ke lokasi terpisah (konfigurasi lewat env, tanpa kunci enkripsi ikut tersalin),
    dengan status/kegagalan masuk `app:backup-status`. Dokumentasikan latihan restore (drill) langkah demi langkah.
    **[TANYA DULU]** tujuan offsite yang kupakai (S3, Google Drive, server lain).
11. **Aset Tesseract membengkakkan repo.** `public/vendor/tesseract` sekitar 12,9 MB dari total sekitar 15,1 MB isi repo.
    `scripts/copy-ocr-assets.mjs` sudah bisa menghasilkannya. **[TANYA DULU]** pilih: (a) keluarkan dari git, ignore
    folder itu, dan jalankan skrip saat build/deploy, atau (b) Git LFS. Jika (a), pastikan alur deploy dan
    `docs/DEPLOY.md` mencantumkannya (skrip mengunduh `ind.traineddata` dari GitHub, jadi butuh jaringan saat build),
    dan scan struk tetap jalan setelah build bersih.
12. **Pemeriksaan CI.** Arsip yang kuterima tidak punya folder `.github/`, padahal dokumen menyebut workflow CI.
    **[VERIFIKASI]** cek apakah workflow ada di repo asli. Jika tidak ada, buat yang menjalankan: `composer audit`,
    tes PHPUnit (PHP 8.3 dan 8.4), Pint, Larastan, `npm run build`, `npm run check:bundle`, `npm audit`,
    dan `npm run test:receipt`. Tes E2E Playwright berada di luar testsuite PHPUnit; putuskan apakah dijalankan
    sebagai job terpisah.
13. **Default konfigurasi yang menyesatkan.** `config/database.php` memakai fallback `mysql` sementara aplikasi
    dan `.env.example` memakai SQLite; ubah fallback ke `sqlite`. Pertimbangkan `SESSION_DRIVER=database` di
    contoh produksi pada `docs/DEPLOY.md` (sesuai `docs/KEAMANAN.md`). Jangan mengubah `.env.example` untuk lokal
    kecuali perlu.
14. **Verifikasi di server (dari butir lama #23).** Ini perlu aku lakukan di luar repo; kamu bantu menyiapkan
    daftar perintah cek yang jelas di `docs/DEPLOY.md`: cron `schedule:run` terpasang, konfigurasi `MAIL`
    terisi dan `app:send-daily-digest` terkirim ke akun uji, run GitHub Actions pada checkout bersih, dan
    konfigurasi Apache production. Jangan menandai butir ini selesai.

## Prioritas 4: fitur foto struk dan tampilan

15. **Uji foto struk di perangkat nyata (dari butir lama #25).** Modul sudah terpasang dan ter-wire.
    Yang tersisa: uji di Chrome Android dan Safari iOS dengan 5 sampai 10 struk asli, laporkan struk mana yang
    salah terbaca (total, tanggal, toko), ukur waktu OCR dan unduhan pertama (±5 MB), dan tulis hasilnya di
    `INTEGRASI.md`. Jangan menyimpan foto. Bagian yang butuh perangkat fisik, serahkan checklist-nya padaku
    dan jangan klaim sudah diuji.
16. **Jangan sampai CSP enforce mematikan scan struk.** `SecurityHeaders` belum punya `'wasm-unsafe-eval'` di
    `script-src`, padahal Tesseract membutuhkan kompilasi WASM. Di mode report, buktikan pelanggaran muncul
    saat scan. Jika terbukti, tambahkan `'wasm-unsafe-eval'` (bukan `'unsafe-eval'`), perbarui `docs/CSP.md`
    dengan alasannya, dan tambah tes header di `SecurityHeadersTest`.
17. **Satukan identitas visual (butir lama #17). [TANYA DULU] soal palet sebelum mengubah visual.**
    `tokens.css` masih punya dua palet (pastel `--y/--g/--p/--b` dan hijau `--color-primary`). Gaya `concept-*`
    dipakai di sekitar 18 view, sementara `surface-card` di sekitar 30 view. Usulkan satu sistem kartu standar
    dulu, lalu migrasi halaman satu per satu mulai dari Target.
18. **Baris daftar jadi kartu mini (butir lama #18).** Bottom sheet dan pengelompokan transaksi per tanggal
    sudah ada. Periksa daftar mana yang belum (dompet, tagihan, utang, tugas, kebiasaan), lalu samakan polanya:
    ikon kategori pastel, judul + keterangan kecil, nominal rata kanan (`tabular-nums`), aksi Ubah/Hapus di
    bottom sheet. Laporkan daftar mana yang sudah dan belum sebelum mengubah.
19. **Rapikan penamaan menu (butir lama #19).** "Pasang di HP" sudah pindah ke Pengaturan. Yang tersisa:
    Target masih berada di subnav Kebiasaan (jadikan item yang mudah ditemukan), label "Api" di halaman streak
    perlu istilah yang jelas, dan **[VERIFIKASI]** apakah "Beranda" dan "Hari ini" masih ganda.
20. **[TANYA DULU] Peringatan saldo minus (butir lama #16).** Logika `negativeWallets` sudah ada. Konfirmasi
    padaku apakah peringatan itu dipertahankan, diubah, atau dihapus. Jangan mengubah tanpa jawabanku.

## Prioritas 5: dokumentasi

21. **Sinkronkan dokumen yang basi.**
    - `docs/ARSITEKTUR.md` masih menulis Laravel 10, PHP 8.1, 59 tes, dan mencatat "user_id di fillable" sebagai
      utang teknis, padahal `Task` dan `Category` sudah tidak memilikinya. Perbarui atau tandai sebagai catatan historis.
    - Jumlah tes berbeda antar dokumen (81, 176, 263). Pakai satu angka dari run terbaru, atau hapus angkanya.
    - Pindahkan `PROMPT_JADWAL_HARIAN_SENIOR.md`, `PROMPT-PERBAIKAN-JADWAL-HARIAN.md`, dan `README_PEMILIK.md`
      ke `docs/archive/` (atau hapus). `README_PEMILIK.md` menunjuk `00_BACA_DULU.md` yang tidak ada.
    - `INTEGRASI.md` berisi langkah pemasangan yang sudah dilakukan; ubah menjadi catatan modul (cara kerja,
      batasan, hasil uji dari butir 15).
    - Rapikan `docs/PROGRESS.md` (sekitar 65 KB) menjadi ringkasan per fase; urutan bagiannya juga tidak kronologis.

---

## Laporan akhir

Per butir: status (selesai / dilewati / butuh keputusanku), file yang diubah, perintah tes yang dijalankan beserta
hasilnya, dan hal yang tidak bisa kamu verifikasi (terutama butir 14 dan 15).
