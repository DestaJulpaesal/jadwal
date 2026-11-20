# Foto struk → isi form "Catat transaksi" (gratis, 100% di HP)

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

## Pasang (5 langkah)
1. Salin `resources/js/receipt/` ke proyek, dan `scripts/copy-ocr-assets.mjs` ke `scripts/`.
2. `npm i tesseract.js@7.0.0` lalu `node scripts/copy-ocr-assets.mjs`
   (mengunduh `ind.traineddata` ±1,1 MB dari GitHub; hasilnya ke `public/vendor/tesseract/`).
3. Di `resources/js/app.js`:
   ```js
   import { initializeReceiptScan } from './receipt/receipt-scan-ui';
   // di dalam initializeApplicationUi():
   initializeReceiptScan();
   ```
4. Di `resources/views/user/money/index.blade.php`, di dalam form `transaction-create`,
   tepat **sebelum** `<x-money-input ... name="amount" />`:
   ```blade
   <div data-receipt-scan class="space-y-2 rounded-lg border border-line p-3">
       <input type="file" accept="image/*" capture="environment" data-receipt-input="camera" hidden>
       <input type="file" accept="image/*" data-receipt-input="gallery" hidden>
       <div class="flex flex-wrap gap-2">
           <x-button type="button" variant="secondary" icon="tambah" data-receipt-trigger="camera">Foto struk</x-button>
           <x-button type="button" variant="secondary" data-receipt-trigger="gallery">Pilih dari galeri</x-button>
       </div>
       <p class="field-help">Struk dibaca langsung di HP kamu. Foto tidak dikirim ke mana pun. Dekatkan kamera sampai struk memenuhi layar dan pastikan terang.</p>
       <p data-receipt-status class="text-sm" role="status" aria-live="polite"></p>
       <div data-receipt-suggestions class="flex flex-wrap gap-2"></div>
   </div>
   ```
5. Jalankan `npm run test:receipt`, `npm run build`, lalu `npm run check:bundle`. Modul ini menambah ±5,5 KB gzip ke bundle utama;
   Tesseract sendiri dimuat lazy (chunk terpisah) hanya saat user menekan tombol.

## CSP (`app/Http/Middleware/SecurityHeaders.php`)
- `worker-src 'self'`, `connect-src 'self'`: sudah cukup (`workerBlobURL: false` diset di kode).
- Kamera lewat `<input capture>`: **tidak perlu** mengubah `Permissions-Policy`.
- Tidak ada pratinjau foto, jadi `img-src` tidak perlu `blob:`.
- WebAssembly: kalau di browser muncul pelanggaran CSP soal `wasm-eval`/`unsafe-eval`
  (lihat log `csp` / mode report), tambahkan `'wasm-unsafe-eval'` ke `script-src` (baris ±51).
  Ini hanya mengizinkan kompilasi WASM, bukan `eval()`.

## Yang SUDAH diuji
- Parser: 16 tes lulus (format rupiah, `600ML` bukan nominal, SUBTOTAL/HEMAT/PPN, tanggal ngawur/masa depan, teks sampah).
- Rantai OCR → parser di Node (tesseract.js 7.0.0, data `ind`) pada struk sintetis: struk bersih → total benar, keyakinan "high";
  foto 1000–3000 px lebar dengan struk kecil di frame → total benar; salah baca angka nol → ditandai "low" dan nilai benar ditawarkan.
- `vite build` memecah tesseract jadi chunk lazy. Skrip aset menghasilkan semua file.

## Yang BELUM diuji (jujur)
- **Struk asli.** Semua uji pakai gambar sintetis. Struk thermal yang pudar/kusut bisa jauh lebih buruk.
- Browser sungguhan (Chrome Android, Safari iOS), CSP di browser, dan pengisian field di modal asli.
- Waktu OCR di HP (di server ±1 detik; HP bisa beberapa kali lebih lambat). Unduhan pertama ±5 MB (core 3,9 + bahasa 1,1 + worker 0,1), setelah itu ter-cache.

## Batasan yang perlu user tahu
- Tidak ada auto-crop/luruskan ala CamScanner. Itu butuh OpenCV.js (berat, ±8 MB). Foto harus cukup dekat, terang, tidak miring.
- Struk dengan beberapa metode bayar (split payment) membuat cek silang tidak cocok → keyakinan "low".
- Yang dibaca: total, tanggal, nama toko. Rincian per item belum disimpan.
- Hasil SELALU perlu dicek user; antarmuka memfokuskan kolom nominal setelah scan.

## Langkah lanjut (opsional)
Tebak kategori dari nama toko (ingat pilihan user sebelumnya), auto-crop ringan, tes E2E browser (Playwright) untuk alur foto → isi → simpan.
