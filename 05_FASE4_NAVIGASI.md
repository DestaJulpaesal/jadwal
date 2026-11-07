# FASE 4 — NAVIGASI & STRUKTUR HALAMAN

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 1–3 selesai.
**Tujuan fase:** Semua fitur yang sudah ada mudah ditemukan; satu shell layout; halaman tidak kepanjangan.
**Dilarang disentuh di fase ini:** logika bisnis/perhitungan; nama rute yang sudah ada (boleh menambah rute turunan untuk sub-tab; jangan menghapus rute lama tanpa redirect 301).
**Keluaran wajib:** `layouts/base`, menu lengkap (bottom-nav + sidebar + halaman/sheet "Lainnya"), sub-nav Keuangan, empty state & onboarding.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-4-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---
## Isi pekerjaan Fase 4

1. **Satu shell aplikasi**: gabungkan pola `layouts/user`, `layouts/admin`, `layouts/guest`, `layouts/error` menjadi layout dasar bersama (`layouts/base`) yang memuat `<head>`, tema, font, Vite, sprite ikon, toast host; layout lain hanya mengisi navigasi. Pastikan SEMUA view memakai layout ini (tidak ada `<html>` mandiri).
2. **Navigasi HP (bottom nav)**: 5 slot — Beranda, Tugas, Kebiasaan, Keuangan, **Lainnya** (menggantikan Profil). "Lainnya" membuka halaman/sheet berisi: Kalender, Target, Tagihan & Cicilan, Utang-Piutang, Laporan, Pencarian, Pengaturan, Profil, Bantuan, Keluar. Item aktif jelas (ikon terisi + label tebal + indikator geser halus — `nav-pop` sudah ada; ganti dengan indikator yang bergeser antar-tab dengan `--dur-base`).
3. **Navigasi desktop (sidebar)**: tampilkan SEMUA menu yang sama dikelompokkan: Ringkasan (Beranda, Kalender), Aktivitas (Tugas, Kebiasaan, Target), Keuangan (Ringkasan, Transaksi, Dompet, Tagihan, Utang-Piutang, Laporan), Akun (Pengaturan, Profil).
4. **Sub-navigasi Keuangan** sebagai tab/segmented yang bisa digeser horizontal di HP, dengan URL sendiri (`/dashboard/money?tab=...` atau rute turunan) sehingga halaman tidak sepanjang sekarang. Pisahkan `reports.index` ke tampilan Laporan yang benar (atau hapus rute duplikat dan arahkan).
5. **Page header seragam** (`x-page-header`): tombol kembali (ikon + teks "Kembali" di halaman dalam), judul, deskripsi satu kalimat, aksi utama di kanan. Breadcrumb ringan di desktop.
6. **Beranda**: urutkan berdasarkan kegunaan — "Perlu perhatian" (terlambat/jatuh tempo), "Hari ini", kebiasaan hari ini (check-in satu ketukan), ringkasan uang (hormati mode privasi), target aktif. Tiap kartu punya satu CTA jelas ("Lihat semua", "Tambah tugas").
7. **Empty state** di semua daftar: ikon, judul ("Belum ada tugas"), penjelasan manfaat, tombol aksi ("Tambah tugas pertama"). **Onboarding** ringan (sudah ada `onboarding.dismiss`): 3 langkah ceklis awal.
8. **Daftar**: filter sebagai chip yang bisa digeser, status tampil dengan lencana teks+ikon (bukan hanya warna), urut/cari konsisten, paginasi berbahasa Indonesia dengan tombol besar, "Muat lebih banyak" di HP bila cocok.
9. Aksi item daftar (centang, edit, hapus, arsipkan) seragam: tombol ikon dengan tooltip di desktop; di HP, tombol terlihat jelas atau menu "⋯" (ikon, `aria-label="Aksi lainnya"`) yang membuka bottom-sheet bernama (ikon + label teks). Hindari aksi tersembunyi di balik swipe saja.
10. Halaman login/daftar: buang hiasan yang menunda interaksi; fokus ke form. Hero maskot boleh ada tetapi tidak mendorong form ke bawah layar di HP 360×640 (form harus terlihat tanpa scroll).

## Checklist selesai Fase 4
- [ ] Tidak ada view dengan tag `<html>` sendiri; semua memakai `layouts/base`.
- [ ] Kalender, Target, Tagihan, Utang-Piutang, Laporan, Pengaturan, Pencarian terjangkau dalam ≤ 2 ketukan dari HP.
- [ ] Keuangan terbagi sub-tab dengan URL sendiri; `reports.index` ditangani (halaman Laporan sungguhan atau redirect 301).
- [ ] FAB berikon "+" dan bottom-sheet "Mau tambah apa?" mencakup semua jenis data.
- [ ] Tombol notifikasi membuka panel "Perlu perhatian" yang nyata (bukan anchor kosong).
- [ ] Semua daftar punya empty state dengan CTA; onboarding 3 langkah berjalan.
- [ ] Login/daftar: form terlihat tanpa scroll di 360×640.
