# PERBAIKAN NAVIGASI — KEMBALIKAN 5 MENU UTAMA, HALAMAN LAIN MASUK SUB-MENU

> **Cara pakai:** satu sesi baru. Tempel isi file ini saja (self-contained). Ini perbaikan atas hasil Fase 4 sebelumnya (menu "Lainnya" dihapus).

## 1. MASALAH
Setelah Fase 4, bottom-nav HP berubah menjadi: Beranda · Tugas · Kebiasaan · Keuangan · **Lainnya**, dan menu "Lainnya" berisi banyak halaman. Pemilik tidak mau itu. Aplikasi ini kebanyakan dipakai di HP, jadi menu utama harus tetap sederhana seperti semula.

## 2. KEPUTUSAN DESAIN (FINAL, JANGAN DITAWAR)
1. **Bottom-nav HP kembali tepat 5 item, urutan tetap:** Beranda · Tugas · Kebiasaan · Keuangan · **Profil**. Tidak ada "Lainnya", tidak ada item keenam.
2. Halaman/fitur lain TIDAK jadi menu utama. Setiap halaman dimasukkan ke salah satu dari 5 menu itu sebagai **sub-menu (tab) di dalam halaman induknya**, sesuai fungsi/tujuannya.
3. Tombol **+** (FAB) dan bottom-sheet "Mau tambah apa?" tetap, dan tetap mencakup semua jenis data.
4. Pencarian tetap ikon di header. Notifikasi/"Perlu perhatian" tetap ikon di header.
5. Nama rute yang sudah ada TIDAK diubah, tidak ada fitur baru, tidak ada logika bisnis yang diubah. Ini murni penataan navigasi.

## 3. PEMETAAN HALAMAN → MENU INDUK

| Menu induk | Sub-menu (tab) di dalamnya | Rute yang dipakai |
|---|---|---|
| **Beranda** | Hari ini · **Kalender** · Perlu perhatian | `user.dashboard`, `user.calendar` (+ panel/anchor "Perlu perhatian") |
| **Tugas** | Daftar · (Kalender tugas bila sudah ada di halaman tugas, tautkan ke tab Kalender Beranda dengan filter tugas) | `user.tasks.*` |
| **Kebiasaan** | **Kebiasaan** · **Target** | `user.habits.*`, `user.goals.*` |
| **Keuangan** | Ringkasan · Transaksi · Dompet · **Tagihan** · **Utang-Piutang** · Laporan | `user.money.index` (+ tab), `user.obligations.index`, `user.loans.index`, `user.reports.index` |
| **Profil** | Profil · **Pengaturan** · (Panel Admin bila role admin) · Keluar | `profile.edit`, `settings.edit`, `admin.*` |

Catatan pemetaan:
- Kalender bersifat gabungan (tugas, kebiasaan, target, agenda), jadi paling cocok di **Beranda**.
- Target (goals) terhubung ke kebiasaan dan dompet; letakkan di **Kebiasaan** (tab "Target"), dan tampilkan juga ringkas di Beranda.
- Tagihan/cicilan dan utang-piutang adalah urusan uang → **Keuangan**.
- Pengaturan dan Panel Admin → **Profil**.
- Jika menurut audit ada halaman lain yang belum terpetakan, masukkan ke menu induk yang fungsinya paling dekat dan catat keputusannya.

## 4. LANGKAH KERJA

### 4.1 Periksa kondisi aktual (jangan tebak)
Baca `resources/views/layouts/*` (terutama layout user/base), semua komponen navigasi (`mobile-nav`, sidebar, bottom-sheet "Lainnya"), `routes/web.php`, dan CSS nav. Catat: item bottom-nav sekarang, isi sheet/halaman "Lainnya", dan rute yang hanya terjangkau lewat "Lainnya".

