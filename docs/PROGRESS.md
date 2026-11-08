# Progress polish

## Fase 0 — Audit, repo, CSS, CI

Status: selesai secara kode, dengan baseline test yang sudah gagal sebelum perubahan.

### Selesai

- Membuat branch `polish/fase-0-audit`.
- Memindahkan dokumen desain lama ke `docs/archive/` dan katalog ikon ke `docs/`.
- Memecah entrypoint CSS tanpa mengganti selector atau nama class.
- Memindahkan CSS landing/auth ke `resources/css/pages/`.
- Menambahkan `.github/workflows/ci.yml` untuk PHP 8.1/8.2 dan Node 20.
- Menambahkan `pint.json`.
- Menambahkan tautan dokumentasi pada README.
- Menulis hasil audit di `docs/AUDIT_POLISH.md`.
- Menambahkan smoke test redirect untuk seluruh halaman user dan admin tanpa sesi.

### Keputusan dan asumsi

- Selector CSS dipertahankan apa adanya agar fase ini tidak mengubah tampilan.
- Database tetap SQLite.
- Istilah sapaan dan standardisasi Bahasa Indonesia dikerjakan pada Fase 3.
- Folder `list/` tidak disentuh dan tetap memerlukan keputusan pemilik.

### Ditunda

- Perbaikan empat test baseline.
- Smoke test status 200 untuk setiap resource dan pengukuran N+1 berbasis browser.
- Lighthouse.
- Semua perubahan UI/UX, CSP, hardening security, PWA, dan fitur bisnis.

### Risiko regresi

- Pemisahan CSS mengubah cara Vite menghasilkan asset; build wajib dijalankan sebelum merge.
- Audit npm melaporkan tiga kerentanan moderate pada dependency dev; tidak diubah pada fase ini karena upgrade dependency di luar lingkup.

### Perlu keputusan pemilik

- Hapus atau keluarkan folder `list/` dari repository dan deployment.
- Konfirmasi pilihan database saat beban produksi meningkat.
- Konfirmasi sapaan baku “kamu” saat Fase 3 menyelaraskan teks.

### Langkah pertama fase berikutnya

Mulai Fase 1 dengan token CSS dan komponen tombol berdasarkan selector yang sudah diaudit.

## Fase 1 — Fondasi desain, tombol, dan komponen

Status: selesai secara kode; build dan format lulus. Tes lengkap mencatat 120 lulus dan 4 gagal, sama dengan baseline Fase 0.

### Selesai

- Lengkapi token warna, spasi, radius, bayangan, tipografi, motion, dan z-index; tambah pasangan warna tema gelap dan varian latar soft untuk alert/lencana.
- Self-host Figtree dan hilangkan tautan Google Fonts dari layout.
- Satukan tombol Blade untuk tombol/tautan, ukuran dan varian, state disabled/loading/success, fokus keyboard, label ikon, dan pengecekan `aria-label`/`title` untuk icon-only.
- Ganti tombol mentah pada view dengan `<x-button>`; grep tidak menemukan tombol mentah di luar komponen.
- Lengkapi komponen Blade untuk field, select, segmented, choice-card, textarea, tanggal cepat, bagian dan aksi formulir, ringkasan error, badge, statistik, tab, alert, toast, dialog, bottom-sheet, keadaan kosong, skeleton, progress, dan header halaman.
- Lengkapi `/styleguide` dengan ikon, jenis/ukuran/state tombol, kontrol formulir, pesan, keadaan kosong, progress, dialog, dan bottom-sheet.
- Hapus deteksi route `user.*` dari komponen dan dokumentasikan token, aturan tombol, serta rasio kontras tema terang/gelap.
- Tambahkan tes rendering, inferensi ikon aksi umum, dan aksesibilitas tombol ikon saja.
- Ukur halaman styleguide pada 360, 390, 768, dan 1280 px; tidak ada overflow horizontal. Cek token dan warna alert tema gelap di browser.

### Keputusan dan asumsi

- Inferensi ikon tombol membantu mempertahankan migrasi yang kompatibel; untuk makna yang tidak jelas, pemanggil tetap harus memberikan ikon eksplisit.
- Tema user gelap mempertahankan aksen emas yang sudah ada; tema gelap lain menggunakan token hijau semantik. Pasangan foreground/background keduanya diverifikasi.
- Peringatan empat tes baseline Fase 0 dipertahankan dan tidak dihapus.
- `npm run build`, Pint `--test`, Blade view cache, dan `git diff --check` lulus; seluruh empat kegagalan baseline masih ada tanpa kegagalan baru.

