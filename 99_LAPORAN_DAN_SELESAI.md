# PENUTUP — LAPORAN AKHIR & DEFINISI SELESAI

> Tempel `00_BACA_DULU.md` + file ini di sesi terakhir (setelah Fase 0–9, dan Fase 10 jika dipilih) untuk menyusun laporan akhir.

## Format laporan akhir

Tulis `docs/LAPORAN_POLISH.md` dan ringkasannya di balasan, berisi:
1. Daftar perubahan per fase (file utama yang berubah).
2. Spesifikasi akhir tombol & komponen (tabel) + tautan ke `/styleguide`.
3. Daftar atribut baru yang ditambahkan (+ migrasi) dan yang dilewati beserta alasannya.
4. Katalog pesan final (login, daftar, CRUD).
5. Hasil ukur performa sebelum/sesudah.
6. Daftar perbaikan keamanan + hal yang masih perlu dilakukan di server (HTTPS, header di web server, cron scheduler, backup SQLite).
7. Asumsi yang kamu ambil dan hal yang perlu keputusan pemilik (mis. "kamu" vs "Anda", folder `list/`).
8. Risiko/regresi yang mungkin dan cara mengujinya.

Tambahan untuk paket ini:
9. Status tiap fase (selesai / sebagian / ditunda) dengan tautan ke commit/branch.
10. Hasil Fase 7 (mode CSP yang aktif di produksi, pelanggaran tersisa) dan Fase 9 (bukti uji restore backup, hasil uji PWA).
11. Daftar modul Fase 10 yang dikerjakan/dilewati beserta alasan.
12. Daftar "Perlu keputusan pemilik" yang masih terbuka.

## Definisi selesai

Pekerjaan dianggap selesai hanya jika: semua tombol memakai satu komponen dengan ikon+label jelas dan semua state; setiap form tambah/edit punya Batal, bar aksi menempel, keyboard HP tepat, bantuan "untuk apa" di tiap field, dan pesan error spesifik; navigasi menjangkau semua fitur; transisi halaman dan animasi konsisten dari token serta menghormati reduced-motion; tidak ada delay terasa; semua aksi punya umpan balik sukses/gagal yang jelas; security headers, sesi, kata sandi, otorisasi, dan upload sudah di-hardening dengan tes; semua tes dan build lulus; laporan akhir tersedia. Jika ada butir yang tidak bisa kamu selesaikan, katakan terus terang di laporan — jangan pura-pura selesai.

Mulai sekarang dengan **Fase 0 (audit)**, lalu lanjut berurutan sampai Fase 7 tanpa menunggu persetujuan di tiap fase.

Tambahan: backup database terjadwal dengan restore teruji, PWA installable tanpa menyimpan data pengguna di cache, CI hijau, dan setiap fase punya catatan serah-terima di `docs/PROGRESS.md`.
