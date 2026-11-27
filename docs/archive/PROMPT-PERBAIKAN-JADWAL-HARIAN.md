# Prompt perbaikan: Jadwal Harian

Cara pakai: tempel ke Claude Code (atau agen coding lain yang bisa menjalankan PHP dan npm di repo).
Sebaiknya tempel **per prioritas**, bukan semuanya sekaligus. Mulai dari Prioritas 1 dan 2.
Lampirkan juga `receipt-scan.zip` saat mengerjakan butir 25.

Catatan: semua temuan di bawah berasal dari membaca kode, bukan dari menjalankan aplikasi.
Butir bertanda **[VERIFIKASI]** masih dugaan dan harus dibuktikan dulu.

---

## Konteks

Kamu bekerja di repo Laravel 13 "Jadwal Harian" (SQLite, Vite, Alpine CSP build, PWA, CSP ketat:
`script-src 'self'` + nonce, `connect-src 'self'`, `worker-src 'self'`). Aku minta perbaikan hasil review
di bawah, **bertahap**.

## Aturan kerja

- Kerjakan satu butir per commit/PR kecil. Jangan refactor di luar butir.
- Sebelum mengubah, baca kodenya dan buktikan masalahnya (tes yang gagal atau pengukuran).
  Butir bertanda [VERIFIKASI] adalah dugaan: buktikan dulu, kalau ternyata tidak terjadi, laporkan dan lewati.
- Setelah tiap butir jalankan: `php artisan test`, `vendor/bin/pint --test`, Larastan (jika ada),
  `npm run build`, `npm run check:bundle`. Jangan bilang "selesai" tanpa hasil nyata dari perintah itu.
- Jangan melonggarkan CSP, jangan menaikkan budget bundle, jangan menghapus tes yang ada.
- Teks UI berbahasa Indonesia, nada ramah, lewat `lang/id/ui.php` seperti pola yang ada.
- Setiap bug fix wajib punya tes regresi.

---

## Prioritas 1: keandalan (keluhan: klik dua kali, reload lambat)

1. `TaskController::complete()` dan `bulk()` memakai `DB::transaction` + `lockForUpdate()` (no-op di SQLite).
   Ganti ke `App\Support\Db::immediate` seperti di `MoneyController`/`LoanController`.
   Tes: dua request complete ke tugas yang sama tidak menghasilkan 500 dan tidak membuat duplikat.
2. **[VERIFIKASI]** Prefetch halaman terautentikasi (`resources/js/nav.js`, `data-prefetch`) dengan header
   `Cache-Control: private, no-cache` (`SecurityHeaders.php`) kemungkinan menggandakan request dan
   menghabiskan flash session. Ukur dengan dan tanpa prefetch. Juga ukur service worker (`public/sw.js`)
   dengan dan tanpa navigation preload. Ubah hanya jika ukuran membuktikan perbaikan.
3. Tambah tes E2E browser (Playwright) untuk: klik dua kali "Tandai selesai", navigasi kembali, offline.

## Prioritas 2: kebenaran tampilan dan build

4. `TaskController` index: angka badge filter harus memakai definisi yang **sama** dengan daftar.
   Sekarang beda: overdue (daftar `due_date < now`, hitungan `< todayStart`), today (daftar `< tomorrowStart`,
   hitungan `whereBetween` inklusif). Satukan definisi, gabungkan 5 query count jadi satu query agregat.
   Tes batas tengah malam dan tugas jatuh tempo tadi pagi.
5. Dashboard menampilkan tugas terlambat dua kali ("Perlu perhatian" dan "Tugas yang terlewat",
   `$overdueTasks` sama). Hapus duplikat. Ganti stat "Total tugas" jadi "Belum selesai hari ini".
6. `.env.example` tidak ada di repo padahal README dan `ci.yml` memakainya. Buat dan pastikan ter-track
   (`APP_*`, `DB_*`, `SESSION_DRIVER`, `MAIL_*`, `BACKUP_*`). Tanpa nilai rahasia.
7. CI frontend merah: `check:bundle` gagal (CSS 21,79 KB gzip vs budget 20). Audit selector tak terpakai
   di `resources/css/components/legacy.css` (61 KB) dan `layouts/legacy.css`, hapus yang mati.
8. **[VERIFIKASI]** `PasswordController` memanggil `Auth::logoutOtherDevices()` **sebelum** menyimpan password baru.
   Cek apakah sesi sekarang ikut keluar setelah ganti password. Jika ya, urutan yang benar: update dulu,
   lalu `logoutOtherDevices(passwordBaru)`. Tambah tes yang melakukan request kedua setelah ganti password.