### Ditunda

- Empat kegagalan tes baseline, Lighthouse, dan pengukuran N+1 tetap di luar lingkup fase ini.
- Pemeriksaan manual halaman utama yang memerlukan sesi autentikasi dan audit aksesibilitas menyeluruh.
- Login/register dan halaman form spesifik akan dipoles pada Fase 2; konsistensi bahasa pada Fase 3.

### Risiko regresi

- Migrasi tombol luas dapat mengubah ukuran/warna pada konteks khusus; cek build, feature tests, dan tampilan lintas ukuran.
- Empat kegagalan baseline diketahui sejak Fase 0; bedakan dengan kegagalan baru saat validasi.

### Langkah pertama fase berikutnya

Buat branch Fase 2 dan audit seluruh create/edit form, termasuk route, partial, validasi, serta perilaku submit, sebelum mengubah pola formulir.

## Fase 2 — Form nyaman di HP

Status: selesai secara implementasi. Build, kompilasi Blade, format PHP, dan syntax JavaScript lulus. Tes lengkap: 126 lulus, 4 gagal; keempatnya sama dengan kegagalan baseline Fase 0.

### Selesai

- Memusatkan submit, pencegahan submit ganda, status loading, pemulihan bfcache, timeout/coba lagi, perlindungan perubahan belum tersimpan, ringkasan error, auto-submit filter, pintasan tanggal/jam, resize textarea, dan hitung karakter di `resources/js/forms.js`.
- Memastikan inisialisasi form dan UI tetap jalan ketika modul dimuat setelah `DOMContentLoaded`.
- Menambahkan ringkasan error, bantuan field, validasi saat blur, aksi sticky, dan perlindungan perubahan pada form tugas, kebiasaan, target, transaksi, tagihan, utang/piutang, profil, dan pengaturan.
- Mengubah jenis kebiasaan dan target menjadi pilihan segmented; menambah pintasan waktu kebiasaan serta disclosure animatif untuk pengaturan lanjutan target.
- Memindahkan quick-add transaksi, dompet, kategori, tagihan/cicilan, dan utang/piutang ke bottom-sheet. FAB global kini menyediakan keenam pintasan dan langsung membuka sheet terkait.
- Menambahkan edit transaksi dan utang/piutang. Transaksi awal utang/piutang disinkronkan sebelum pembayaran; arah, nominal, tanggal awal, dan dompet dikunci setelah pembayaran agar saldo/riwayat konsisten.
- Menambahkan pemilih warna kategori admin dengan nama warna, pratinjau teks, dan rasio kontras; penghapusan kategori/kebiasaan menggunakan dialog konfirmasi.
- Penghapusan akun kini meminta pengguna mengetik `HAPUS` serta memasukkan password. Form password menyediakan tombol tampil/sembunyi dan indikator kekuatan.
- Mengganti filter laporan inline `onchange` dengan handler terpusat dan tombol cadangan tanpa JavaScript.
- Pemeriksaan browser login/register pada 360×640, 390×844, 768×1024, dan 1280×900: tidak ada overflow horizontal; kontrol teks 16 px dan tinggi 48 px. Indikator kekuatan password merespons input.
- Tidak ada perubahan skema database. `npm run build`, `php artisan view:cache`, Pint `--test`, `node --check`, dan `git diff --check` lulus; hanya satu handler submit terdelegasi dan tidak ada atribut inline `onchange`, `onclick`, atau `onsubmit` di view.

### Keputusan dan asumsi

- Perubahan inti transaksi utang/piutang tidak boleh mengubah perhitungan setelah pembayaran tercatat; pihak, jatuh tempo, dan catatan tetap bisa diperbarui.
- Pemilih warna mencakup kategori tugas/kebiasaan admin. `money_categories` tidak memiliki kolom warna; kolom baru dilarang pada fase ini, jadi warna untuk kategori uang pengguna ditunda.
- Draf otomatis `localStorage` bersifat opsional dan tidak ditambahkan; data password tidak pernah dimasukkan ke snapshot perubahan form.
- Empat kegagalan tes baseline tetap ada pada suite lengkap: dua di `AdminUserManagementTest` dan dua di `UserTaskTest`; tidak ada kegagalan baru.

### Ditunda

