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

Jalankan `php artisan test` dan `npm run build`, lalu mulai Fase 1 dengan token CSS dan komponen tombol berdasarkan selector yang sudah diaudit.
