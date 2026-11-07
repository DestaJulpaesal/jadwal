# FASE 8 — AKSESIBILITAS, KONSISTENSI AKHIR, DAN TES MENYELURUH

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 1–7 selesai (atau Fase 7 berhenti di mode `report` dengan catatan).
**Tujuan fase:** Memastikan semuanya benar-benar berfungsi dan konsisten di perangkat nyata, bukan hanya "terlihat benar" di kode.
**Dilarang disentuh di fase ini:** fitur baru.
**Keluaran wajib:** audit aksesibilitas, tes otomatis lengkap, tangkapan layar sebelum/sesudah, angka Lighthouse akhir.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-8-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---
## 8A. Aksesibilitas & konsistensi akhir

- Kontras (AA) semua teks/ikon/batas input; mode gelap diuji.
- Fokus: urutan tab logis, ring fokus terlihat, focus-trap pada sheet/modal, tombol "Lompat ke konten".
- Semantik: satu `<h1>` per halaman, landmark (`header/nav/main`), `aria-current="page"`, `aria-live` untuk toast & hasil pencarian, `aria-busy` saat loading, `aria-expanded` pada disclosure/menu, ikon dekoratif `aria-hidden`.
- Error form: `aria-invalid`, `aria-describedby` menunjuk ke help + error, ringkasan error fokus otomatis.
- Zoom 200% & teks besar OS tidak merusak layout; tidak ada scroll horizontal pada 320 px.
- Target sentuh ≥ 44 px, jarak antar target ≥ 8 px.
- Pastikan semua halaman memakai komponen (grep tidak boleh menemukan `class="btn-` mentah di view selain di dalam komponen).

## 8B. Tes & verifikasi

1. `php artisan test` hijau; tambahkan tes baru: pesan validasi Indonesia untuk tiap FormRequest, skenario login (kosong/format salah/salah kredensial/nonaktif/throttle/berhasil), IDOR lintas-user untuk semua resource, security headers hadir, `Password::defaults` ditegakkan, admin tidak bisa menurunkan diri/admin terakhir, upload menolak file palsu, seeder demo tidak jalan di produksi.
2. `npm run build` tanpa peringatan; laporkan ukuran bundle sebelum/sesudah.
3. Uji manual/otomatis (Playwright bila tersedia) pada 360×640, 390×844, 768×1024, 1280×800: login, daftar, buat/ubah/hapus tugas, check-in kebiasaan, catat transaksi (sheet), batalkan form dengan perubahan, kehilangan koneksi, kata sandi salah berkali-kali, akun nonaktif, sesi habis (419). Ambil tangkapan layar sebelum/sesudah untuk 6 halaman utama.
4. Lighthouse mobile: Performance ≥ 90, Accessibility ≥ 95, Best Practices ≥ 95, SEO ≥ 90 untuk halaman publik.
5. Uji dengan `prefers-reduced-motion: reduce` dan mode gelap.
6. Cek tidak ada teks Inggris/key mentah: `grep -rniE "log ?in|log ?out|password|submit|cancel|delete|remember me|register|profile information" resources lang app` (abaikan nama variabel/kode).
7. Pastikan folder `list/`, `vendor/`, `storage/` tidak berubah.

## 8C. Konsistensi lintas-halaman (periksa satu per satu)
Buat tabel di `docs/AUDIT_POLISH.md` (bagian "Hasil akhir") yang menilai TIAP halaman terhadap: tombol seragam, form seragam, header halaman seragam, empty state, pesan, animasi, kontras, keyboard, 360px, mode gelap, reduced-motion. Tidak boleh ada sel kosong atau "tidak dicek".

## Checklist selesai Fase 8
- [ ] Semua butir 8A terpenuhi (kontras, fokus, semantik, error, zoom 200%, target sentuh).
- [ ] Semua butir 8B terpenuhi (tes hijau, build bersih, manual 4 ukuran layar, Lighthouse target).
- [ ] Tabel konsistensi per halaman lengkap.
