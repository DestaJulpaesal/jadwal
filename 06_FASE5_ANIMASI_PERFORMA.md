# FASE 5 — ANIMASI, TRANSISI, DAN KECEPATAN

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 1–4 selesai (token motion tersedia, komponen & navigasi final).
**Tujuan fase:** Gerakan halus dan konsisten dari token; tidak ada delay yang terasa; metrik performa memenuhi target.
**Dilarang disentuh di fase ini:** CSP (Fase 7) — tetapi jangan menambah inline script/style baru; perilaku bisnis.
**Keluaran wajib:** `resources/js` termodul, View Transitions + prefetch, optimistic UI untuk centang/check-in, font self-host, laporan bundle & Lighthouse sebelum/sesudah.

**Pengecualian yang diizinkan:** respons JSON untuk request `Accept: application/json` pada endpoint yang sudah ada (centang tugas, check-in, batal). Tanpa JS, perilaku form POST + redirect harus tetap jalan.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-5-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---
## 5A. Animasi & transisi

### 5A.1 Prinsip
- Animasi harus **menjelaskan** (apa yang berubah, dari mana ke mana), bukan menghias. Maksimal 1 gerakan "utama" per layar pada satu waktu.
- Hanya animasikan `transform` dan `opacity` (GPU). Jangan animasikan `width/height/top/left/box-shadow` besar. Untuk buka/tutup tinggi, pakai teknik `grid-template-rows: 0fr → 1fr` atau `interpolate-size`/`height: auto` dengan fallback.
- **Tidak boleh menahan konten**: konten harus bisa dibaca dan ditekan segera. Ganti animasi masuk halaman 0,38 dtk menjadi ≤ 160 ms (fade + geser 6 px) dan jangan menyembunyikan konten sampai animasi selesai (hindari `opacity:0` awal pada konten penting; gunakan `animation-fill-mode: backwards` ringan).
- Durasi hanya dari token: micro (tekan/hover) 80–140 ms, UI (toggle, chip, tab) 140–200 ms, overlay/sheet 240–320 ms, tidak ada yang > 400 ms kecuali perayaan sekali-jalan.
- Easing: masuk `--ease-enter`, keluar `--ease-exit` (keluar lebih singkat ±70% dari masuk), perubahan posisi `--ease-standard`.
- **`prefers-reduced-motion: reduce`** (dan opsi manual `reduce_motion` di Pengaturan): matikan geser/skala/paralaks/maskot berputar; sisakan fade ≤ 100 ms. Maskot/ornamen yang bergerak terus-menerus (`bobf`, `concept-blink`, `concept-swing`) dihentikan saat tab tidak terlihat, saat `reduce`, dan di perangkat hemat daya (`@media (update: slow)`); batasi ke halaman login/beranda.
- Hindari kedipan: tidak ada FOUC tema (sudah ada skrip tema di `<head>`; pertahankan dan pastikan jalan di semua layout), tidak ada loncatan layout (set `aspect-ratio`/`min-height` untuk avatar, kartu, skeleton).

### 5A.2 Inventaris animasi yang harus ada (semua dari token)
1. **Transisi antar-halaman**: aktifkan View Transitions API lintas-dokumen (`@view-transition { navigation: auto; }`) dengan `::view-transition-old/new(root)` crossfade 140 ms; header dan bottom-nav diberi `view-transition-name` agar tetap diam sementara konten berganti. Fallback otomatis untuk browser tanpa dukungan (tanpa animasi, tetap cepat). Hormati reduced-motion.
2. **Navigasi terasa instan**: prefetch tautan internal saat `pointerdown`/`touchstart`/hover (± 65 ms) dengan skrip kecil (tanpa library); jangan prefetch rute berefek samping (hanya GET aman, hindari `logout`, `complete`, `check-in`). Tambahkan indikator progres tipis di atas layar untuk navigasi > 300 ms.
3. **Tombol**: tekan = skala 0,97 (80 ms), loading = spinner, sukses = centang singkat.
4. **Daftar**: item baru masuk dengan fade+geser 8 px (stagger ≤ 30 ms, maksimal 8 item); item dihapus mengecil+memudar lalu daftar menutup celah; item diselesaikan (centang tugas) → coret halus + pindah ke bagian "Selesai".
5. **Check-in kebiasaan & centang tugas**: ubah ke **fetch progressive-enhancement** (tanpa reload): optimistic UI (langsung berubah), kirim request di belakang, bila gagal kembalikan + toast error "Belum tersimpan. Coba lagi." dan bila sukses perbarui streak/angka dengan animasi hitung (count-up ≤ 400 ms). Tanpa JS tetap form POST biasa.
6. **Bottom-sheet/modal**: sheet naik dari bawah (`translateY(100%)→0`, 280 ms, `--ease-enter`) + latar memudar; bisa digeser turun untuk menutup; fokus dikunci (focus trap) dan dikembalikan ke pemicu; `Esc` menutup; body scroll terkunci tanpa loncatan lebar.
7. **Toast**: masuk dari bawah+fade, keluar fade+turun; progress bar tipis penanda sisa waktu (berhenti saat hover/sentuh).
8. **Form**: error muncul dengan guncangan horizontal kecil (±4 px, 200 ms) pada field + fade-in pesan; field kondisional membuka tinggi dengan transisi; centang valid muncul dengan skala-in.
9. **Progress bar & angka**: bar target/anggaran terisi dari 0 ke nilai (400–600 ms, sekali saat terlihat di viewport via `IntersectionObserver`); angka uang dihitung naik ringan.
10. **Tab/segmented**: indikator geser antar opsi; konten tab crossfade.
11. **Skeleton**: `x-skeleton` shimmer halus untuk bagian yang dimuat lambat (laporan, pencarian); jangan dipakai bila konten tersedia < 150 ms.
12. **Perayaan**: selesai target/streak milestone → animasi kecil sekali-jalan (konfeti ringan/ikon memantul ≤ 800 ms), mati saat reduced-motion.
13. **Fokus & hover**: transisi warna/bayangan 140 ms seragam, tidak ada elemen yang "meloncat" saat hover (hover hanya aktif di `@media (hover: hover)`).