- Empat kegagalan baseline dari Fase 0.
- Warna untuk kategori uang pengguna karena tidak tersedia kolom yang dapat dipakai tanpa perubahan skema.
- Pengukuran visual browser untuk halaman terautentikasi; view terkait tercakup dalam feature tests.
- Draf otomatis lokal; tidak wajib dan memerlukan kebijakan masa simpan/penghapusan yang lebih jelas.

### Risiko regresi

- Dialog dan pintasan form bergantung pada JavaScript terpusat; jalur tanpa JavaScript tetap tersedia untuk filter laporan, tetapi interaksi sheet memerlukan JavaScript.
- Pembaruan transaksi awal utang/piutang diuji sebelum dan sesudah pembayaran; tetap lakukan pemeriksaan saldo saat integrasi data produksi.

### Langkah pertama fase berikutnya

Buat branch Fase 3 dan baca `04_FASE3_PESAN_VALIDASI.md`; fokus pada penyelarasan pesan validasi tanpa mengubah validasi atau perilaku bisnis.

## Fase 3 — Pesan, validasi, dan umpan balik (sedang dikerjakan)

Status: belum selesai. Implementasi berlanjut di branch `polish/fase-3-closeout`; fondasi feedback/auth dan katalog sudah diperluas, tetapi checklist belum seluruhnya terpenuhi.

### Sudah diterapkan

- Memusatkan pesan feedback domain dan validasi FormRequest di katalog `lang/id/ui.php` dan `lang/id/validation.php`; menambahkan pesan spesifik untuk tugas, kebiasaan, target, profil, login, pendaftaran, dan pengaturan.
- Menambahkan `messages()` dan `attributes()` pada lima FormRequest yang ada; pesan kepemilikan kategori tidak lagi menyebut kepemilikan.
- Menambahkan `App\Support\Feedback` dan memindahkan flash sukses/error controller ke payload terpadu; tidak ditemukan lagi `session('success')` atau `session('error')` manual.
- Memperjelas pesan login generik dan menampilkan pesan akun nonaktif hanya setelah kata sandi yang benar; kesalahan kata sandi yang salah tetap generik.
- Menyamakan syarat kata sandi live dengan validasi server (minimal 8 karakter, huruf besar-kecil, dan angka) untuk pendaftaran, reset, dan ganti kata sandi.
- Menambahkan petunjuk Caps Lock, hitung mundur throttle, pesan berhasil masuk/keluar, anti-enumerasi reset kata sandi, jeda toast saat hover/fokus/sentuhan, dan redirect sesi kedaluwarsa ke halaman Masuk.
- Menambahkan banner offline yang mencegah submit saat terputus, aksi coba lagi untuk kegagalan koneksi, halaman error 403/404/419/429/500/503, serta hitung mundur 429 bila header tersedia.
- Respons lupa kata sandi sama untuk email terdaftar/tidak terdaftar; tes anti-enumerasi sudah ada.
- Menambahkan tes untuk payload feedback/toast, FormRequest, pesan login, throttle, akun nonaktif, anti-enumerasi reset, dan kekuatan kata sandi pendaftaran.
- Menyelaraskan istilah yang ditemukan pada halaman detail admin, email ringkasan, dan pesan akun nonaktif.
- Toast kini menggunakan antrean: maksimal tiga tampil bersamaan, sisanya menunggu; waktu tampil dijeda saat pointer/fokus berada di toast, dan menutup toast memajukan antrean.
- Memusatkan pesan validasi inline untuk tautan reset kata sandi, konfirmasi hapus akun, pengaturan, dan pesan khusus hapus admin terakhir; menambah tes pesan untuk jalur tersebut dan filter tugas.

### Belum selesai — jangan tandai fase ini sebagai selesai

- Audit pesan Inggris/key mentah dan istilah di seluruh UI/email/lang belum selesai; sebagian jalur validasi inline controller juga belum memiliki tes pesan.
- Aksi urungkan belum membatalkan perubahan secara nyata. Belum ada operasi yang aman untuk dibalik tanpa mengubah lifecycle data; jangan tampilkan aksi tersebut sebagai undo sungguhan.
- Pemeriksaan kata sandi bocor tidak ditambahkan: layanan eksternal dapat menerima sidik jari kata sandi tanpa persetujuan eksplisit.
- Audit lintas-pengguna dan validasi setiap jalur inline masih perlu dilengkapi; jangan menganggap tes FormRequest mencakup jalur-jalur tersebut.

