# FASE 7 — CONTENT SECURITY POLICY — BERTAHAP & BISA DI-ROLLBACK

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 5 (JS termodul, tanpa Google Fonts) dan Fase 6 (header dasar) selesai.
**Tujuan fase:** Mengaktifkan CSP ketat tanpa merusak UI. Ini pekerjaan yang makan waktu: wajar jika butuh beberapa sesi. Hasil minimal yang bisa diterima: CSP `report` aktif + laporan pelanggaran nol di halaman utama; `enforce` hanya jika semua halaman bersih.
**Dilarang disentuh di fase ini:** tampilan & logika bisnis. Jangan mengaktifkan `enforce` di produksi sebelum seluruh halaman utama bersih di mode `report`.
**Keluaran wajib:** `config/security.php` dengan `csp_mode`, nonce terintegrasi dengan Vite, Alpine versi CSP, semua `x-data` inline dipindah ke komponen terdaftar, tidak ada inline event handler, endpoint laporan pelanggaran, `docs/CSP.md`.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-7-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---

## Kenapa dibuat bertahap
Alpine standar memakai `eval`/`new Function` untuk mengevaluasi ekspresi di atribut (`x-data="{ open: false }"`, `x-on:click="open = !open"`), sehingga memerlukan `'unsafe-eval'`. Untuk CSP ketat, pakai build **`@alpinejs/csp`**. Build ini TIDAK mendukung ekspresi bebas: tidak ada assignment inline (`open = false`), tidak ada arrow function di atribut, tidak ada literal objek kompleks. Semua logika harus dipindah ke `Alpine.data('nama', () => ({ ... }))` dan atribut hanya memanggil properti/metode sederhana (`x-on:click="toggle"`, `x-show="open"`). Perkirakan puluhan komponen (modal, dropdown, toast, konfirmasi, bottom-sheet, rupiah input, dll.).

## Langkah 7.1 — Inventaris
Dari `docs/AUDIT_POLISH.md` (butir d), buat tabel di `docs/CSP.md`: setiap `x-data/x-init/x-on/x-show/x-bind` dengan ekspresi non-trivial, `onclick=`/`onchange=`, `<script>` inline (mis. skrip tema di `<head>`), `style="..."` inline. Beri status: pindah-ke-komponen / pindah-ke-kelas / tetap-dengan-nonce.

## Langkah 7.2 — Mode & infrastruktur
- `config/security.php`: `csp_mode` dari `CSP_MODE` (`off|report|enforce`, default `off`), `csp_report_uri`.
- Middleware `SecurityHeaders` membuat **nonce per-request** (`Vite::useCspNonce()` + bagikan ke view untuk `<script nonce>` dan `<style nonce>` inline yang sengaja dipertahankan, mis. skrip tema).
- Kebijakan awal (mode `report` → header `Content-Security-Policy-Report-Only`):
  `default-src 'self'; script-src 'self' 'nonce-{n}'; style-src 'self' 'nonce-{n}'; style-src-attr 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'; report-uri /csp-report`.
  Catatan: `style-src-attr 'unsafe-inline'` sengaja dipertahankan sebagai kompromi praktis untuk atribut `style="--var: …"`; risikonya jauh lebih rendah daripada inline script. Dokumentasikan; kurangi `style=` seiring waktu.
- Lingkungan `local`: izinkan origin Vite HMR (mis. `http://localhost:5173`, `ws://localhost:5173`) HANYA saat `APP_ENV=local`.
- Rute `POST /csp-report` (throttle ketat, tanpa auth, tanpa CSRF, ukuran body dibatasi) menulis ke log khusus `storage/logs/csp.log` (rotasi harian). Jangan mencatat data pribadi.

## Langkah 7.3 — Migrasi Alpine ke build CSP
1. `npm i @alpinejs/csp` (hapus `alpinejs` biasa dari bundle; versi mayor sama). Ganti `import Alpine from 'alpinejs'` dengan `@alpinejs/csp`.
2. Buat `resources/js/components/*.js` dengan `Alpine.data(...)` untuk: `modal`, `dropdown`, `bottomSheet`, `confirmAction`, `toast`, `rupiahInput`, `passwordField`, `disclosure`, `tabs`, `dirtyForm`, `datetimeQuick`, `privacyToggle`, dst. (sesuai inventaris).
3. Ganti semua atribut ekspresi inline menjadi pemanggilan metode/properti. Event antar-komponen (`$dispatch('open-modal', name)`) tetap boleh karena itu pemanggilan fungsi sederhana — verifikasi di build CSP; jika tidak didukung, bungkus dalam metode.
4. Hapus semua `onclick=`/`onchange=` inline → `data-*` atau `x-on:` dengan metode.
5. Skrip tema di `<head>`: pindahkan ke file `theme-init.js` yang dimuat sinkron dari `'self'` (preferred), atau beri nonce.
6. Jalankan seluruh app dengan `CSP_MODE=report` di lokal; buka SETIAP halaman (user, admin, auth, error, styleguide) dan SETIAP komponen interaktif; kumpulkan pelanggaran di konsol/`csp.log`; perbaiki sampai nol.

## Langkah 7.4 — Uji regresi interaksi
Uji manual/otomatis (Playwright jika ada) setiap interaksi Alpine: buka/tutup modal & sheet, dropdown, toast, konfirmasi hapus, input rupiah, tampilkan password, disclosure, tab, filter, privasi nominal, dirty-form dialog, quick-add. Bandingkan perilaku dengan sebelum migrasi.

## Langkah 7.5 — Rilis bertahap
1. Produksi: `CSP_MODE=report` minimal beberapa hari; pantau `csp.log`.
2. Jika bersih → `CSP_MODE=enforce`. Jika ada masalah → kembali ke `report`/`off` hanya dengan mengubah `.env` (tanpa deploy ulang kode).
3. Tulis di `docs/CSP.md`: cara mengganti mode, cara membaca laporan, cara menambah sumber eksternal yang sah (hanya dengan alasan tertulis).

## Jika waktu/konteks tidak cukup
Berhenti di titik aman: CSP `report` aktif dengan daftar pelanggaran tersisa tertulis di `docs/CSP.md` dan `docs/PROGRESS.md`. Jangan paksa `enforce`.

## Checklist selesai Fase 7
- [ ] `CSP_MODE` bekerja (`off/report/enforce`) dan nonce dipasang.
- [ ] Tidak ada `'unsafe-eval'`; tidak ada `'unsafe-inline'` pada `script-src`.
- [ ] Tidak ada inline event handler; `x-data` inline non-trivial sudah jadi `Alpine.data`.
- [ ] Semua halaman utama nol pelanggaran di mode `report`.
- [ ] Tes: header CSP hadir sesuai mode; endpoint `/csp-report` ter-throttle.
- [ ] `docs/CSP.md` selesai; semua interaksi lolos uji regresi.