### 4.2 Bottom-nav kembali ke 5 item
- Hapus item/halaman/sheet "Lainnya". Item ke-5 = **Profil** (ikon profil / avatar kecil, label "Profil").
- Item aktif memakai pencocokan **berbasis grup rute**, jadi menu induk tetap menyala saat berada di sub-halamannya:
  - Beranda aktif untuk `user.dashboard`, `user.calendar`, `user.search`.
  - Tugas aktif untuk `user.tasks.*`.
  - Kebiasaan aktif untuk `user.habits.*`, `user.goals.*`.
  - Keuangan aktif untuk `user.money.*`, `user.obligations.*`, `user.loans.*`, `user.reports.*`.
  - Profil aktif untuk `profile.*`, `settings.*`, `admin.*`.
- Pertahankan `aria-current="page"`, indikator geser halus (token motion yang ada), target sentuh ≥ 44 px, safe-area bawah, dan padding bawah konten agar FAB dan konten terakhir tidak tertutup.
- Badge angka kecil pada item bila ada yang perlu perhatian (mis. Keuangan: tagihan jatuh tempo; Tugas: terlambat) — hanya jika datanya sudah tersedia di `AgendaService`; jangan menambah query berat (eager load / cache ringan).

### 4.3 Komponen sub-menu (tab) yang dipakai semua halaman induk
Buat/lengkapi komponen Blade `<x-subnav>` (bukan JavaScript berat):
- Tampilan: deretan tab horizontal di bawah judul halaman, **bisa digeser** di HP (`overflow-x: auto`, `scroll-snap`, tanpa scrollbar terlihat, fade di tepi bila ada tab tersembunyi), tab aktif otomatis di-scroll ke tengah saat halaman dibuka.
- Setiap tab = **tautan biasa ke URL sendiri** (progressive enhancement; jalan tanpa JS, bisa di-bookmark, tombol Back berfungsi). Tab aktif memakai `aria-current="page"`; wadah `<nav aria-label="Sub-menu {Induk}">`.
- Gaya konsisten dengan sistem tombol/chip yang ada: ikon kecil + label, tinggi ≥ 44 px, indikator garis bawah/pil yang bergeser halus antar tab (token `--dur-base`, `--ease-standard`; hormati `prefers-reduced-motion`). Tidak ada gaya baru di luar token.
- Sticky tepat di bawah header saat scroll (`position: sticky; top: <tinggi header>`), latar surface + garis bawah tipis, tidak menutupi judul.
- Props: `items` (label, ikon, rute, pola-aktif, badge opsional), `label` aksesibilitas.
- Transisi antar-tab memakai View Transition/fade yang sudah ada dari Fase 5 (konten berganti, sub-nav tetap diam via `view-transition-name`).
- Terapkan di: Beranda, Kebiasaan, Keuangan, Profil. Untuk Tugas yang hanya punya satu tab, jangan tampilkan sub-nav kosong (cukup header halaman biasa).

### 4.4 Rapikan halaman induk
- **Beranda:** header halaman + sub-nav `Hari ini · Kalender · Perlu perhatian`. Halaman kalender yang sudah ada (`user.calendar`) tampil sebagai tab Kalender (URL tetap). "Perlu perhatian" menjadi tab/panel yang membuka daftar tugas terlambat, tagihan jatuh tempo, utang jatuh tempo (dari `AgendaService`); ikon notifikasi di header mengarah ke tab ini.
- **Kebiasaan:** sub-nav `Kebiasaan · Target`. Halaman Target (`user.goals.index`) tampil sebagai tab; tombol tambah/edit mengikuti pola form yang sudah ada.
- **Keuangan:** sub-nav `Ringkasan · Transaksi · Dompet · Tagihan · Utang-Piutang · Laporan`. Pastikan tiap tab punya URL sendiri (rute turunan atau query `?tab=` yang sudah dibuat di Fase 4) dan `reports.index` menampilkan Laporan sungguhan (bukan duplikat halaman Keuangan). Judul, tombol aksi utama (mis. "Catat transaksi"), dan empty state per tab.
- **Profil:** sub-nav `Profil · Pengaturan` (+ `Panel Admin` hanya untuk admin). Tombol **Keluar** ditaruh jelas di halaman Profil (tombol danger-ringan dengan dialog konfirmasi), bukan di bottom-nav. Tambahkan juga di sini bila sudah ada: "Pasang aplikasi", tautan bantuan.
- Halaman dalam (tambah/edit) tetap memakai tombol "Kembali" yang mengarah ke tab induknya, bukan ke beranda.

