# PAKET PROMPT — Polish UI/UX, Form Mobile, Pesan, Animasi, Keamanan, PWA "Jadwal Harian"

> Paket ini dipecah per fase. **Jangan tempel semuanya sekaligus.** Tiap sesi: tempel file ini (`00_BACA_DULU.md`) + SATU file fase. Selesai satu fase → buka sesi baru → tempel file ini + fase berikutnya.

# 1. PERAN

Kamu adalah Senior Full-Stack Engineer + UI/UX Designer + Security Reviewer dengan pengalaman 12+ tahun membangun aplikasi Laravel produksi yang dipakai orang awam, mayoritas lewat HP. Kamu menguasai:

- Desain sistem (token, komponen, konsistensi) dan hierarki visual.
- Motion design yang halus, cepat, dan hemat baterai (bukan animasi yang memperlambat).
- Form UX mobile: keyboard HP, jempol, layar kecil, koneksi lambat.
- UX writing Bahasa Indonesia: ramah, singkat, spesifik, tidak menyalahkan.
- Aksesibilitas WCAG 2.2 AA.
- Keamanan aplikasi web (OWASP Top 10, hardening Laravel).
- Performa front-end dan back-end (Vite, Eloquent, caching).

# 2. TUJUAN

Ubah aplikasi ini menjadi aplikasi yang:

1. **Lebih berguna**: setiap layar jelas fungsinya, fitur yang sudah ada mudah ditemukan, aksi umum maksimal 2 ketukan.
2. **Lebih terstruktur**: satu sistem layout, satu sistem komponen, satu pola form, satu pola feedback.
3. **Lebih mudah dipakai di HP**: form enak diisi satu tangan, tombol jelas, keyboard muncul sesuai jenis isian.
4. **Konsisten**: tombol, ikon, animasi, transisi, jarak, dan bahasa sama di semua halaman.
5. **Terasa cepat**: tidak ada delay yang bisa dirasakan, transisi halaman mulus, tidak ada layout loncat.
6. **Jelas saat sukses/gagal**: setiap aksi punya validasi dan umpan balik yang spesifik, mudah dipahami awam.
7. **Aman**: security headers, sesi, password, otorisasi, upload, dan konfigurasi produksi di-hardening.

# 3. KONTEKS PROJECT (sudah diverifikasi)

- Aplikasi "Jadwal Harian": multi-user. Fitur: tugas, kebiasaan (check-in + streak), target (goals), keuangan pribadi (dompet, transaksi, tagihan/cicilan, utang-piutang, laporan bulanan), kalender, pencarian global, profil + foto, pengaturan, ringkasan email harian, panel admin (akun + kategori).
- Stack: Laravel 10, PHP 8.1+, Blade, Tailwind CSS 3.x + `@tailwindcss/forms`, Alpine.js 3, Vite, SQLite, bahasa Indonesia (`lang/id`). **Jangan upgrade versi mayor apa pun** (terutama Tailwind ke v4, Laravel ke 11).
- Pengguna: orang awam, kebanyakan dari HP. Target layar utama: 360×640, 390×844; tablet 768; desktop 1280.
- File acuan yang sudah ada dan WAJIB dibaca dulu: `docs/DESIGN_SYSTEM.md`, `docs/ARSITEKTUR.md`, `PROMPT_REDESAIN_UIUX.md`, `DAFTAR_IKON_CUSTOM.md`, `README.md`, `resources/css/app.css` (±2.900 baris), `resources/views/components/*`, `resources/views/layouts/*`, `lang/id/*`, `tests/*`.
- Folder `list/` di root adalah salinan lama (Laravel skeleton versi awal). Jangan diubah. Tandai di laporan bahwa folder itu sebaiknya dikeluarkan dari repo/deploy (lihat Fase 7).

# 4. KONDISI SAAT INI — MASALAH NYATA YANG HARUS DIBENAHI

Ini bukan tebakan; semuanya ditemukan di kode. Kerjakan semuanya, lalu cari sendiri yang serupa dengan grep.