### Risiko dan keputusan sementara

- Akun nonaktif dibedakan hanya setelah verifikasi kata sandi cocok, sesuai kebutuhan UX tanpa mengungkap status saat kredensial salah.
- Tidak ada pengiriman sidik jari kata sandi ke layanan eksternal.
- Fase ini menyentuh autentikasi dan pesan global; validasi penuh wajib dijalankan lagi setelah audit bahasa dan pengujian validasi inline.
- Empat kegagalan baseline suite penuh tetap ada: dua `AdminUserManagementTest` dan dua `UserTaskTest`.

### Verifikasi checkpoint

- Tes terarah feedback, pengaturan, reset kata sandi, profil, dan validasi filter tugas: 32 lulus (171 assertion).
- Uji browser antrean: dari lima toast, tiga tampil dan dua menunggu; setelah satu ditutup, toast berikutnya tampil.
- `npm run build`, `php artisan view:cache`, Pint pada PHP yang berubah, dan `git diff --check` lulus.
- Suite penuh terakhir: 145 lulus, 4 gagal pada empat tes baseline yang tercatat di atas; nama kegagalannya tetap sama.

### Langkah berikutnya

Audit pesan yang terlihat dan seluruh jalur validasi inline, lengkapi tes lintas-pengguna, lalu putuskan rancangan undo yang aman; jangan menandai Fase 3 selesai sebelum semua butir wajib ditutup.

## Fase 4 — Navigasi dan struktur halaman (sedang dikerjakan; belum boleh ditandai selesai)

### Selesai pada implementasi saat ini

- Menambahkan `layouts/base`; seluruh layout web user/admin/guest/error, beranda, dan styleguide kini menggunakannya. Layout email tetap memakai kerangka HTML email.
- Menambahkan halaman `Lainnya`, mengganti slot Profil pada bottom-nav, dan melengkapi tautan sidebar untuk kalender, target, bagian keuangan, tagihan, utang-piutang, laporan, pengaturan, dan profil.
- Menghubungkan `reports.index` ke `ReportController` dan menjaga nama rute yang sudah ada.
- Memperbaiki penanda tab aktif Keuangan agar tetap benar saat URL memuat parameter bulan.
- Menyeragamkan header daftar tugas, target, tagihan, utang-piutang, laporan, dan halaman Lainnya; menambahkan tombol Kembali berikon pada halaman buat/ubah.
- Menambahkan ikon, penjelasan, dan CTA pada empty state target, tagihan, utang-piutang, transaksi, pencarian, serta daftar tugas.
- Memastikan formulir login muat tanpa scroll pada 360×640; tidak ada overflow horizontal pada 360, 390, 768, dan 1280 px.
- Memverifikasi ulang di browser: formulir login seluruhnya terlihat tanpa scroll pada 360×640 dan 390×844; tidak ada overflow horizontal pada 360, 390, 768, dan 1280 px.
- Mengubah notifikasi menjadi sheet Perlu perhatian; di Beranda sheet menampilkan tugas terlambat dan pengingat, sedangkan halaman lain memberi pintasan ke ringkasan Beranda.
- Menampilkan onboarding 3 langkah dengan status selesai berdasarkan tugas, kebiasaan, dan transaksi yang sudah ada.
- Menambahkan ikon, manfaat, dan aksi langsung pada empty state dompet/kategori uang, serta CTA dari ringkasan tanpa data; menyamakan header Keuangan, Pencarian, Pengaturan, dan Profil dengan `x-page-header`.
- Membuat lekukan indikator bottom-nav berpindah dengan transisi token `--dur-base` dan menghormati `prefers-reduced-motion`.
- Validasi lanjutan: tes `UserNavigationTest` dan `LifeHubTest` lulus (31 tes, 178 assertions); suite lengkap 138 lulus dan 4 gagal, sama dengan kegagalan baseline di `AdminUserManagementTest` dan `UserTaskTest`. Cache Blade, build Vite, Pint dari binary paket, dan `git diff --check` lulus.

### Belum selesai

- Header halaman belum diseragamkan di semua halaman; navigasi kembali/breadcrumb belum diaudit.
- Empty state dan CTA setiap daftar belum diaudit menyeluruh; termasuk daftar admin dan semua halaman detail/form.
- Audit penuh aksi daftar pada layar HP/desktop dan pengukuran browser untuk halaman terautentikasi masih perlu dilakukan.

