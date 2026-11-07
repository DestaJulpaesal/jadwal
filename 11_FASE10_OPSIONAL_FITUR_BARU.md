# FASE 10 — MODUL OPSIONAL: FITUR BARU (DIPILIH SATU-SATU)

> **Cara pakai file ini:** tempel `00_BACA_DULU.md` + file ini, DAN sebutkan modul mana yang dikerjakan (contoh: "Kerjakan Modul A bagian Tugas saja"). **Satu modul = satu sesi.** Jangan kerjakan semua sekaligus.

**Syarat mulai:** Fase 0–9 hijau dan pemilik sudah memilih modul. Fase ini SENGAJA terpisah karena menambah fitur/kolom (melanggar aturan "tidak menambah fitur" di fase inti).

## Aturan khusus semua modul
1. Setiap modul: migrasi baru (nullable/dengan default aman, reversible), `$fillable`/`casts`, FormRequest + pesan Indonesia, factory, seeder demo, tampilan form (pola Fase 2), tampilan daftar/detail, tes (fitur + otorisasi lintas-user), pembaruan `docs/`.
2. Fitur yang menyimpan berkas/uang/data pribadi wajib memenuhi `docs/KEAMANAN.md` (privat, kepemilikan diperiksa, tanpa log data sensitif) dan lolos CSP.
3. Jika modul terlalu berisiko atau tidak cocok dengan arsitektur, lewati dan jelaskan alasannya di laporan; jangan dipaksakan.
4. Selesai satu modul → perbarui `docs/PROGRESS.md` + `docs/DESIGN_SYSTEM.md` bila ada komponen baru.

---

## Modul A — Atribut tambahan per form
Tambahkan atribut yang berguna. Tiap bagian (Tugas, Kebiasaan, Target, Dompet, Transaksi, Tagihan, Utang-Piutang, Profil, Kategori) bisa dikerjakan di sesi sendiri.

**Tugas** (kolom sekarang: judul, deskripsi, kategori, status, mulai, selesai, jatuh tempo)
- ➕ `priority` (Rendah/Normal/Tinggi/Penting — segmented, default Normal; dipakai untuk urutan & lencana).
- ➕ `reminder_minutes_before` (Tanpa pengingat/10 mnt/30 mnt/1 jam/1 hari — untuk pengingat di digest/dashboard).
- ➕ `all_day` (boolean, "Seharian" — menyembunyikan input jam).
- ➕ `repeat_rule` (Tidak berulang/Harian/Mingguan/Bulanan) — hanya jika kamu bisa mengimplementasikannya aman; kalau tidak, cukup dokumentasikan sebagai saran.
- ➕ `completed_at` jika belum ada (untuk statistik & "Urungkan selesai").

**Kebiasaan** (judul, deskripsi, kategori, frekuensi, hari, target mingguan, target nilai + satuan, jam pilihan, jam jadwal, mulai, berakhir)
- ➕ `color`/`emoji` (pilih ikon/warna untuk dikenali cepat di daftar).
- ➕ `reminder_enabled` + `reminder_time` terpisah dari `preferred_time` (supaya jelas mana jadwal, mana pengingat).
- ➕ `note_required_on_skip` tidak perlu; cukup ➕ `skip_reason` opsional pada log "dilewati".

**Target / Goals** (judul, deskripsi, kategori, tipe, nilai target, nilai awal, satuan, tenggat, status, kebiasaan terkait, dompet terkait)
- ➕ `milestones` (titik capaian 25/50/75/100% dengan animasi perayaan kecil, hormati reduced-motion).
- ➕ `why` (alasan/motivasi singkat, tampil di kartu agar semangat).

**Dompet** (nama, jenis, saldo awal)
- ➕ `color`/`icon` (kenali cepat), ➕ `account_number_last4` (opsional, 4 digit terakhir saja untuk membedakan rekening; jangan simpan nomor penuh), ➕ `include_in_total` (boolean; dompet tabungan bisa dikeluarkan dari total), ➕ `sort_order`.