## 4.1 Tombol & ikon tidak konsisten (keluhan utama pemilik)
- Di area `user.*`, ikon sengaja dibuang dan diganti teks: `toast.blade.php` (tombol "Tutup" teks), `bottom-sheet.blade.php` (tombol "Tutup" teks bergaya `btn-secondary`), `confirm-action.blade.php` dan `input-error.blade.php` (`@if (! request()->routeIs('user.*'))` menyembunyikan ikon). Hasilnya: tombol area pengguna terlihat "cuma teks", beda dengan area admin/auth yang pakai ikon. Pola `request()->routeIs('user.*')` di dalam komponen harus dihapus; komponen harus tampil sama di mana pun.
- Banyak tombol memakai `<button class="btn-primary">` mentah (contoh: halaman Keuangan "Tambah dompet", "Simpan transaksi", "Simpan kategori") tanpa ikon, tanpa `data-loading-text`, tanpa komponen `<x-primary-button>`. Ada juga `<a class="btn-secondary">` untuk "Batal" di sebagian halaman tetapi tidak ada di form inline Keuangan.
- Ada banyak kelas tombol paralel: `btn-primary/secondary/danger/icon`, `go` (login), `nav-link`, `mobile-create-button` (utility Tailwind panjang di layout), `global-search-button`, `password-toggle`. Tidak ada satu spesifikasi (ukuran, radius, ikon, state) yang berlaku.
- Tombol FAB di `layouts/user.blade.php` memakai ikon `spark` (bintang) — tidak menjelaskan fungsinya ("Tambah"). Tombol notifikasi hanya anchor ke `#attention-heading`, bukan halaman notifikasi.

## 4.2 Form belum nyaman di HP
- Halaman Keuangan (`user/money/index.blade.php`) sangat panjang: 5+ bagian + 3 form inline (dompet, transaksi, kategori) ditumpuk. Tidak ada tombol Batal, form tidak di-bottom-sheet, user harus scroll jauh untuk mencatat transaksi.
- Filter bulan memakai `onchange="this.form.submit()"` inline (tidak jelas bagi user, dan akan bentrok dengan CSP ketat).
- `resources/js/app.js` punya DUA handler submit untuk semua `<form>` (satu untuk `.user-app form`, satu lagi untuk semua form) yang sama-sama mengubah teks tombol & men-disable. Duplikat, rawan konflik, dan teks tombol diganti ke "Menyimpan..." walau tombolnya "Cari"/"Masuk".
- Pilihan seperti Jenis transaksi, Jenis dompet, Frekuensi kebiasaan memakai `<select>` biasa. Di HP, segmented control / kartu pilihan lebih cepat dan lebih jelas.
- Isian datetime-local tanpa shortcut ("Hari ini", "Besok", "Jam 09.00"). Mengisi jadwal di HP lambat.
- Atribut mobile keyboard belum ada: tidak ada `inputmode`, `enterkeyhint`, `autocapitalize`, `autocorrect` yang tepat. Pastikan semua `font-size` input minimal 16px (di iOS kurang dari itu memicu zoom otomatis). Banyak font-size di `app.css` bernilai 10–13px — periksa apakah ada yang menyentuh input/label.
- Label bantuan ("untuk apa isian ini") baru ada di sebagian form (Tugas ada; Keuangan, Utang, Tagihan hampir tidak ada).
- Create dan Edit untuk Tugas/Kebiasaan/Target sudah satu partial (`form.blade.php`), tetapi Keuangan/Tagihan/Utang tidak punya halaman edit sama sekali dan pola form berbeda-beda.

## 4.3 Navigasi & struktur belum jelas
- Menu utama hanya: Beranda, Tugas, Kebiasaan, Keuangan, Profil. Padahal ada rute `calendar`, `goals`, `obligations`, `loans`, `reports`, `settings`, `search` yang tidak ada di menu (sulit ditemukan). `reports.index` malah menunjuk ke `MoneyController@index` yang sama dengan Keuangan.
- Layout terpecah: `layouts/user`, `layouts/admin`, `layouts/guest`, `layouts/error`, plus `welcome.blade.php` dan halaman lupa-password mungkin punya `<html>` sendiri — verifikasi.
- Tiga font families di-load dari Google Fonts (Bagel Fat One, Baloo 2, Caveat, Nunito) DAN Figtree dari `@fontsource` — dua sistem font, render-blocking ke domain luar, tidak konsisten dengan `tailwind.config.js` (`Figtree`).
- Bahasa campur: "Password" vs "Kata sandi", "Tambah" vs "Catat", "Tutup" vs ikon. Tentukan satu istilah baku (lihat Fase 3, katalog pesan).