## Prioritas 3: data uang (`MoneyController`, services)

9. Utang/piutang yang lewat jatuh tempo dan belum lunas hilang dari "Rencana" bulan berjalan
   (filter `isSameMonth` di index). Tampilkan sebagai "Terlambat".
10. **[VERIFIKASI]** Rencana tagihan hanya memakai `duePeriods(...)[0]`. Cek frekuensi yang didukung; jika ada
    lebih dari satu jatuh tempo per bulan, hitung semuanya.
11. UI harus menjelaskan kenapa "net bulan ini" bukan perubahan saldo (`MonthlySummary` mengecualikan
    `loan`, `loan_repayment`, `adjustment`; `WalletBalance` menghitung semuanya). Tambah teks bantu singkat.
12. `destroyWallet` hanya memeriksa transaksi; tagihan/pinjaman yang memakai dompet itu diam-diam kehilangan
    dompet (FK `nullOnDelete`). Blokir atau beri peringatan yang jelas.
13. Nama kategori peka huruf besar-kecil ("Makan" vs "makan"). Buat unik tanpa peka huruf besar-kecil
    (migrasi aman untuk data lama + validasi).
14. `MoneyController::index` menghitung semua bagian untuk semua section. Hitung hanya yang dipakai section aktif.
15. Prioritas rendah: edit transaksi ber-dompet/kategori terarsip via URL langsung memberi 404 tanpa pesan;
    ganti jadi pesan jelas. (Tombol "Ubah" sudah disembunyikan di view, jadi ini hanya akses langsung.)
16. **Tanyakan padaku sebelum mengerjakan:** peringatan saldo minus (disengaja atau tidak).

## Prioritas 4: desain dan keterbacaan

**Tanya aku dulu soal palet sebelum mengubah visual.**

17. Satukan identitas visual: `tokens.css` punya dua palet (pastel `--y/--g/--p/--b` vs hijau `--color-primary`).
    Halaman lucu (`concept-*`) hanya dashboard, Keuangan, Kebiasaan, login; sisanya polos (`surface-card`).
    Usulkan satu sistem kartu standar dulu, lalu migrasi halaman satu per satu mulai dari Target.
18. Baris daftar (transaksi, tugas, dompet, tagihan, utang): ganti pola "garis putus-putus + 2-4 tombol teks
    per baris" jadi kartu mini: ikon kategori pastel, judul + keterangan kecil, nominal rata kanan
    (`tabular-nums`). Aksi Ubah/Hapus masuk bottom sheet. Kelompokkan transaksi per tanggal.
19. Rapikan penamaan menu: "Beranda"/"Hari ini" disatukan, "Api aktivitas" diberi label yang dimengerti,
    "Aplikasi" pindah ke Pengaturan ("Pasang di HP"), Target tidak disembunyikan di bawah Kebiasaan.
20. Onboarding "Mulai dari mana?" pindah ke atas dashboard untuk akun baru, dan tambah jalur
    "Lihat panduan lagi" di Pengaturan (pesan sekarang berjanji bisa dimulai lagi tapi tidak ada rutenya).
21. Tugas berulang yang berhenti (`recurrence_until` lewat, atau mingguan tanpa hari) harus memberi tahu user.

## Prioritas 5: operasional

22. Tambah salinan backup offsite (`EncryptedSqliteBackup` sudah baik tapi hanya lokal) dan dokumentasikan drill restore.
23. Verifikasi di server: cron `schedule:run` terpasang, konfigurasi `MAIL` terisi (`SendDailyDigest`).
24. Opsional (tanya dulu): notifikasi push PWA untuk pengingat.

## Fitur baru: foto struk (gratis, di perangkat)

25. Pasang modul dari `receipt-scan.zip` yang dilampirkan (ikuti `INTEGRASI.md` di dalamnya). Setelah terpasang,
    uji di browser sungguhan (Chrome Android + Safari iOS) dengan 5-10 struk asli, laporkan struk mana yang
    salah terbaca, dan cek apakah CSP memblokir WebAssembly (jika ya, tambah `'wasm-unsafe-eval'` ke `script-src`
    dan jelaskan alasannya). Jangan menyimpan foto.

---

## Laporan akhir

Per butir: status (selesai / dilewati / butuh keputusanku), file yang diubah, perintah tes yang dijalankan
beserta hasilnya, dan hal yang tidak bisa kamu verifikasi.
