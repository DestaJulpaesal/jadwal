# Modul Foto Struk

Foto dibaca di perangkat dengan `tesseract.js` (Apache-2.0). Tidak ada kunci API, tidak ada biaya,
dan **foto tidak dikirim ke server mana pun**. Scan hanya MENGISI form; user memeriksa lalu menekan
"Simpan transaksi" seperti biasa, jadi tidak ada jalur tulis baru ke database.

## Isi paket
| File | Fungsi |
|---|---|
| `resources/js/receipt/receipt-parser.js` | Teks OCR → total, tanggal, toko, tingkat keyakinan. Murni, teruji. |
| `resources/js/receipt/image-prep.js` | Orientasi, ukuran, grayscale, kontras. |
| `resources/js/receipt/receipt-scan.js` | Memuat Tesseract (lazy, self-host), menjalankan OCR. |
| `resources/js/receipt/receipt-scan-ui.js` | Menghubungkan tombol, mengisi field, menampilkan saran. |
| `scripts/copy-ocr-assets.mjs` | Menyalin worker, core wasm, dan data bahasa `ind` ke `public/vendor/tesseract`. |
| `tests/js/*.test.mjs` | 16 tes (`node --test tests/js/*.test.mjs`). |

## Build dan deploy
- Dependency Tesseract tercatat di `package.json`.
- `npm run build` menjalankan `scripts/copy-ocr-assets.mjs`, yang menyalin worker/core lokal dan mengambil `ind.traineddata` bila belum ada cache valid.
- Aset OCR tidak dilacak oleh git dan harus tersedia saat build/deploy; checkout bersih membutuhkan akses jaringan ke GitHub untuk data bahasa.
- Jalankan `npm run test:receipt`, `npm run build`, `npm run check:bundle`, dan `npm run test:e2e`. Modul ini menambah ±5,5 KB gzip ke bundle utama;
   Tesseract sendiri dimuat lazy (chunk terpisah) hanya saat user menekan tombol.

## CSP (`app/Http/Middleware/SecurityHeaders.php`)
- `worker-src 'self'`, `connect-src 'self'`: cukup (`workerBlobURL: false` diset di kode). E2E Chromium berhasil memindai struk sintetis pada `CSP_MODE=enforce` tanpa pengecualian WebAssembly.
- Kamera lewat `<input capture>`: **tidak perlu** mengubah `Permissions-Policy`.
- Tidak ada pratinjau foto, jadi `img-src` tidak perlu `blob:`.
- WebAssembly: jangan tambahkan `'wasm-unsafe-eval'` tanpa bukti pelanggaran pada mode report/browser. E2E saat ini lulus pada CSP enforce; revisi hanya bila ada bukti baru.

## Yang SUDAH diuji
- Parser: 16 tes lulus (format rupiah, `600ML` bukan nominal, SUBTOTAL/HEMAT/PPN, tanggal ngawur/masa depan, teks sampah).
- Rantai OCR → parser di Node (tesseract.js 7.0.0, data `ind`) pada struk sintetis: struk bersih → total benar, keyakinan "high";
  foto 1000–3000 px lebar dengan struk kecil di frame → total benar; salah baca angka nol → ditandai "low" dan nilai benar ditawarkan.
- `vite build` memecah tesseract jadi chunk lazy. Skrip aset menghasilkan semua file.
- E2E Playwright: 4 tes lulus, termasuk pemindaian struk sintetis di CSP enforce.

## Yang BELUM diuji (jujur)
- **Struk asli.** Semua uji pakai gambar sintetis. Struk thermal yang pudar/kusut bisa jauh lebih buruk.
- Chrome Android dan Safari iOS pada perangkat nyata. Playwright Chromium lokal sudah menguji scan sintetis pada `CSP_MODE=enforce` dan berhasil mencapai parser; foto asli dan perangkat fisik tetap belum diuji.
- Waktu OCR dan unduhan pertama pada perangkat nyata belum diukur. Aset yang dimuat saat scan berukuran sekitar 5 MB menurut build (core 3,9 + bahasa 1,1 + worker 0,1); browser dapat menyimpannya di cache setelah unduhan awal.

## Checklist uji perangkat nyata

Jalankan di Chrome Android dan Safari iOS menggunakan 5–10 struk asli per perangkat. Jangan simpan atau unggah foto setelah pengujian.

| Perangkat/browser | Jumlah | Kesalahan total/tanggal/toko | Waktu OCR | Unduhan pertama |
|---|---:|---|---:|---:|
| Chrome Android |  |  |  |  |
| Safari iOS |  |  |  |  |

Catat jenis kesalahan dan kondisi foto (pudar, miring, terpotong, atau pencahayaan rendah), bukan gambar atau informasi identitas pada struk. Isi tabel setelah uji fisik; hasilnya belum tersedia.

## Batasan yang perlu user tahu
- Tidak ada auto-crop/luruskan ala CamScanner. Itu butuh OpenCV.js (berat, ±8 MB). Foto harus cukup dekat, terang, tidak miring.
- Struk dengan beberapa metode bayar (split payment) membuat cek silang tidak cocok → keyakinan "low".
- Yang dibaca: total, tanggal, nama toko. Rincian per item belum disimpan.
- Hasil SELALU perlu dicek user; antarmuka memfokuskan kolom nominal setelah scan.

## Langkah lanjut (opsional)
Tebak kategori dari nama toko (ingat pilihan user sebelumnya), auto-crop ringan, tes E2E browser (Playwright) untuk alur foto → isi → simpan.