## 4.4 Validasi & pesan
- Login: hanya SATU baris `.msg` yang menampilkan `$errors->first()`. Pesan gagal selalu `"Email atau password yang Anda masukkan tidak cocok."` — baik untuk anti-enumerasi, tetapi user tidak tahu apa yang harus dilakukan. Tidak ada validasi sisi klien yang spesifik (kosong / format email salah / caps lock aktif), tidak ada hitung mundur saat terkena throttle (hanya "dalam :seconds detik" statis), akun nonaktif juga memunculkan pesan yang sama (user bingung) sementara middleware `EnsureAccountIsActive` memakai pesan lain ("Akun Anda telah dinonaktifkan.").
- Register memakai `Password::defaults()` (default Laravel = hanya min 8 karakter). Tidak ada indikator kekuatan/syarat password saat mengetik.
- `LoginRequest` tidak punya `messages()` / `attributes()` — pesan "required"/"email" jatuh ke `validation.php` generik.
- `lang/id/validation.php` `attributes` hanya mencakup sebagian field; field Keuangan/Kebiasaan/Target/Utang (mis. `amount`, `wallet_id`, `counterparty`, `due_on`, `installments_total`, `weekly_target`, `goal_value`, dst.) kemungkinan tampil sebagai nama field mentah. Cek satu per satu.
- Alert error di halaman form hanya menampilkan daftar error; tidak ada ringkasan yang bisa diklik ke field bermasalah. Toast sukses hanya berisi kalimat generik dan hilang otomatis 6 detik tanpa jeda saat disentuh/di-hover, tanpa tombol "Urungkan".
- Tidak ada penanganan error jaringan/offline sisi klien (tombol bisa menggantung di status "Menyimpan..." selamanya jika request gagal/di-back).

## 4.5 Animasi & kecepatan
- Sudah ada `user-page-in` (animasi masuk halaman 0,38 dtk), `nav-pop`, beberapa `transition` 160–200 ms, dan animasi maskot tak-berhenti (`concept-blink`, `concept-swing`, class `bobf`) — bagus karena sudah dibungkus `prefers-reduced-motion: no-preference`, tetapi: (a) animasi masuk 0,38 dtk di setiap pindah halaman terasa menunda konten, (b) tidak ada transisi antar-halaman yang nyata (hanya fade-in setelah load), (c) tidak ada skeleton/placeholder saat memuat, (d) durasi dan easing tersebar sebagai angka hard-coded di banyak tempat, bukan token.
- Setiap aksi kecil (centang tugas, check-in kebiasaan, bayar tagihan) memicu full page reload → terasa "delay" dan scroll hilang.
- Google Fonts dimuat dari CDN pada setiap halaman (render-blocking, dan menambah satu koneksi pihak ketiga).
- `Model::preventLazyLoading()` sudah aktif di non-produksi (bagus) — jangan dimatikan; pastikan tidak ada N+1 yang baru.

## 4.6 Keamanan
Yang sudah baik (pertahankan): CSRF aktif, rate limit login (5 percobaan), `throttle:60,1` pada rute tulis, `session()->regenerate()` setelah login, kebijakan (`Policy`) untuk model milik user, upload avatar dibatasi `image` + `mimes` + 5 MB dan disimpan privat, cookie `http_only` + `same_site=lax`, seeder demo hanya di local, cek akun aktif lewat middleware.

Yang perlu ditambah/diperbaiki:
- Tidak ada middleware security headers sama sekali (tidak ada CSP, `X-Frame-Options`/`frame-ancestors`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS).
- `config/session.php`: `encrypt => false`; `secure` bergantung env (`SESSION_SECURE_COOKIE` kosong); `.env.example` memakai `SESSION_DRIVER=file`, `APP_DEBUG=true`.
- Policy password lemah (`Password::defaults()` belum dikonfigurasi di `AppServiceProvider`).
- Middleware alias `auth.session` (AuthenticateSession) sudah terdaftar tetapi tidak dipakai → mengganti password tidak otomatis mengeluarkan sesi di perangkat lain.
- Operasi sensitif (hapus akun, ganti email/password) belum dilindungi `password.confirm` — verifikasi.
- Limiter login hanya berbasis `email|ip`; belum ada limiter per-IP murni (rentan password spraying dari satu IP ke banyak email).
- Login membedakan "akun nonaktif" lewat jalur lain → potensi bocor eksistensi akun.
- Admin: periksa apakah admin bisa menghapus/menurunkan role dirinya sendiri atau admin terakhir; belum ada audit log aksi admin.
- Inline `onchange=`/`style=` di banyak view menghalangi CSP ketat tanpa `'unsafe-inline'`.
- Perlu uji otorisasi (IDOR) otomatis untuk SEMUA resource milik user (Task, Habit, Goal, Wallet, Transaction, Obligation, Loan, Category).

# 5. ATURAN MAIN (WAJIB)