### 4.5 Sidebar desktop
Tetap menampilkan 5 grup yang sama dengan anak-anaknya (Beranda → Hari ini, Kalender; Tugas; Kebiasaan → Kebiasaan, Target; Keuangan → Ringkasan, Transaksi, Dompet, Tagihan, Utang-Piutang, Laporan; Profil → Profil, Pengaturan). Grup induk dapat diciutkan; grup yang sedang aktif terbuka otomatis. Urutan & istilah harus identik dengan sub-nav HP.

### 4.6 Bottom-sheet FAB "Mau tambah apa?"
Tetap sama, berisi: Tugas, Kebiasaan, Catat uang, Target, Tagihan/Cicilan, Utang-Piutang (ikon + satu kalimat penjelasan). Pastikan setiap opsi mengarah ke form/sheet yang benar dan setelah simpan kembali ke tab induknya.

### 4.7 Bersih-bersih
- Hapus kode, CSS, dan view yang hanya dipakai "Lainnya" (tanpa menghapus rute apa pun).
- Tidak ada tautan mati; semua halaman lama tetap terjangkau (cek dengan daftar rute dari `php artisan route:list`).
- Perbarui `docs/DESIGN_SYSTEM.md` (komponen `x-subnav`, aturan 5 menu utama, tabel pemetaan di atas) dan `/styleguide`.

## 5. ATURAN
- Jangan ubah nama rute, logika bisnis, atau skema DB. Jangan menambah item bottom-nav lagi di masa depan: fitur baru harus dimasukkan sebagai sub-menu ke salah satu dari 5 induk (tulis aturan ini di `docs/DESIGN_SYSTEM.md`).
- Mobile-first (360×640 dan 390×844), tetap rapi di 768 dan 1280.
- Konsisten dengan sistem yang ada: `x-button`, token warna/motion, pesan Indonesia, "kamu".
- Tanpa inline `onclick/style` baru dan tanpa `x-data` ekspresi inline baru (kompatibel dengan CSP Fase 7: pakai `Alpine.data` bila butuh JS).
- Aksesibilitas: landmark `nav` berlabel, `aria-current`, fokus terlihat, urutan tab logis, kontras AA.

## 6. TES & VERIFIKASI
1. `php artisan test` hijau. Tambah tes: (a) tiap rute di tabel pemetaan merender 200 dan memuat `<x-subnav>` dengan tab aktif yang benar; (b) bottom-nav berisi tepat 5 tautan dengan tujuan Beranda/Tugas/Kebiasaan/Keuangan/Profil; (c) menu induk aktif untuk semua rute anaknya; (d) tab Panel Admin hanya muncul untuk admin; (e) tidak ada teks "Lainnya" di nav.
2. `npm run build` bersih. 
3. Uji manual 360, 390, 768, 1280: pindah antar tab, tombol Back, bookmark URL tab, geser tab horizontal, tab aktif ter-scroll ke tengah, mode gelap, reduced-motion.
4. Pastikan FAB tidak menutupi tombol/konten dan sub-nav tidak menutupi judul.

## 7. CHECKLIST SELESAI
- [ ] Bottom-nav = 5 item (Beranda, Tugas, Kebiasaan, Keuangan, Profil); "Lainnya" hilang total.
- [ ] Kalender ada di Beranda; Target di Kebiasaan; Tagihan, Utang-Piutang, Laporan di Keuangan; Pengaturan & Admin di Profil.
- [ ] `<x-subnav>` dipakai konsisten (scrollable, URL sendiri, aktif jelas, animasi halus).
- [ ] Menu induk menyala benar di semua sub-halaman; sidebar desktop selaras.
- [ ] Semua rute lama masih terjangkau; tidak ada tautan mati.
- [ ] Tes baru hijau, build bersih, docs diperbarui.
- [ ] Ringkasan di `docs/PROGRESS.md`: apa yang dipindah, keputusan pemetaan, dan hal yang perlu keputusan pemilik.