### Langkah berikutnya

Panel/sheet Perlu perhatian kini menampilkan item dashboard dan onboarding memiliki tiga langkah dengan status penyelesaian berbasis data; tes baru mencakup keduanya. Build Vite dan tes navigasi/keuangan lulus. Launcher Composer `vendor/bin/pint` rusak karena `__DIR__` terpotong, tetapi formatter berhasil dijalankan langsung dari binary paket. Fase 3 masih memiliki butir wajib terbuka. Selesaikan audit dan closeout Fase 3 sebelum menandai Fase 4 selesai; setelah itu tuntaskan audit header, empty state, aksi daftar, dan pengukuran browser pada layar terautentikasi.

## Fase 5 — Animasi, transisi, dan performa (sedang dikerjakan; belum selesai)

### Sudah diterapkan pada checkpoint ini

- Memusatkan durasi/easing motion pada token, membetulkan deklarasi animasi maskot, dan menambahkan transisi lintas-halaman, indikator navigasi lambat, prefetch terbatas pada tautan bertanda, serta jeda animasi saat tab tersembunyi.
- Memperbaiki easing transisi halaman yang sebelumnya tidak valid, membatasi animasi masuk halaman/kartu hingga 140 ms, dan menghentikan animasi dekoratif pada `prefers-reduced-motion` serta perangkat `update: slow`.
- Memecah perilaku UI ke modul `nav.js`, `motion.js`, `network.js`, `toast.js`, `optimistic-actions.js`, `password.js`, dan `privacy.js`; delegasi click dan submit kini terpusat di `forms.js`.
- Menambahkan dukungan respons JSON pada centang tugas, check-in kebiasaan, dan pembatalan check-in sambil mempertahankan POST/redirect untuk formulir tanpa JavaScript.
- Menambahkan submit fetch dengan status optimistic, pemulihan tampilan ketika gagal, pesan kesalahan server, dan rollback DOM untuk status kebiasaan.
- Menguji centang tugas, check-in, dan pembatalan check-in JSON secara terarah: 3 tes lulus (10 assertion). Syntax check seluruh modul, build Vite, kompilasi Blade, format PHP, dan `git diff --check` lulus; ukuran gzip saat ini: JS 44,55 KB, CSS bersama 19,04 KB, CSS halaman masuk 3,05 KB, CSS beranda 3,79 KB.
- Pengujian browser login: tanpa overflow horizontal pada 360×640, 390×844, 768×1024, dan 1280×800; form seluruhnya terlihat pada tiap viewport. Tombol tampil/sembunyikan kata sandi berfungsi dan animasi maskot `none` saat reduced motion.

### Belum selesai / belum dibuktikan

- Fase 3 dan Fase 4 masih punya checklist terbuka; prasyarat Fase 5 belum terpenuhi.
- Pengujian browser submit sukses/gagal dan navigasi terautentikasi belum dilakukan; pengujian reduced-motion serta ukuran layar hanya mencakup halaman login.
- Ukuran build sebelum perubahan dan Lighthouse belum tersedia; jangan mengklaim perbandingan atau target Lighthouse.
- Audit animasi daftar/progres, responsif performa, N+1, cache aset, dan konfigurasi produksi Fase 5 belum dilakukan.
- Tes `UserTaskTest` + `LifeHubTest`: 48 lulus, 2 gagal pada dua kasus baseline `UserTaskTest` yang telah tercatat sebelum perubahan; kedua tes JSON baru lulus.
- Suite penuh terakhir: 147 lulus, 4 gagal pada empat tes baseline yang tercatat (dua `AdminUserManagementTest`, dua `UserTaskTest`); tidak ada kegagalan baru dari perubahan Fase 5.

### Langkah berikutnya

Tuntaskan audit wajib Fase 3 dan Fase 4, lalu lanjutkan modularisasi dan verifikasi browser Fase 5. Jangan memulai Fase 6/7 atau menyatakan fase selesai sebelum dependensi fase sebelumnya terpenuhi.

### Verifikasi Fase 3 pada checkpoint ini

- Tes `FeedbackTest.php` dan seluruh tes pada `tests/Feature/Auth`: 35 lulus (143 assertion).
- Format PHP yang berubah, kompilasi Blade, dan `git diff --check` lulus.
- Audit semua teks/kunci Inggris, validasi inline, dan tes lintas-pengguna belum lengkap; Fase 3 tetap terbuka.