1. **Fase inti (0–9) TIDAK menambah fitur bisnis.** Jangan ubah logika bisnis, nama rute, nama kolom DB, atau perhitungan uang/streak/saldo. Ini polish + hardening, bukan rewrite. Pengecualian yang diizinkan di fase inti: (a) migrasi indeks DB, (b) tabel audit/log keamanan, (c) halaman/sheet edit untuk data yang sudah ada, (d) respons JSON tambahan untuk request `Accept: application/json` pada endpoint yang sudah ada. Fitur baru, kolom baru untuk data pengguna, dan kemampuan baru HANYA ada di Fase 10 (opsional) dan baru boleh dikerjakan setelah Fase 0–9 hijau dan pemilik setuju.
2. Semua teks yang terlihat user dalam Bahasa Indonesia sehari-hari. Tidak ada sisa Inggris.
3. Mobile-first. Desain dari 360 px ke atas; baru lalu tablet dan desktop.
4. Satu sumber kebenaran: semua warna, jarak, radius, bayangan, durasi, easing, z-index berasal dari CSS custom properties di `:root` (token). Dilarang angka ajaib baru di komponen.
5. Komponen dulu, halaman kemudian. Bangun/rapikan komponen Blade, lalu terapkan ke semua halaman. Dilarang menulis ulang kelas tombol/field di view; selalu pakai komponen.
6. Aksesibilitas bukan tambahan: kontras ≥ 4,5:1 (teks) / 3:1 (UI), target sentuh ≥ 44×44 px, fokus terlihat, `prefers-reduced-motion` dihormati, label terhubung ke input, error dihubungkan dengan `aria-describedby`.
7. Progressive enhancement: semua form dan aksi HARUS tetap jalan tanpa JavaScript (server-rendered, redirect + flash). JavaScript hanya mempercepat dan memperhalus.
8. JS seminimal mungkin. Pakai Alpine yang sudah ada; jangan menambah framework/library besar. Library kecil boleh jika benar-benar perlu dan dijelaskan alasannya.
9. Kerja **satu fase per sesi** (lihat Bagian 6 dan protokol sesi di tiap file fase). Setelah tiap fase: tes + build + cek manual 360/390/768/1280 px + commit + catatan serah-terima di `docs/PROGRESS.md`.
10. Jangan bertanya balik untuk hal yang bisa kamu putuskan sendiri. Ambil keputusan terbaik, catat asumsi di laporan akhir.
11. Jangan menyentuh folder `list/`, `vendor/`, `node_modules/`, `storage/`, dan file `.env`.

# 6. PETA FASE & URUTAN KERJA

Pecah per sesi supaya konteks agent tidak habis. Kerjakan BERURUTAN; tiap fase bergantung pada sebelumnya.

| File | Fase | Isi | Bergantung pada |
|---|---|---|---|
| `01_FASE0_AUDIT_REPO_CI.md` | 0 | Audit, bersihkan repo, pecah CSS, CI dasar | — |
| `02_FASE1_FONDASI.md` | 1 | Token desain, sistem tombol, komponen | 0 |
| `03_FASE2_FORM_MOBILE.md` | 2 | Pola form, quick-add sheet, label & bantuan | 1 |
| `04_FASE3_PESAN_VALIDASI.md` | 3 | Katalog pesan, flash/toast, validasi, halaman error | 1–2 |
| `05_FASE4_NAVIGASI.md` | 4 | Satu shell, menu lengkap, sub-nav Keuangan, empty state | 1–3 |
| `06_FASE5_ANIMASI_PERFORMA.md` | 5 | Animasi/transisi konsisten, kecepatan, bundle | 1–4 |
| `07_FASE6_KEAMANAN_INTI.md` | 6 | Header non-CSP, sesi, password, limiter, IDOR, upload, admin | 0 |
| `08_FASE7_CSP_BERTAHAP.md` | 7 | CSP dengan Alpine CSP-build, bertahap, bisa di-rollback | 5, 6 |
| `09_FASE8_AKSESIBILITAS_TES.md` | 8 | Aksesibilitas, tes akhir, Lighthouse | 1–7 |
| `10_FASE9_PWA_BACKUP_DEPLOY.md` | 9 | PWA, backup SQLite, keputusan DB, deploy, monitoring | 6–8 |
| `11_FASE10_OPSIONAL_FITUR_BARU.md` | 10 | Modul fitur baru (opsional, dipilih satu-satu) | 0–9 + persetujuan pemilik |
| `99_LAPORAN_DAN_SELESAI.md` | — | Format laporan akhir & definisi selesai | semua |

Fase 6 (keamanan inti) boleh dikerjakan lebih awal secara paralel dengan Fase 1–5 di branch terpisah karena hanya bergantung pada Fase 0, tetapi Fase 7 (CSP) selalu setelah Fase 5 dan 6.

**Keputusan default (jangan tanya ulang, ubah hanya jika pemilik minta):**
- Sapaan: **"kamu"** (ramah). Terapkan di seluruh app, email, dan `lang/`.
- Istilah baku: "kata sandi", "Masuk/Keluar", "Simpan", "Tambah", "Batal", "Hapus", "Ubah".
- Folder `list/`: jangan diubah; hanya ditandai di laporan.
- Database: tetap SQLite untuk sekarang, dengan backup (Fase 9) dan kriteria pindah ke MySQL/Postgres.
- CSP: bertahap lewat mode `off | report | enforce` yang bisa diatur lewat `.env` (Fase 7).
