# FASE 1 — FONDASI: TOKEN DESAIN, SISTEM TOMBOL, KOMPONEN

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 0 selesai (CSS sudah terpecah, CI hijau).
**Tujuan fase:** Satu sistem desain yang dipakai semua halaman; semua tombol seragam (ikon + label jelas + state lengkap).
**Dilarang disentuh di fase ini:** logika controller/model; halaman Keuangan/Tugas hanya diubah sebatas mengganti tombol & komponen mentah dengan komponen baru.
**Keluaran wajib:** token di `tokens.css`, komponen Blade baru/rapi, `/styleguide` lengkap, seluruh `<button class="btn-…">` mentah terganti.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-1-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---
## Isi pekerjaan Fase 1: token & komponen

### 1.1 Token (di `:root`, dengan override `[data-color-scheme="dark"]`)
Rapikan/lengkapi token yang sudah ada di `app.css`:
- Warna: canvas, surface, ink, muted, line, primary 50–900, success, warning, danger, info (+ varian `-soft` untuk latar alert/badge). Verifikasi kontras tiap pasangan.
- Spasi: skala 4 px (`--space-1` … `--space-12`).
- Radius: `--radius-sm/md/lg/xl/full`. Satu radius untuk tombol, satu untuk kartu, satu untuk input.
- Bayangan: `--shadow-xs/card/raised/overlay`.
- Tipografi: SATU keluarga utama (Figtree, di-self-host lewat `@fontsource`, sudah ada di package) untuk semua teks. Font dekoratif (Bagel Fat One/Baloo 2) hanya untuk judul besar bila memang bagian dari identitas; jika dipertahankan, self-host juga dan batasi bobot. Hapus `<link>` Google Fonts dari semua layout (self-host; detail performa font ada di Fase 5, tetapi tautan eksternal harus hilang di sini agar konsisten). Skala: `--text-xs 12`, `--text-sm 14`, `--text-base 16`, `--text-lg 18`, `--text-xl 20`, `--text-2xl 24`, `--text-3xl 30`. Teks isi minimal 14 px; input minimal 16 px.
- Motion: `--dur-instant 80ms`, `--dur-fast 140ms`, `--dur-base 200ms`, `--dur-slow 320ms`; `--ease-standard cubic-bezier(.2,0,0,1)`, `--ease-enter cubic-bezier(.05,.7,.1,1)`, `--ease-exit cubic-bezier(.3,0,.8,.15)`, `--ease-spring cubic-bezier(.2,1.4,.4,1)`. Ganti semua durasi/easing hard-coded di `app.css`, `concept-b-home.css`, `concept-b-login.css` memakai token ini.
- Z-index: `--z-header`, `--z-nav`, `--z-fab`, `--z-sheet`, `--z-modal`, `--z-toast`.

### 1.2 Spesifikasi tombol (SATU sistem untuk seluruh app, termasuk login, auth, admin)
Gabungkan `btn-*`, `go`, `global-search-button`, FAB, dll. ke satu keluarga:

| Varian | Dipakai untuk | Tampilan |
|---|---|---|
| `primary` | Aksi utama layar (maksimal 1 per layar/form) | Isi primary-700, teks putih, ikon + label |
| `secondary` | Aksi pendukung, Batal | Garis tipis, latar surface, ikon opsional + label |
| `ghost` | Aksi tersier (Lihat semua, Tutup) | Tanpa garis, ikon + label |
| `danger` | Hapus, nonaktifkan, keluar | Isi danger; SELALU lewat dialog konfirmasi |
| `icon` | Hanya ikon (tutup, tampilkan password, notifikasi) | 44×44 px, WAJIB `aria-label` + `title` (tooltip) |