**Transaksi** (dompet, dompet tujuan, jenis, nominal, kategori, tanggal, catatan)
- ➕ `occurred_time` (jam, opsional), ➕ `merchant` / `payee` (nama toko/penerima, ikut pencarian global), ➕ `payment_method` (tunai/transfer/QRIS/kartu — opsional), ➕ `attachment` (foto struk, privat, ≤ 3 MB, disimpan di disk privat dengan validasi mime & re-encode), ➕ `is_recurring_source` (readonly, menandai transaksi hasil tagihan).

**Tagihan/Cicilan (Obligation)** (jenis, judul, nominal, dompet, kategori uang, tanggal jatuh tempo, tanggal mulai, jumlah cicilan, ingatkan N hari sebelum, catatan)
- ➕ `auto_record` (catat otomatis sebagai transaksi saat ditandai lunas), ➕ `provider` (nama penyedia, mis. PLN/Indihome), ➕ `grace_days` (toleransi terlambat).

**Utang-Piutang (Loan)** (arah, nama pihak, nominal, tanggal pinjam, jatuh tempo, dompet, catatan)
- ➕ `contact_phone` (opsional, untuk tombol "Ingatkan lewat WhatsApp" — pesan template sudah ada di `lang/id/messages.php`), ➕ `interest_note`/`agreement_note` (catatan kesepakatan), ➕ `proof` (foto bukti, privat).

**Profil & Pengaturan**
- ➕ `phone` (opsional), ➕ `locale`/`currency_display` jika relevan, ➕ `quiet_hours_start/end` (jam tenang untuk ringkasan/pengingat), ➕ `reduce_motion` (preferensi animasi manual, melengkapi `prefers-reduced-motion`), ➕ `default_landing` (halaman awal setelah login).

**Kategori (admin & user)**
- ➕ `icon`, ➕ `sort_order`.

Catatan: item bertanda ➕ yang menyangkut pengingat (`reminder_*`) bergantung pada Modul E; item `repeat_rule` bergantung pada Modul B; lampiran/bukti bergantung pada Modul C. Kerjakan modul yang diperlukan lebih dulu.

## Modul B — Tugas berulang (`repeat_rule`)
- Aturan: tidak berulang / harian / mingguan (hari tertentu) / bulanan (tanggal atau minggu ke-n) / tiap N hari; tanggal berakhir atau jumlah kemunculan.
- Rancangan aman: simpan aturan pada tugas induk; buat instance berikutnya saat instance sekarang diselesaikan ATAU lewat scheduler harian (pilih satu, dokumentasikan alasannya). Hindari membuat ratusan baris di muka.
- Tangani zona waktu (jadwal disimpan UTC, tampil sesuai zona akun), akhir bulan (31 → 30/28), dan "lewati kali ini".
- UI: pemilih ulang yang sederhana (chip + ringkasan kalimat, mis. "Tiap Senin & Kamis sampai 31 Des").
- Tes: batas bulan, tahun kabisat, DST tidak relevan (WIB) tapi zona lain ya, penyelesaian beruntun, tugas terlambat.

## Modul C — Lampiran: foto struk & bukti
- Transaksi: `attachment` (struk) ≤ 3 MB; Utang-Piutang: `proof`. Satu layanan unggah terpusat (`app/Services/PrivateUpload.php`): validasi MIME sebenarnya, dimensi maksimum, re-encode GD/Imagick (buang EXIF), nama acak, disk privat, rute penyaji yang memeriksa kepemilikan + `nosniff`.
- UI: ambil dari kamera/galeri (`accept="image/*" capture` sebagai opsi), pratinjau, tombol hapus lampiran dengan konfirmasi, progres unggah.
- Ikut backup (Fase 9B) dan ikut terhapus saat objek/akun dihapus. Tes: tidak bisa mengunduh milik user lain; file palsu ditolak; yatim piatu dibersihkan oleh `app:prune-stale-data`.

