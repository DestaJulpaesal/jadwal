# Progress polish

## Fase 0 — Audit, repo, CSS, CI

Status: selesai secara kode, dengan baseline test yang sudah gagal sebelum perubahan.

### Selesai

- Membuat branch `polish/fase-0-audit`.
- Memindahkan dokumen desain lama ke `docs/archive/` dan katalog ikon ke `docs/`.
- Memecah entrypoint CSS tanpa mengganti selector atau nama class.
- Memindahkan CSS landing/auth ke `resources/css/pages/`.
- Menambahkan `.github/workflows/ci.yml` untuk PHP 8.1/8.2 dan Node 20.
- Menambahkan `pint.json`.
- Menambahkan tautan dokumentasi pada README.
- Menulis hasil audit di `docs/AUDIT_POLISH.md`.
- Menambahkan smoke test redirect untuk seluruh halaman user dan admin tanpa sesi.

### Keputusan dan asumsi

- Selector CSS dipertahankan apa adanya agar fase ini tidak mengubah tampilan.
- Database tetap SQLite.
- Istilah sapaan dan standardisasi Bahasa Indonesia dikerjakan pada Fase 3.
- Folder `list/` tidak disentuh dan tetap memerlukan keputusan pemilik.

### Ditunda

- Perbaikan empat test baseline.
- Smoke test status 200 untuk setiap resource dan pengukuran N+1 berbasis browser.
- Lighthouse.
- Semua perubahan UI/UX, CSP, hardening security, PWA, dan fitur bisnis.

### Risiko regresi

- Pemisahan CSS mengubah cara Vite menghasilkan asset; build wajib dijalankan sebelum merge.
- Audit npm melaporkan tiga kerentanan moderate pada dependency dev; tidak diubah pada fase ini karena upgrade dependency di luar lingkup.

### Perlu keputusan pemilik

- Hapus atau keluarkan folder `list/` dari repository dan deployment.
- Konfirmasi pilihan database saat beban produksi meningkat.
- Konfirmasi sapaan baku “kamu” saat Fase 3 menyelaraskan teks.

### Langkah pertama fase berikutnya

Mulai Fase 1 dengan token CSS dan komponen tombol berdasarkan selector yang sudah diaudit.

## Fase 1 — Fondasi desain, tombol, dan komponen

Status: selesai secara kode; build dan format lulus. Tes lengkap mencatat 120 lulus dan 4 gagal, sama dengan baseline Fase 0.

### Selesai

- Lengkapi token warna, spasi, radius, bayangan, tipografi, motion, dan z-index; tambah pasangan warna tema gelap dan varian latar soft untuk alert/lencana.
- Self-host Figtree dan hilangkan tautan Google Fonts dari layout.
- Satukan tombol Blade untuk tombol/tautan, ukuran dan varian, state disabled/loading/success, fokus keyboard, label ikon, dan pengecekan `aria-label`/`title` untuk icon-only.
- Ganti tombol mentah pada view dengan `<x-button>`; grep tidak menemukan tombol mentah di luar komponen.
- Lengkapi komponen Blade untuk field, select, segmented, choice-card, textarea, tanggal cepat, bagian dan aksi formulir, ringkasan error, badge, statistik, tab, alert, toast, dialog, bottom-sheet, keadaan kosong, skeleton, progress, dan header halaman.
- Lengkapi `/styleguide` dengan ikon, jenis/ukuran/state tombol, kontrol formulir, pesan, keadaan kosong, progress, dialog, dan bottom-sheet.
- Hapus deteksi route `user.*` dari komponen dan dokumentasikan token, aturan tombol, serta rasio kontras tema terang/gelap.
- Tambahkan tes rendering, inferensi ikon aksi umum, dan aksesibilitas tombol ikon saja.
- Ukur halaman styleguide pada 360, 390, 768, dan 1280 px; tidak ada overflow horizontal. Cek token dan warna alert tema gelap di browser.

### Keputusan dan asumsi

- Inferensi ikon tombol membantu mempertahankan migrasi yang kompatibel; untuk makna yang tidak jelas, pemanggil tetap harus memberikan ikon eksplisit.
- Tema user gelap mempertahankan aksen emas yang sudah ada; tema gelap lain menggunakan token hijau semantik. Pasangan foreground/background keduanya diverifikasi.
- Peringatan empat tes baseline Fase 0 dipertahankan dan tidak dihapus.
- `npm run build`, Pint `--test`, Blade view cache, dan `git diff --check` lulus; seluruh empat kegagalan baseline masih ada tanpa kegagalan baru.

