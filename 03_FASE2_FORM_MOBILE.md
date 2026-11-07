# FASE 2 — FORM YANG ENAK DIISI DI HP

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 1 selesai (`x-button`, `x-field`, dst. tersedia).
**Tujuan fase:** Semua form (tambah & edit) memakai satu pola: satu kolom, label+bantuan jelas, bar aksi menempel, tombol Batal, keyboard HP tepat, aman dari kehilangan data.
**Dilarang disentuh di fase ini:** skema DB (tanpa kolom baru), logika perhitungan; kata-kata pesan error (itu Fase 3).
**Keluaran wajib:** form seragam di Tugas, Kebiasaan, Target, Dompet, Transaksi, Kategori uang, Tagihan, Utang-Piutang, Profil, Pengaturan, Auth; quick-add bottom-sheet; halaman/sheet edit yang kurang.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-2-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---

## 2.1 Pola wajib untuk SEMUA form (tambah dan edit)
1. **Satu kolom** di HP. Dua kolom hanya ≥ 640 px dan hanya untuk pasangan yang memang berpasangan (mulai–selesai).
2. **Label selalu tampak di atas field** (bukan hanya placeholder). Placeholder = contoh isi ("Contoh: Bayar listrik"), bukan pengganti label.
3. **Setiap field punya teks bantu satu kalimat yang menjawab "ini untuk apa"**, tertaut `aria-describedby`. Tandai opsional dengan "(opsional)"; field wajib diberi tanda jelas (mis. "wajib" kecil, bukan hanya bintang).
4. **Kelompokkan** field berkaitan dalam `x-form-section` (judul + satu kalimat penjelasan). Field jarang dipakai disembunyikan di "Pengaturan lanjutan" (disclosure animatif, bukan `<details>` kasar) tetapi otomatis terbuka jika ada error di dalamnya.
5. **Tombol aksi selalu terlihat**: di HP, bar aksi menempel di bawah (`position: sticky; bottom: 0`, latar surface + garis atas, `padding-bottom: env(safe-area-inset-bottom)`), tidak tertutup bottom-nav maupun keyboard. Isi: **Batal** (secondary) + **Simpan …** (primary, label spesifik). Setiap form tambah/edit WAJIB punya Batal.
6. **Batal / tutup dengan aman**: jika ada perubahan belum disimpan (dirty), tampilkan dialog "Buang perubahan? — Perubahan yang belum disimpan akan hilang." dengan tombol "Lanjut mengisi" (primary) dan "Buang perubahan" (danger). Jika tidak ada perubahan, Batal langsung kembali. Pakai `beforeunload` hanya saat dirty. Tombol/gestur back browser juga dilindungi.
7. **Draf otomatis** (opsional tapi diutamakan untuk form panjang): simpan ke `localStorage` (dibungkus try/catch; jangan menyimpan data sensitif seperti password) dan tawarkan "Lanjutkan draf terakhir?" saat form dibuka kembali. Hapus draf setelah sukses simpan.
8. **Keyboard HP yang tepat**: email → `type=email inputmode=email autocapitalize=none autocomplete=username`; nominal → `inputmode=numeric` (atau `decimal`) + pemisah ribuan otomatis + prefiks "Rp"; nama/judul → `autocapitalize=sentences`; angka kecil (hari, target, jumlah cicilan) → `inputmode=numeric pattern="[0-9]*"`; pencarian → `type=search enterkeyhint=search`; tombol Enter di keyboard → `enterkeyhint=next` pada field tengah, `done`/`go` pada field terakhir. Semua `autocomplete` benar (`name`, `email`, `current-password`, `new-password`, `off` untuk catatan).
9. **Ukuran sentuh dan jarak**: tinggi input ≥ 48 px di HP, jarak antar field ≥ 16 px, radio/checkbox area sentuh ≥ 44 px (seluruh baris bisa ditekan).
10. **Font input ≥ 16 px** (cegah zoom iOS). Pasang `scroll-margin-top`/`scroll-margin-bottom` pada field agar saat fokus tidak tertutup header atau bar aksi; gunakan `dvh` dan (bila perlu) `visualViewport` agar layout benar saat keyboard terbuka.
11. **Pilihan sedikit → segmented/kartu, bukan `<select>`**: Jenis transaksi (Pengeluaran/Pemasukan/Pindah dana), Jenis dompet, Arah utang (Saya meminjamkan / Saya berutang), Frekuensi kebiasaan (Harian/Mingguan/Hari tertentu), Jenis target. `<select>` native tetap dipakai untuk daftar panjang (kategori, dompet, zona waktu).
12. **Field kondisional**: sembunyikan yang tidak relevan secara animatif (mis. "Dompet tujuan" hanya muncul jika jenis = Pindah dana; "Hari" hanya jika frekuensi = Hari tertentu) dan pastikan field tersembunyi tidak ikut divalidasi/terkirim.
13. **Pintasan tanggal/jam**: chip "Hari ini", "Besok", "Akhir pekan", "Minggu depan"; untuk jam: "Pagi 08.00", "Siang 12.00", "Sore 16.00", "Malam 20.00"; untuk durasi: "30 mnt", "1 jam", "2 jam" (mengisi waktu selesai otomatis dari waktu mulai). Tetap tampilkan input native sebagai cadangan.
14. **Validasi saat mengetik secukupnya**: validasi inline saat `blur` (bukan tiap ketukan); tampilkan centang hijau halus jika valid; hitung mundur karakter (mis. "12/255"). Setelah submit gagal, validasi real-time per-field agar error hilang begitu diperbaiki.
15. **Submit**: cegah double-submit (satu handler terpusat menggantikan dua handler duplikat di `app.js`), tombol masuk status loading dengan teks sesuai konteks (`data-loading-text`), spinner, `aria-busy`. Pulihkan tombol otomatis jika halaman kembali dari bfcache (`pageshow` dengan `event.persisted`) atau jika request gagal/timeout 15 dtk (tampilkan pesan "Koneksi lambat. Coba lagi?" dengan tombol Coba lagi, data form tetap utuh).
16. **Setelah gagal validasi**: tampilkan `x-error-summary` di atas form (fokus otomatis, `role=alert`, judul spesifik: "Tugas belum tersimpan. Periksa 2 isian berikut:"), tiap item adalah tautan yang melompat dan memfokuskan field. Pindahkan fokus ke field pertama yang bermasalah dan gulir halus ke sana. Nilai yang sudah diisi tetap ada (`old()`), kecuali password.
17. **Setelah sukses**: redirect + toast spesifik ("Tugas "Bayar listrik" tersimpan."), bila relevan dengan tombol "Urungkan" (hanya untuk aksi yang MEMANG sudah bisa dibalik di app: batal check-in, batal pembayaran, tandai tugas belum selesai; penghapusan permanen tidak punya Urungkan kecuali Modul Soft-delete di Fase 10 dikerjakan) atau "Lihat" (menuju item).

