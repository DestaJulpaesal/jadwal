# Audit polish — Fase 0

Tanggal audit: 7 November 2026  
Branch: `polish/fase-0-audit`  
Root aplikasi: `C:\jadwal\list`

## Ringkasan

Project adalah Laravel 10 + PHP 8.1+, Blade, Tailwind CSS 3, Alpine.js, Vite 6, dan SQLite. Terdapat 79 view Blade. Fase ini tidak mengubah logika bisnis; perubahan hanya memindahkan aset, memecah entrypoint CSS tanpa mengganti selector, menambah CI, dan memperbarui dokumentasi.

## (a) Halaman dan layout

View dengan tag `<html>` sendiri:

- `resources/views/welcome.blade.php`
- `resources/views/styleguide.blade.php`
- `resources/views/layouts/user.blade.php`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/layouts/error.blade.php`
- `resources/views/errors/*.blade.php` melalui `layouts.error`
- `resources/views/vendor/mail/html/layout.blade.php` untuk email

Layout utama:

| Area | Layout |
|---|---|
| Pengguna | `layouts.user` |
| Admin | `layouts.admin` |
| Auth | komponen `x-guest-layout` yang merender layout guest |
| Error | `layouts.error` |
| Landing | `welcome.blade.php` |
| Email | layout mail Laravel |

Entry CSS konsep dipindahkan tanpa perubahan isi ke `resources/css/pages/home.css` dan `resources/css/pages/login.css`.

## (b) Varian kontrol UI

Hasil grep view sebelum pemisahan CSS: 89 kemunculan `btn-`, 70 tag `<button>`, 76 `form-control`, dan belum ada pemakaian `x-primary-button`. Komponen tombol yang sudah tersedia adalah `primary-button`, `secondary-button`, dan `danger-button`; migrasi pemakaian seragam ditunda ke Fase 1.

Komponen feedback/modal yang tersedia: `alert`, `toast`, `modal`, `confirm-action`, `input-error`, dan `empty-state`.

## (c) Teks Inggris

Grep katalog fase menemukan sisa istilah Inggris pada view, locale, dan route auth, terutama `Password`, `Register`, dan teks bawaan Laravel. Sebagian adalah identifier teknis atau nama route. Penggantian teks terlihat user ditunda ke Fase 3 agar konsisten dengan katalog pesan dan tidak mengubah kontrak test di fase ini.

## (d) Inline handler/style

| Pola | Jumlah di view |
|---|---:|
| `style=` | 36 |
| `onclick=` | 0 |
| `onchange=` | 2 |
| `x-data=` | 6 |

Inline style pada kategori, progress, email, dan sprite masih dibutuhkan oleh view saat ini. Penghapusan inline handler dan rollout CSP dicatat untuk Fase 7.

## (e) Form

Form utama berada pada auth, profil, pengaturan, tugas, kebiasaan, target, keuangan, tagihan, utang, pencarian, serta admin. Komponen `x-field`, `x-input-label`, `x-text-input`, `x-input-error`, dan `x-money-input` sudah tersedia, tetapi belum dipakai seragam. Atribut keyboard mobile belum lengkap pada semua field; inventarisasi dan standardisasi ditunda ke Fase 2.

## (f) Route dan menu

Route utama yang terdaftar mencakup dashboard, kalender, tugas, kebiasaan, target, keuangan, tagihan, utang, laporan, pencarian, profil, pengaturan, autentikasi, dan admin. Audit menu menemukan beberapa route fitur yang tidak selalu tampil pada navigasi utama; perapian menu ditunda ke Fase 4.

`reports.index` masih memakai controller laporan/keuangan yang sudah ada. Verifikasi pemisahan perilaku laporan ditunda ke fase navigasi karena fase ini tidak boleh mengubah logika bisnis.

## (g) Baseline test, build, dan audit dependensi

| Pemeriksaan | Hasil baseline |
|---|---|
| `php artisan test` | 102 lulus, 4 gagal, 475 assertions; gagal sudah ada sebelum perubahan fase ini |
| `npm run build` | lulus; CSS utama 98.62 kB (gzip 17.26 kB), JS 106.47 kB (gzip 38.74 kB) |
| `npm audit --audit-level=high` | exit non-zero; 3 moderate pada `glob`/`minimatch` melalui dependency dev |
| `composer audit` | tidak dapat dijalankan karena Composer CLI tidak tersedia pada environment ini |
| Lighthouse | belum dapat diukur tanpa browser audit runner |

Kegagalan test baseline yang tercatat:

1. Ekspektasi label dashboard `Perencanaan` tidak ditemukan.
2. Test kategori mengharapkan `user_id`, sedangkan fixture kategori saat ini menghasilkan `null`.
3. Dua kegagalan lain berada pada suite yang sama dan perlu ditinjau sebelum fase berikutnya.

CI baru menjalankan pemeriksaan yang sama pada PHP 8.1/8.2 dan Node 20.

## (h) Lazy loading / N+1

Suite test berjalan dengan konfigurasi non-produksi yang mempertahankan `Model::preventLazyLoading()`. Audit halaman interaktif penuh belum dapat diselesaikan karena tidak ada browser/Lighthouse runner dalam sesi ini. Smoke test dan pemeriksaan N+1 harus menjadi langkah awal Fase 1 sebelum mengubah query.

## (i) Baseline Lighthouse

Belum tersedia pada environment ini. URL dan empat viewport yang harus diukur pada fase penutup: login, beranda, tugas, dan keuangan pada 360×640, 390×844, 768 px, dan 1280 px.

## Perubahan Fase 0

- CSS utama dipisah menjadi `tokens.css`, `base.css`, `components/legacy.css`, dan `layouts/legacy.css` dengan selector dan urutan aturan dipertahankan.
- CSS landing/auth dipindahkan ke `resources/css/pages/`.
- Sisa dokumen desain dipindahkan ke `docs/archive/`; katalog ikon dipindahkan ke `docs/`.
- `.gitignore` mencakup database SQLite dan arsip ZIP.
- CI GitHub Actions ditambahkan untuk PHP 8.1/8.2 dan Node 20.

## Temuan ditunda

- Folder `list/` adalah salinan skeleton lama; jangan diubah atau dihapus tanpa persetujuan pemilik. Sarannya: keluarkan dari repository/deployment pada pekerjaan terpisah.
- Standardisasi tombol, form mobile, pesan, navigasi, motion, security headers, CSP, aksesibilitas, PWA, dan backup mengikuti fase masing-masing.

## Hasil akhir Fase 8 — checkpoint (belum lulus penuh)

Link “Lompat ke konten” dan target `main#main-content` kini tersedia di layout dasar dan halaman utama; tes struktur mencakup halaman publik, auth, user, dan admin. Matriks di bawah ini sengaja membedakan tinjauan kode dari verifikasi browser. `Belum tervalidasi` bukan tanda lulus.

| Halaman / alur | Tombol, header, empty state | Form dan pesan | Kontras dan motion | Keyboard/fokus | 320 px, tema gelap, zoom | Bukti |
|---|---|---|---|---|---|---|
| Beranda publik | Komponen dan header ditinjau; empty state tidak berlaku | Tidak ada form transaksi | Token desain terdokumentasi; belum audit visual | Skip link bersama; browser belum diuji | Belum tervalidasi | View `welcome` |
| Masuk | Komponen dan header ditinjau | Label, bantuan, error dan feedback terlihat | Tema gelap belum audit visual; reduced motion durasi animasi 1 ms | Skip link dan target main diuji | 320/360/390/768/1280 tanpa overflow; zoom CSS 200% tanpa overflow | Browser + tes satu `h1` |
| Daftar | Komponen dan header ditinjau | Form daftar memakai komponen field | Belum audit visual per field | Skip link tersedia; tab seluruh kontrol belum diuji | Belum tervalidasi | Tes satu `h1` dan target struktur |
| Lupa/reset/konfirmasi/verifikasi kata sandi | Komponen shared | Form auth berbagi field/error; tiap status belum diuji | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Layout guest |
| Dashboard user | Header/CTA shared; empty state belum audit per data | N/A | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Tes layout user |
| Kalender user | Tombol tanggal dan agenda | N/A | Belum tervalidasi | Pemilihan tanggal keyboard belum diuji | Belum tervalidasi | View kalender |
| Daftar dan form tugas | Tombol/form shared | Error/help ada; tiap error bag belum audit aksesibilitas | Belum tervalidasi | Skip link tersedia; urutan fokus belum diuji | Belum tervalidasi | Views tugas |
| Daftar dan form kebiasaan | Tombol/form shared | Error/help ada; field kondisional belum diuji dengan pembaca layar | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views kebiasaan |
| Daftar dan form target | Tombol/form shared | Form kondisional belum diuji dengan pembaca layar | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views target |
| Ringkasan dan transaksi uang | Tombol/modal shared; empty state belum audit per data | Form nominal memiliki label; mode privasi belum diuji aksesibilitas | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views uang |
| Daftar/form tagihan dan cicilan | Tombol/modal shared | Field dan konfirmasi belum audit per skenario | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views kewajiban |
| Daftar/form utang-piutang | Tombol/modal shared | Form create/edit belum audit per skenario | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views pinjaman |
| Laporan dan pencarian | Header/hasil ditinjau sebagian | Filter/search belum diuji dengan keyboard | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views laporan/pencarian |
| Lainnya, profil, pengaturan | Header/form shared | Validasi belum audit per field | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views akun |
| Dashboard, daftar akun, detail akun admin | Header/tabel/aksi admin belum diuji visual | Form aksi admin belum diuji | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Views admin |
| Kategori admin | Tombol/form shared | Pemilih warna belum diuji pembaca layar | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | View kategori |
| Error 403 | Layout error shared | N/A | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Template error |
| Error 404 | Layout error shared | N/A | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Template error |
| Error 419 | Layout error shared | N/A | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Template error |
| Error 429 | Layout error shared | N/A | Belum tervalidasi | Retry countdown belum diuji screen reader | Belum tervalidasi | Template error |
| Error 500 | Layout error shared | N/A | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Template error |
| Error 503 | Layout error shared | N/A | Belum tervalidasi | Skip link tersedia | Belum tervalidasi | Template error |
| Styleguide | Variasi komponen ditinjau | Input Rupiah berfungsi saat CSP enforce | Browser enforce diuji; mode gelap belum audit visual | Modal dibuka/ditutup; focus loop belum diverifikasi browser | Belum tervalidasi | Browser lokal |

### Pengukuran checkpoint

- Login: tidak ada scroll horizontal pada viewport 320×640, 360×640, 390×844, 768×1024, dan 1280×800 setelah memperbaiki flex layout yang sebelumnya overflow pada layar sempit.
- Skip link menerima Tab pertama dan Enter memfokuskan target main.
- Simulasi `prefers-reduced-motion: reduce` menghasilkan durasi animasi 1 ms; perubahan token ini bukan pengganti audit semua halaman.
- Simulasi CSS zoom 200% pada lebar viewport 640 px tidak menghasilkan scroll horizontal. Ini bukan uji browser zoom/OS sungguhan.
- Lighthouse, screenshot before/after enam halaman, audit kontras seluruh halaman, tema gelap menyeluruh, dan browser sweep semua route belum dilakukan. Fase 8 tetap terbuka.

## Hasil akhir — checkpoint 9 November 2026

| Bukti | Hasil |
|---|---|
| Layout dokumen HTML | View aplikasi memakai `layouts.base`; elemen `<html>` tersisa hanya di layout dasar dan layout email vendor. |
| Inline event handler di Blade | Tidak ditemukan `onclick`, `onchange`, atau `onsubmit`. Ekspresi Alpine memakai komponen CSP dan pemanggilan sederhana. |
| Durasi/easing CSS | Nilai `ms` dan `cubic-bezier()` hanya ditemukan di `resources/css/tokens.css`. |
| Test suite | 177 lulus (1.107 assertion) pada run penuh final. |
| Build | Vite lulus; bundle saat checkpoint: JS 54,15 KB gzip, CSS bersama 19,15 KB, login 3,06 KB, beranda 3,79 KB. |
| Dependency audit | `npm audit` 0 vulnerability setelah update non-major; Composer CLI tidak tersedia. |
| Format/route/patch | Pint `--test`, Blade view cache, route health, dan `git diff --check` lulus. |
| Browser | Login diuji di 360×640, 390×844, 768×1024, dan 1280×800 tanpa overflow horizontal; reduced motion terukur 0,001 s. |
| PWA/backup | Manifest/icon dan kebijakan cache statis diuji; backup/restore SQLite plus foto lulus test otomatis pada Windows. |
| Belum dapat dibuktikan | Lighthouse, N+1 semua route terautentikasi, screen reader, kontras semua tema/halaman, report CSP semua halaman, install/offline Android/iOS, Composer audit, dan restore production. |

Sel yang belum terverifikasi dalam matriks aksesibilitas di atas tetap berstatus belum terverifikasi; tidak diubah menjadi lulus berdasarkan inspeksi sumber saja.