### Ditunda

- Empat kegagalan tes baseline, Lighthouse, dan pengukuran N+1 tetap di luar lingkup fase ini.
- Pemeriksaan manual halaman utama yang memerlukan sesi autentikasi dan audit aksesibilitas menyeluruh.
- Login/register dan halaman form spesifik akan dipoles pada Fase 2; konsistensi bahasa pada Fase 3.

### Risiko regresi

- Migrasi tombol luas dapat mengubah ukuran/warna pada konteks khusus; cek build, feature tests, dan tampilan lintas ukuran.
- Empat kegagalan baseline diketahui sejak Fase 0; bedakan dengan kegagalan baru saat validasi.

### Langkah pertama fase berikutnya

Buat branch Fase 2 dan audit seluruh create/edit form, termasuk route, partial, validasi, serta perilaku submit, sebelum mengubah pola formulir.

## Fase 2 — Form nyaman di HP

Status: selesai secara implementasi. Build, kompilasi Blade, format PHP, dan syntax JavaScript lulus. Tes lengkap: 126 lulus, 4 gagal; keempatnya sama dengan kegagalan baseline Fase 0.

### Selesai

- Memusatkan submit, pencegahan submit ganda, status loading, pemulihan bfcache, timeout/coba lagi, perlindungan perubahan belum tersimpan, ringkasan error, auto-submit filter, pintasan tanggal/jam, resize textarea, dan hitung karakter di `resources/js/forms.js`.
- Memastikan inisialisasi form dan UI tetap jalan ketika modul dimuat setelah `DOMContentLoaded`.
- Menambahkan ringkasan error, bantuan field, validasi saat blur, aksi sticky, dan perlindungan perubahan pada form tugas, kebiasaan, target, transaksi, tagihan, utang/piutang, profil, dan pengaturan.
- Mengubah jenis kebiasaan dan target menjadi pilihan segmented; menambah pintasan waktu kebiasaan serta disclosure animatif untuk pengaturan lanjutan target.
- Memindahkan quick-add transaksi, dompet, kategori, tagihan/cicilan, dan utang/piutang ke bottom-sheet. FAB global kini menyediakan keenam pintasan dan langsung membuka sheet terkait.
- Menambahkan edit transaksi dan utang/piutang. Transaksi awal utang/piutang disinkronkan sebelum pembayaran; arah, nominal, tanggal awal, dan dompet dikunci setelah pembayaran agar saldo/riwayat konsisten.
- Menambahkan pemilih warna kategori admin dengan nama warna, pratinjau teks, dan rasio kontras; penghapusan kategori/kebiasaan menggunakan dialog konfirmasi.
- Penghapusan akun kini meminta pengguna mengetik `HAPUS` serta memasukkan password. Form password menyediakan tombol tampil/sembunyi dan indikator kekuatan.
- Mengganti filter laporan inline `onchange` dengan handler terpusat dan tombol cadangan tanpa JavaScript.
- Pemeriksaan browser login/register pada 360×640, 390×844, 768×1024, dan 1280×900: tidak ada overflow horizontal; kontrol teks 16 px dan tinggi 48 px. Indikator kekuatan password merespons input.
- Tidak ada perubahan skema database. `npm run build`, `php artisan view:cache`, Pint `--test`, `node --check`, dan `git diff --check` lulus; hanya satu handler submit terdelegasi dan tidak ada atribut inline `onchange`, `onclick`, atau `onsubmit` di view.

### Keputusan dan asumsi

- Perubahan inti transaksi utang/piutang tidak boleh mengubah perhitungan setelah pembayaran tercatat; pihak, jatuh tempo, dan catatan tetap bisa diperbarui.
- Pemilih warna mencakup kategori tugas/kebiasaan admin. `money_categories` tidak memiliki kolom warna; kolom baru dilarang pada fase ini, jadi warna untuk kategori uang pengguna ditunda.
- Draf otomatis `localStorage` bersifat opsional dan tidak ditambahkan; data password tidak pernah dimasukkan ke snapshot perubahan form.
- Empat kegagalan tes baseline tetap ada pada suite lengkap: dua di `AdminUserManagementTest` dan dua di `UserTaskTest`; tidak ada kegagalan baru.

