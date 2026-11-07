# FASE 0 — AUDIT, BERSIHKAN REPO, PECAH CSS, CI DASAR

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** tidak ada.
**Tujuan fase:** Mengetahui kondisi sebenarnya, merapikan repo supaya fase berikutnya aman, dan memasang jaring pengaman (CI + tes) sebelum perubahan besar.
**Dilarang disentuh di fase ini:** logika bisnis, migrasi lama, folder `list/`, `vendor/`, `node_modules/`, `storage/`, `.env`.
**Keluaran wajib:** `docs/AUDIT_POLISH.md`, `docs/PROGRESS.md`, struktur CSS baru, workflow CI, repo bersih.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-0-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---

## 0.1 Audit (baca dulu, jangan ubah kode untuk bagian ini)
Baca semua file acuan di Bagian 3 `00_BACA_DULU.md`. Hasilkan `docs/AUDIT_POLISH.md` berisi:
- (a) Semua halaman + layout yang dipakainya (cek view yang punya tag `<html>` sendiri).
- (b) Semua varian tombol/field/alert/modal (grep `btn-`, `<button`, `class="go"`, `form-control`, `x-primary-button`).
- (c) Semua teks Inggris tersisa (grep: `Log ?in|Log ?out|Password|Submit|Cancel|Delete|Remember me|Register|Profile Information|Save`).
- (d) Semua inline `style=`, `onclick=`, `onchange=`, dan `x-data="{...}"` inline beserta lokasinya (dipakai Fase 7).
- (e) Semua field form: nama, label, helper, tipe input, atribut mobile (inputmode/autocomplete/enterkeyhint).
- (f) Rute yang tidak ada di menu; rute duplikat (mis. `reports.index` → `MoneyController@index`).
- (g) Jalankan `composer audit`, `npm audit`, `php artisan test`, `npm run build` dan catat baseline (jumlah tes lulus, ukuran bundle CSS/JS gzip, peringatan).
- (h) Jalankan semua halaman utama sekali dengan `Model::preventLazyLoading` aktif (sudah aktif non-produksi) dan catat pelanggaran N+1.
- (i) Ukur baseline Lighthouse mobile untuk login, beranda, tugas, keuangan (simpan angka untuk dibandingkan di laporan akhir).

## 0.2 Bersihkan repo (aman, tanpa mengubah perilaku)
1. Buat `docs/archive/` dan PINDAHKAN (git mv) file sisa proses desain dari root: `Jadwal Harian · Konsep B v3.html`, `Jadwal Harian · Konsep B v3.md`, `PROMPT_REDESAIN_UIUX.md`, `PROMPT_FITUR_PUSAT_KEHIDUPAN.md`. Pastikan tidak ada kode yang membacanya. `DAFTAR_IKON_CUSTOM.md` pindah ke `docs/`.
2. Folder `list/` (salinan skeleton lama, ±1,4 MB): JANGAN dihapus tanpa persetujuan. Tambahkan ke `.gitignore`? Tidak — cukup tulis di `docs/PROGRESS.md` bagian "Perlu keputusan pemilik": sarankan dihapus dari repo/deploy.
3. Periksa `.gitignore` mencakup: `.env`, `database/*.sqlite`, `storage/*.key`, `node_modules`, `vendor`, `public/build`, `public/hot`, `*.zip`. Pastikan tidak ada rahasia/akun nyata di repo (`git log -p` cepat untuk string seperti `APP_KEY=base64`, kata sandi, token).
4. `README.md`: tautan ke `docs/DESIGN_SYSTEM.md` dan `docs/ARSITEKTUR.md` harus valid; tambahkan bagian "Dokumentasi" yang menautkan `docs/PROGRESS.md`.

## 0.3 Pecah `resources/css/app.css` (±2.900 baris) tanpa mengubah tampilan
Tujuan: bisa dirawat, bukan estetika baru. Pecah menjadi file yang diimpor `app.css`:
```
resources/css/
  app.css              (hanya @tailwind + @import berurutan)
  tokens.css           (:root, dark, motion, z-index)
  base.css             (reset ringan, tipografi, fokus, reduced-motion)
  components/          (buttons.css, forms.css, alerts.css, modal.css, toast.css, badges.css, cards.css, nav.css, progress.css, skeleton.css)
  layouts/             (user.css, admin.css, guest.css)
  pages/               (home.css, login.css, tasks.css, habits.css, money.css, goals.css, calendar.css, profile.css)
```
Aturan: pindahkan per blok `@layer`, pertahankan urutan cascade, JANGAN mengganti nama kelas di fase ini (penggantian nama terjadi bertahap saat komponen diganti di Fase 1–4). Setelah memecah: `npm run build` → bandingkan ukuran bundle dan screenshot 6 halaman utama sebelum/sesudah; harus identik secara visual. File `concept-b-home.css` dan `concept-b-login.css` dipindah ke `pages/` tanpa ubah isi; sesuaikan `vite.config.js` dan `@vite` di layout.

## 0.4 CI dasar (GitHub Actions) — jaring pengaman untuk semua fase
Buat `.github/workflows/ci.yml` (jalan pada push & PR):
- PHP 8.1/8.2 (matrix), `composer install --prefer-dist --no-progress`, salin `.env.example` → `.env`, `php artisan key:generate`, buat `database/database.sqlite`.
- `vendor/bin/pint --test` (jika belum ada konfigurasi, tambahkan `pint.json` preset `laravel`; kalau banyak pelanggaran lama, jalankan `pint` sekali dalam commit terpisah "style: pint").
- `php artisan test`.
- Node 20: `npm ci`, `npm run build`.
- `composer audit` dan `npm audit --audit-level=high` (boleh `continue-on-error` untuk npm di awal, catat di laporan).
- Cache composer & npm. Status badge di README.
Jika repo bukan di GitHub, buat skrip setara `scripts/check.ps1` dan `scripts/check.sh` yang menjalankan langkah yang sama secara lokal.

## 0.5 Jaring pengaman tes sebelum perubahan besar
- Pastikan seluruh tes yang ada lulus. Jika ada yang gagal sebelum kamu mengubah apa pun, catat sebagai "gagal sejak awal" dan perbaiki hanya bila penyebabnya jelas dan kecil.
- Tambahkan **tes karakterisasi** (smoke) untuk halaman utama: tiap halaman user & admin mengembalikan 200 untuk user yang berhak, 302 ke login untuk tamu, dan 404/403 untuk akses lintas-user. Ini yang akan menjaga fase 1–8 tidak merusak rute.

## Checklist selesai Fase 0
- [ ] `docs/AUDIT_POLISH.md` lengkap (a–i) dengan angka baseline.
- [ ] Root repo bersih; arsip di `docs/archive/`.
- [ ] CSS terpecah, bundle & tampilan identik.
- [ ] CI hijau; tes smoke baru hijau.
- [ ] `docs/PROGRESS.md` berisi daftar "Perlu keputusan pemilik" (folder `list/`, kamu/Anda, DB).
