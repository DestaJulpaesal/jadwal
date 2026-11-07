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
