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