## 5B. Kecepatan & tanpa delay

**Front-end**
- Hapus Google Fonts; self-host semua font lewat `@fontsource`/file lokal, subset Latin, `font-display: swap`, `preload` untuk 1–2 file kritis. Ini juga memperkuat CSP.
- Muat CSS kritis sekali lewat Vite; pecah CSS halaman login/home (`concept-b-*.css`) hanya di halaman terkait (sudah terpisah — pastikan tidak ikut termuat di halaman lain). Periksa ukuran bundle (`npm run build`), target CSS < 60 KB gzip dan JS < 80 KB gzip; laporkan sebelum/sesudah.
- `defer` untuk JS, `loading="lazy"` + `decoding="async"` + `width/height` untuk gambar/avatar, SVG sprite dipertahankan (inline sekali di layout).
- Hindari `backdrop-filter` besar dan bayangan berat di perangkat low-end; gunakan `content-visibility: auto` + `contain-intrinsic-size` untuk daftar panjang.
- Pecahkan `resources/js/app.js` menjadi modul kecil (`privacy.js`, `forms.js`, `password.js`, `nav.js`, `motion.js`, `toast.js`) yang diimpor ke `app.js`; satu pendengar event terdelegasi per jenis; tidak ada handler duplikat.
- Cache bfcache: jangan pasang `unload`/`Cache-Control: no-store` yang memutus bfcache tanpa alasan; tangani `pageshow` untuk memulihkan tombol.

**Back-end**
- Pastikan tidak ada N+1 (preventLazyLoading aktif di dev; jalankan seluruh halaman utama sekali dan perbaiki pelanggaran). Tambahkan indeks yang hilang untuk kolom yang sering difilter (`user_id + occurred_on`, `user_id + due_on`, `user_id + archived_at`, dsb.) lewat migrasi baru.
- Cache ringan (`Cache::remember`, kunci per-user, invalidasi saat tulis) untuk ringkasan bulanan/saldo bila perhitungannya berat; gunakan `select` kolom seperlunya; paginasi default 15–20.
- Produksi: `php artisan config:cache route:cache view:cache event:cache`, OPcache aktif, `SESSION_DRIVER=database` atau `redis`, `CACHE_DRIVER` bukan `file` bila banyak pengguna. Dokumentasikan di README.
- Header cache aset berhash Vite: `Cache-Control: public, max-age=31536000, immutable` (di `.htaccess`/server) dan kompresi gzip/brotli.

**Target performa (ukur dengan Lighthouse mobile, throttling 4G)**: LCP < 2,0 dtk, CLS < 0,05, INP < 200 ms, TBT < 150 ms; interaksi (ketuk → respons visual) < 100 ms.

## 5C. Hal tambahan
- Tulis `docs/MOTION.md`: tabel token durasi/easing, kapan dipakai, daftar animasi dan komponen yang memakainya.
- Pastikan setiap animasi punya padanan `prefers-reduced-motion` (uji dengan emulasi DevTools) dan opsi manual `reduce_motion` hanya jika kolomnya sudah ada (kolom baru → Fase 10); jika belum, cukup hormati preferensi OS.
- Uji di perangkat lambat (CPU throttling 4×): animasi tetap ≥ 50 fps atau dimatikan.

## Checklist selesai Fase 5
- [ ] Semua durasi/easing berasal dari token (grep angka `ms`/`cubic-bezier` di luar `tokens.css` tidak ada).
- [ ] Transisi antar-halaman aktif dengan fallback; header & bottom-nav tidak berkedip.
- [ ] Centang tugas & check-in tanpa reload (optimistic), dengan rollback + toast error bila gagal; tanpa JS tetap jalan.
- [ ] Tidak ada Google Fonts; font self-host; tidak ada FOUC tema; tidak ada layout shift.
- [ ] Bundle: CSS < 60 KB gzip, JS < 80 KB gzip (atau jelaskan bila tidak tercapai).
- [ ] Lighthouse mobile: LCP < 2,0 s, CLS < 0,05, INP < 200 ms; angka sebelum/sesudah dicatat.
- [ ] Tidak ada N+1 baru; indeks DB baru lewat migrasi baru yang reversible.