## 2.2 Pola untuk Tambah cepat (quick add)
- Halaman Keuangan: pecah menjadi sub-tab (Ringkasan · Transaksi · Dompet · Tagihan · Utang · Laporan). Form "Catat transaksi", "Tambah dompet", "Tambah kategori" berpindah ke **bottom-sheet** (HP) / dialog (desktop) yang dibuka dari tombol "Catat transaksi" (ikon +). Isi sheet: field utama dulu (jenis, nominal, dompet, tanggal), sisanya di "Pengaturan lanjutan". Sheet punya tombol Tutup (ikon, `aria-label`), Batal, dan Simpan; bisa ditutup dengan geser ke bawah, tombol Esc, dan ketuk latar (dengan perlindungan dirty).
- Bottom-sheet FAB global ("Mau tambah apa?") diperluas: Tugas, Kebiasaan, Catat uang, Target, Tagihan/Cicilan, Utang-Piutang — masing-masing ikon + satu kalimat penjelasan.
- Tambahkan halaman/sheet **edit** (diizinkan sebagai pengecualian aturan 1; hanya mengubah data yang sudah ada, tanpa kolom baru) untuk: transaksi, dompet (ganti nama), kategori uang, tagihan/cicilan, utang-piutang. Tiap edit punya Batal, dan tiap hapus punya dialog konfirmasi yang menyebut apa yang akan hilang ("Hapus transaksi Rp 50.000 pada 3 Nov? Saldo dompet akan dihitung ulang.").

## 2.3 Isi form: label & teks bantu (PAKAI KOLOM YANG SUDAH ADA)
Untuk setiap form di bawah: perbaiki label & teks bantu satu kalimat "ini untuk apa". **Jangan menambah kolom/atribut baru di fase ini.** Atribut tambahan yang disarankan dikumpulkan di Fase 10 (Modul A) dan baru dikerjakan atas persetujuan pemilik.