Aturan:
- Ukuran: `md` (min-h 44 px, default), `lg` (min-h 52 px, tombol utama bawah form di HP), `sm` (min-h 36 px hanya di tabel padat desktop; di HP tetap 44 px lewat area sentuh).
- **Setiap tombol bertulis harus punya ikon di kiri + label verbal jelas** (kata kerja + objek): "Simpan tugas", "Tambah dompet", "Catat transaksi", "Batalkan", "Hapus tugas". Dilarang label ambigu: "OK", "Ya", "Kirim", "Submit", "Proses". Tombol hanya-ikon hanya boleh untuk simbol universal (tutup, tampilkan/sembunyikan password, hapus teks pencarian) dan harus ada tooltip.
- State lengkap untuk semua varian: default, hover (hanya perangkat hover), `:active` (skala 0,97 + 80 ms), `:focus-visible` (ring 2 px + offset 2 px, kontras tinggi), `disabled` (opacity .55 + `cursor-not-allowed` + alasan lewat `title`/teks bantu bila perlu), `loading` (spinner + teks "Menyimpan…", lebar tombol dikunci agar tidak loncat, `aria-busy`), `success` singkat (centang 600 ms) untuk aksi toggle.
- Buat komponen Blade: `<x-button variant size icon iconOnly href type loading-text>` yang merender `<button>` atau `<a>` sesuai `href`. `x-primary-button`, `x-secondary-button`, `x-danger-button` dipertahankan sebagai pembungkus tipis. Ganti SEMUA `<button class="btn-...">` dan `<a class="btn-...">` mentah di seluruh view.
- Tombol di dalam form: urutan di HP → Primary di atas/paling menonjol, Batal di bawahnya (full width, `flex-col-reverse` sudah dipakai, pertahankan); di desktop → rata kanan, Batal di kiri Primary.
- Perbaiki FAB: ikon `+` (tambah), label tooltip "Tambah baru", ukuran 56 px, di atas bottom nav, tidak menutupi konten terakhir (padding bawah halaman cukup). Di desktop jadi tombol "Tambah baru" (ikon + teks) di header.
- Perbaiki tombol notifikasi: jika belum ada halaman notifikasi, ganti jadi "Perlu perhatian" yang membuka panel/bottom-sheet berisi tugas terlambat, tagihan jatuh tempo, dsb. dari `AgendaService`; badge angka bila ada.

### 1.3 Komponen yang harus ada, konsisten, dan terdokumentasi di `/styleguide`
`x-button`, `x-field` (label + input + help + error + counter; satu-satunya cara membuat field), `x-select`, `x-segmented` (pilihan 2–4 opsi), `x-choice-card` (radio/checkbox berbentuk kartu dengan ikon + penjelasan singkat), `x-money-input` (sudah ada, rapikan), `x-datetime-quick` (input tanggal/jam + chip pintas), `x-textarea` (autosize + counter), `x-alert` (success/error/warning/info), `x-toast` (dengan aksi opsional), `x-form-actions` (bar tombol Simpan/Batal), `x-form-section` (fieldset + legend + deskripsi), `x-error-summary`, `x-bottom-sheet` (+ varian dialog di desktop), `x-confirm-action` (dialog konfirmasi), `x-empty-state` (ikon + judul + penjelasan + CTA), `x-skeleton`, `x-page-header` (judul + tombol kembali + aksi), `x-tabs`/`x-subnav`, `x-badge`, `x-progress-bar`, `x-stat-card`.

Hapus semua `request()->routeIs('user.*')` dari dalam komponen; kalau memang perlu beda gaya, pakai varian/prop eksplisit, bukan deteksi rute.

## Penugasan tambahan Fase 1
- Update `/styleguide` (hanya `local`) menampilkan: semua varian tombol × ukuran × state, field (semua tipe), alert/toast, dialog konfirmasi, bottom-sheet, empty state, skeleton, badge, progress, tab/segmented. Ini jadi "satu-satunya sumber kebenaran" visual.
- Ganti SEMUA tombol mentah di view dengan `<x-button>` (grep: `class="btn-`, `<button`, `class="go"`). Tombol login `go` dan `global-search-button` ikut diseragamkan.
- Hapus semua `request()->routeIs('user.*')` dari komponen.
- Tulis aturan pemakaian singkat di `docs/DESIGN_SYSTEM.md` (kapan pakai varian apa, penamaan label tombol, kapan icon-only).

## Checklist selesai Fase 1
- [ ] Tidak ada `class="btn-` / `<button class=` mentah di `resources/views` di luar komponen (grep bersih).
- [ ] Tidak ada deteksi rute di dalam komponen.
- [ ] Semua tombol punya ikon + label verbal; icon-only punya `aria-label` + `title`.
- [ ] Semua state tombol terlihat di `/styleguide`.
- [ ] Kontras AA terverifikasi untuk semua pasangan token (terang & gelap).
- [ ] Tampilan 6 halaman utama tetap rapi di 360/390/768/1280.