### Ditunda

- Empat kegagalan baseline dari Fase 0.
- Warna untuk kategori uang pengguna karena tidak tersedia kolom yang dapat dipakai tanpa perubahan skema.
- Pengukuran visual browser untuk halaman terautentikasi; view terkait tercakup dalam feature tests.
- Draf otomatis lokal; tidak wajib dan memerlukan kebijakan masa simpan/penghapusan yang lebih jelas.

### Risiko regresi

- Dialog dan pintasan form bergantung pada JavaScript terpusat; jalur tanpa JavaScript tetap tersedia untuk filter laporan, tetapi interaksi sheet memerlukan JavaScript.
- Pembaruan transaksi awal utang/piutang diuji sebelum dan sesudah pembayaran; tetap lakukan pemeriksaan saldo saat integrasi data produksi.

### Langkah pertama fase berikutnya

Buat branch Fase 3 dan baca `04_FASE3_PESAN_VALIDASI.md`; fokus pada penyelarasan pesan validasi tanpa mengubah validasi atau perilaku bisnis.

## Fase 3 — Pesan, validasi, dan umpan balik (sedang dikerjakan)

Status: belum selesai. Implementasi awal sudah dimulai di branch `polish/fase-3-pesan-validasi`; jangan lanjut ke Fase 4 sebelum checklist Fase 3 dipenuhi.

### Selesai sejauh ini

- Menambahkan katalog awal `lang/id/ui.php` dan melengkapi atribut validasi untuk field utama tugas, kebiasaan, target, profil, dan autentikasi.
- Menambahkan `messages()` dan `attributes()` pada lima FormRequest yang ada; pesan kepemilikan kategori tidak lagi menyebut kepemilikan.
- Memperjelas pesan login generik dan menampilkan pesan akun nonaktif hanya setelah kata sandi yang benar; kesalahan kata sandi yang salah tetap generik.
- Menambahkan petunjuk Caps Lock, tautan atur ulang kata sandi pada pesan login, pesan berhasil masuk/keluar, jeda toast saat hover/fokus, dan redirect sesi kedaluwarsa ke halaman Masuk.
- Halaman error 429/500/503 menyediakan aksi coba lagi.
- Verifikasi sementara: `npm run build`, Pint, dan cache Blade lulus; login/logout tests lulus. Suite penuh: 126 lulus dan empat kegagalan baseline yang sama dari Fase 0.
- Pemeriksaan browser halaman Masuk pada lebar 360, 390, 768, dan 1280 px tidak menemukan overflow horizontal.

### Belum selesai — jangan tandai fase ini sebagai selesai

- Helper flash terpadu belum diterapkan ke seluruh controller; `session('success')` manual masih tersebar.
- Toast belum punya antrean maksimal tiga dan aksi urungkan; pemetaan seluruh jenis feedback ke toast/alert juga belum selesai.
- Hitung mundur throttle hidup dan penonaktifan tombol Masuk belum ditambahkan.
- Respons lupa kata sandi belum diseragamkan; syarat kata sandi bocor saat pendaftaran belum ditangani.
- Offline banner/umpan balik kegagalan jaringan belum dibuat.
- Katalog pesan dan attributes belum diaudit untuk setiap aturan validasi inline/controller; tes pesan per FormRequest dan tes akses lintas pengguna belum ditambahkan.
- Istilah “kamu”/“kata sandi” belum diselaraskan di seluruh UI dan email; halaman error perlu ditinjau untuk bahasa serta aksi yang konsisten.

### Risiko dan keputusan sementara

- Akun nonaktif dibedakan hanya setelah verifikasi kata sandi cocok, sesuai kebutuhan UX tanpa mengungkap status saat kredensial salah.
- Fase ini menyentuh autentikasi dan pesan global; validasi penuh wajib dijalankan lagi setelah flash terpusat dan perubahan alur reset kata sandi.
- Empat kegagalan suite penuh saat ini tetap sama dengan baseline: dua `AdminUserManagementTest` dan dua `UserTaskTest`.

### Langkah berikutnya

Implementasikan helper flash tunggal dan migrasikan seluruh controller/layout ke payload feedback terstruktur; setelah itu tambahkan tes validasi FormRequest dan inline sebelum menyelesaikan toast, reset kata sandi, offline, dan sisa checklist Fase 3.