**Tugas** (kolom sekarang: judul, deskripsi, kategori, status, mulai, selesai, jatuh tempo)
- Judul: bantuan "Tulis apa yang harus dikerjakan, singkat saja." Placeholder "Contoh: Kirim laporan ke dosen".
- Catatan: "Tambahkan detail, tautan, atau langkah-langkah (opsional)."
- Waktu: pakai `x-datetime-quick`; validasi bentrok jadwal (sudah ada) tampil sebagai peringatan kuning yang bisa diabaikan, bukan error merah.

**Kebiasaan** (judul, deskripsi, kategori, frekuensi, hari, target mingguan, target nilai + satuan, jam pilihan, jam jadwal, mulai, berakhir)
- Bantuan per field: "Frekuensi — seberapa sering kamu ingin melakukannya", "Target nilai — jumlah yang ingin dicapai tiap kali (mis. 8 gelas)", "Satuan — gelas, menit, halaman…", "Jam pengingat".

**Target / Goals** (judul, deskripsi, kategori, tipe, nilai target, nilai awal, satuan, tenggat, status, kebiasaan terkait, dompet terkait)
- Bantuan: "Nilai awal — posisi kamu sekarang", "Nilai target — tujuan akhir", "Tautkan ke dompet agar progres tabungan otomatis".

**Dompet** (nama, jenis, saldo awal)

**Transaksi** (dompet, dompet tujuan, jenis, nominal, kategori, tanggal, catatan)
- Bantuan: "Nominal — tulis angka saja, titik ribuan otomatis", "Dompet tujuan — hanya untuk pindah dana".

**Tagihan/Cicilan (Obligation)** (jenis, judul, nominal, dompet, kategori uang, tanggal jatuh tempo, tanggal mulai, jumlah cicilan, ingatkan N hari sebelum, catatan)
- Bantuan: "Tanggal jatuh tempo — tanggal berapa tiap bulan", "Jumlah cicilan — kosongkan jika tagihan tanpa batas", "Ingatkan — berapa hari sebelum jatuh tempo".

**Utang-Piutang (Loan)** (arah, nama pihak, nominal, tanggal pinjam, jatuh tempo, dompet, catatan)
- Bantuan: arah dijelaskan lewat dua kartu pilihan: "Saya meminjamkan — orang lain berutang ke saya" dan "Saya berutang — saya wajib membayar".

**Profil & Pengaturan**
- Ganti Password: tampilkan syarat, kekuatan, tombol tampilkan/sembunyikan; hapus akun: ketik kata konfirmasi + password + dialog.

**Kategori (admin & user)**: pemilih warna yang aksesibel (nama warna + kontras teks).

## 2.4 Perbaikan JS form (terpusat, tanpa duplikat)
Buat `resources/js/forms.js` (diimpor `app.js`) yang menggantikan DUA handler submit lama di `app.js`:
- Satu pendengar `submit` terdelegasi; validasi `checkValidity()`; cegah double-submit; loading per tombol yang diklik (bukan semua tombol/`querySelector` pertama); teks loading dari `data-loading-text`, kembalikan teks aslinya bila halaman kembali dari bfcache (`pageshow` + `event.persisted`) atau request timeout 15 dtk.
- Modul dirty-state, draf `localStorage` (try/catch), error-summary fokus, auto-resize textarea, counter karakter, chip tanggal/jam.
- Ganti `onchange="this.form.submit()"` pada filter bulan dengan perilaku auto-submit via `data-auto-submit` yang ditangani `forms.js`, plus tombol "Terapkan" sebagai cadangan tanpa JS.

## Checklist selesai Fase 2
- [ ] Setiap form tambah/edit punya Batal dan bar aksi menempel (tidak tertutup bottom-nav/keyboard) di 360×640.
- [ ] Semua input font ≥ 16px; `inputmode`/`autocomplete`/`enterkeyhint` benar; tinggi ≥ 48px di HP.
- [ ] Tiap field ada label + bantuan "untuk apa"; opsional/wajib jelas.
- [ ] Halaman Keuangan tidak lagi menumpuk 3 form inline; quick-add lewat bottom-sheet; ada edit untuk transaksi, dompet, kategori uang, tagihan, utang-piutang.
- [ ] Dialog "Buang perubahan?" bekerja; double-submit tidak mungkin; tombol pulih saat kembali dari bfcache.
- [ ] Hanya satu handler submit di JS; tidak ada `onchange=`/`onclick=` inline tersisa di form.
- [ ] Tidak ada kolom DB baru ditambahkan.