## Modul D — Ekspor data & hapus akun penuh
- "Ekspor data saya": ZIP berisi CSV/JSON per entitas (tugas, kebiasaan + log, target, dompet, transaksi, tagihan, utang-piutang), dibuat via job/antrean bila besar, tautan unduhan bertanda tangan & kedaluwarsa, wajib `password.confirm`, throttle ketat. Hormati zona waktu & format Rupiah/angka mentah.
- "Hapus akun beserta seluruh data": konfirmasi ketik kata + kata sandi, penghapusan berantai (termasuk berkas), email pemberitahuan, tes bahwa tidak ada sisa data (`Task`, `Habit`, … milik user itu nol).

## Modul E — Pengingat & notifikasi
- Tahap 1 (aman, tanpa layanan eksternal): pengingat dalam-app ("Perlu perhatian"), digest email (sudah ada) diperkaya: pengingat tugas/tagihan sesuai `reminder_*` dan "jam tenang".
- Tahap 2 (opsional): **Web Push** (VAPID) untuk PWA: tabel `push_subscriptions` (per user & perangkat), izin diminta HANYA setelah aksi pengguna ("Aktifkan pengingat"), job terjadwal yang mengirim pengingat, kelola/cabut perangkat di Pengaturan, isi notifikasi TANPA nominal bila `hide_financial_amounts` aktif. iOS hanya untuk PWA yang dipasang (dokumentasikan).
- Tes: pengingat terkirim sekali (idempoten), tidak terkirim di jam tenang, dihentikan saat tugas selesai.

## Modul F — Keamanan akun lanjutan
- 2FA TOTP (aplikasi autentikator) dengan kode pemulihan sekali pakai (di-hash), pengaturan di Profil, wajib `password.confirm`; opsional wajib untuk admin.
- Daftar sesi/perangkat aktif + "keluar dari perangkat ini". Notifikasi email saat masuk dari perangkat baru atau kata sandi diubah.
- Tes: alur aktif/nonaktif, kode salah/dipakai ulang, kode pemulihan, throttle.

## Modul G — Soft-delete + "Urungkan"
- Tambahkan `SoftDeletes` pada Task, Habit, Goal, Obligation, Loan, Transaction (hati-hati: saldo/streak/perhitungan harus mengabaikan yang terhapus; pastikan semua query & layanan `WalletBalance`, `LoanBalance`, `GoalProgress`, `HabitStreakCalculator`, `MonthlySummary` benar). Toast "Dihapus. **Urungkan**" (8 dtk) memulihkan; pembersihan permanen setelah 30 hari via `app:prune-stale-data`.
- Tes khusus: saldo dompet dan ringkasan bulanan tidak berubah salah saat hapus/pulihkan.

## Modul H — Preferensi tampilan & personalisasi
- `reduce_motion`, `default_landing`, `quiet_hours_*`, tema warna aksen/ukuran teks (kecil/normal/besar), format tanggal 24 jam. Semua tersimpan di `users` (kolom baru) dan dibaca di layout dasar.

## Modul I — Wawasan & laporan
- Laporan keuangan: tren 6 bulan, anggaran per kategori (batas bulanan + peringatan 80%/100%), perbandingan tahun lalu; laporan kebiasaan (heatmap konsistensi); ringkasan mingguan otomatis. Grafik ringan (SVG/Canvas tanpa library besar) dengan alternatif tabel untuk aksesibilitas.

## Checklist umum per modul
- [ ] Migrasi reversible, factory/seeder, tes fitur + otorisasi.
- [ ] Form mengikuti pola Fase 2, pesan mengikuti Fase 3, animasi mengikuti Fase 5.
- [ ] Lolos CSP (tanpa inline baru), aman, tercatat di `docs/`.
- [ ] Dibackup dan ikut proses hapus akun/ekspor bila relevan.
