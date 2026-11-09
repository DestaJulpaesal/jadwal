# Progress polish

## Status penutupan terakhir — 9 November 2026

Fase 0–2 tercatat selesai pada checkpoint sebelumnya. Fase 3–9 telah ditinjau dan sisa pekerjaan yang aman serta dapat diverifikasi di repo sudah dilanjutkan; fase yang membutuhkan bukti produksi/perangkat nyata tidak ditandai selesai hanya berdasarkan inspeksi kode.

| Fase | Status saat ini |
|---|---|
| 0–2 | Selesai menurut catatan checkpoint masing-masing. |
| 3 | Katalog search dan validasi diperluas, teks kebiasaan diperbaiki, tes pesan bertambah. Audit semua teks/email dan seluruh validasi inline belum tuntas. |
| 4 | Bottom-nav kembali tepat lima induk; sub-menu Kalender, Target, Keuangan, Pengaturan, dan Panel Admin dipetakan lewat URL; sidebar desktop dan tes pemetaan diperbarui. Audit seluruh halaman/aksi serta uji manual seluruh viewport/tema masih terbuka. |
| 5 | Motion terdokumentasi; token CSS, optimistic actions, dan ukuran bundle diperiksa. Lighthouse, N+1 seluruh route, dan CPU throttling belum diukur. |
| 6 | Sesi/password/login/IP limiter, host/proxy, IDOR kategori global, hardening upload, audit log admin, password confirmation, serta retensi audit ditangani dan dites. Audit IDOR menyeluruh per semua metode/resource dan verifikasi server tetap perlu. |
| 7 | CSP dengan nonce dan mode rollback tersedia; default `off` dipertahankan. Belum ada masa observasi report lintas halaman, jadi belum siap enforce/production. |
| 8 | Struktur aksesibilitas dan login diuji pada beberapa viewport/reduced-motion; audit seluruh halaman, screen reader, tema/kontras, screenshot, dan Lighthouse belum tersedia. |
| 9 | Manifest, ikon, worker yang hanya cache aset statis berhash/offline, health check, WAL, backup terenkripsi, jadwal, restore test, dan panduan tersedia. Install fisik dan pemulihan backup produksi belum diuji. |
| 10 | Opsional; tidak dikerjakan karena tidak dipilih sebagai cakupan wajib. |

Verifikasi final: suite penuh **177 lulus (1.107 assertion)**. Vite build, `npm audit` (0 vulnerability), Pint `--test` untuk file PHP yang berubah, kompilasi Blade, pemeriksaan route health, dan `git diff --check` lulus. Composer CLI tidak tersedia sehingga `composer audit` belum dapat dijalankan. Uji browser login pada 360×640, 390×844, 768×1024, dan 1280×800 tidak menemukan overflow horizontal; reduced-motion menghasilkan 0,001 s. Bukti dan batas tiap fase dirangkum di `docs/LAPORAN_POLISH.md`.

### Perubahan penutupan yang ditambahkan

- Password default terpusat, pembatas login per IP, `auth.session`, pemaksaan HTTPS URL di production, pemeriksaan debug production, dan aktivasi `TrustHosts`.
- Aksi admin sensitif meminta konfirmasi password; perubahan status/peran/reset/hapus dicatat di audit log dengan retensi 365 hari.
- Penghapusan akun menghapus kategori pribadi. Validasi kategori menerima kategori global dan kategori yang memang dimiliki pengguna, tanpa menerima data pengguna lain.
- Foto profil dibatasi dimensi dan diproses ulang melalui GD sebelum disimpan privat; MIME dan nama berkas yang dikirim klien tidak dipakai untuk menentukan berkas keluaran.
- Uji test yang usang disesuaikan dengan perilaku kategori global dan label navigasi terbaru; backup restore pada Windows menutup PDO fixture sebelum mengganti file database.
- `docs/MOTION.md` dan pembaruan `docs/KEAMANAN.md` dibuat.

### Pekerjaan/verifikasi yang masih terbuka

- Composer audit belum dapat dilakukan karena executable Composer tidak tersedia.
- `npm audit` melaporkan 0 vulnerability setelah pembaruan lock/dependency non-major; peringatan install-script esbuild memerlukan kebijakan allow-scripts terpisah.
- Pemeriksaan login/halaman publik di browser bukan pengganti pengujian seluruh route terautentikasi pada perangkat nyata.
- CSP tetap `off` sampai report dipantau; backup luar server dan uji restore berkala wajib dioperasikan pemilik deployment.
- Butuh keputusan pemilik: keluarkan folder `list/` dari repo/deploy; persetujuan eksplisit sebelum menggunakan layanan eksternal untuk kata sandi bocor; kunci backup dan tujuan salinan luar server.

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

## Fase 3 — Pesan, validasi, dan umpan balik (belum selesai)

Status: belum selesai. Fondasi feedback/auth dan katalog sudah diperluas, tetapi checklist belum seluruhnya terpenuhi.

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
- Melengkapi label global untuk field validasi turunan dan pencarian (`goal_unit_other`, `weekdays.*`, `schedule_times.*`, `remove_avatar`, dan `q`) serta menambahkan tes regresi cakupan label untuk field yang dipakai controller.

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
- Checkpoint audit: `FeedbackTest` 10 lulus (228 assertion), termasuk validasi form dompet, kategori uang, transaksi, tagihan, dan utang/piutang; gabungan tes feedback, pengaturan, profil, autentikasi, dan reset kata sandi 39 lulus (343 assertion). Pint lulus pada dua file yang berubah; build Vite lulus. Pemeriksaan login di browser tidak menemukan overflow horizontal pada 360, 390, 768, atau 1280 px.
- Suite penuh pada checkpoint ini: 160 lulus, 7 gagal. Empat kegagalan baseline yang sudah diketahui tetap ada; dua kegagalan `CspReportTest` dan satu `EncryptedSqliteBackupTest` pada Windows belum ditangani dan harus ditutup pada audit fase terkait.
- Uji browser antrean: dari lima toast, tiga tampil dan dua menunggu; setelah satu ditutup, toast berikutnya tampil.
- `npm run build`, `php artisan view:cache`, Pint pada PHP yang berubah, dan `git diff --check` lulus.
- Suite penuh terakhir: 145 lulus, 4 gagal pada empat tes baseline yang tercatat di atas; nama kegagalannya tetap sama.

### Langkah berikutnya

Audit pesan yang terlihat dan seluruh jalur validasi inline, lengkapi tes lintas-pengguna, lalu putuskan rancangan undo yang aman; jangan menandai Fase 3 selesai sebelum semua butir wajib ditutup.

## Fase 4 — Navigasi dan struktur halaman (sedang dikerjakan; belum selesai)

### Diterapkan pada perbaikan navigasi lima menu

- Bottom-nav mengembalikan urutan tepat **Beranda · Tugas · Kebiasaan · Keuangan · Profil**. Pencocokan grup rute mempertahankan induk aktif di halaman turunan; tidak ada "Lainnya" di navigasi.
- Route bernama `user.more` tetap tersedia dan mengarahkan pengguna ke Profil. Isi halaman bantuan singkat dan informasi instalasi dipindahkan ke Profil; rute lain tidak dihapus atau diganti nama.
- Menambahkan komponen `<x-subnav>` berisi link biasa dengan label landmark, ikon, `aria-current`, scroll horizontal, scroll-snap, badge opsional, serta pemusatan tab aktif saat halaman dibuka. Transisi menggunakan token yang tersedia dan menghormati reduced-motion.
- Pemetaan: Beranda → Hari ini/Kalender/Perlu perhatian; Kebiasaan → Kebiasaan/Target; Keuangan → Ringkasan/Transaksi/Dompet/Tagihan/Utang-piutang/Laporan; Profil → Profil/Pengaturan/Panel Admin untuk admin. Tugas tetap satu tujuan.
- Menyelaraskan lima grup sidebar desktop dengan pemetaan; grup yang berisi route aktif dibuka otomatis dan dapat diciutkan.
- Ikon pencarian tetap di header; ikon notifikasi menuju anchor Perlu perhatian di Beranda. FAB dan enam jenis tambah dipertahankan. Tombol Keluar tersedia di Profil dengan dialog konfirmasi.
- Menambah contoh `<x-subnav>` pada `/styleguide` dan mendokumentasikan aturan lima menu serta tabel pemetaan di `docs/DESIGN_SYSTEM.md`.
- Tes mencakup tepat lima link, grup induk aktif pada route pemetaan, tab sub-menu aktif, redirect rute `user.more`, dan visibilitas Panel Admin hanya bagi admin.

### Belum selesai / verifikasi manual

- Uji manual geser tab, tombol Back/bookmark, posisi tab aktif, safe-area/FAB, mode gelap, dan reduced-motion pada 360×640, 390×844, 768, serta 1280 px belum dijalankan sebagai sweep browser.
- Tab Panel Admin ditautkan dari Profil; halaman admin memakai shell admin tersendiri, bukan bottom-nav pengguna.
- Audit semua route detail/form dan aksi untuk breadcrumb/kembali serta semua navigasi yang sebelumnya hanya tersedia melalui halaman Lainnya masih perlu pemeriksaan lanjutan.

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

Tuntaskan audit wajib Fase 3 dan Fase 4 serta verifikasi browser Fase 5. Fase 6 boleh dikerjakan paralel; jangan mulai Fase 7 sebelum Fase 5 dan Fase 6 terpenuhi.

### Verifikasi Fase 3 pada checkpoint ini

- Tes `FeedbackTest.php` dan seluruh tes pada `tests/Feature/Auth`: 35 lulus (143 assertion).
- Format PHP yang berubah, kompilasi Blade, dan `git diff --check` lulus.
- Audit semua teks/kunci Inggris, validasi inline, dan tes lintas-pengguna belum lengkap; Fase 3 tetap terbuka.

## Fase 6 — Keamanan inti (sedang dikerjakan; belum selesai)

### Sudah diterapkan pada checkpoint ini

- Middleware global menambahkan header `nosniff`, `DENY`, Referrer/Permissions Policy, COOP dan CORP.
- Respons terautentikasi diberi `Cache-Control: private, no-cache`; HSTS hanya aktif pada environment production dan request HTTPS.
- Konfigurasi `csp_mode`/`csp_report_uri` disiapkan untuk rollout pada Fase 7.
- Tes security headers: 3 lulus (18 assertion); format PHP dan `git diff --check` lulus.
- Suite penuh setelah penambahan middleware: 150 lulus, 4 gagal pada empat tes baseline yang sama; tidak ada kegagalan baru.

### Belum selesai

- Sesi/cookie dan `auth.session`, kebijakan kata sandi, limiter IP, audit IDOR untuk semua resource, validasi/mass assignment, operasi sensitif, hardening upload, guard/audit log admin, konfigurasi produksi, serta dokumentasi keamanan lengkap masih perlu dikerjakan.
- Fase 6 tidak boleh dinyatakan selesai sebelum seluruh checklist wajib dan suite tes diverifikasi.

## Fase 7 — Content Security Policy (belum boleh dianggap rollout produksi selesai)

### Selesai pada checkpoint ini

- Mengimplementasikan `CSP_MODE=off|report|enforce` dengan default aman `off`; mode report memakai `Content-Security-Policy-Report-Only`, enforce memakai header aktif, dan konfigurasi invalid ditolak.
- Membuat nonce per request melalui `Vite::useCspNonce()`, membagikannya ke view, menerapkannya pada tag Vite, dan menambahkan nonce pada skrip tema.
- Memasang policy tanpa `unsafe-eval`/`unsafe-inline` untuk script; atribut style tetap diizinkan melalui `style-src-attr 'unsafe-inline'`. Origin HMR localhost dan 127.0.0.1 hanya diizinkan pada environment local.
- Mengganti dependency Alpine dengan `@alpinejs/csp`; modal, dropdown, navigasi admin, dan input Rupiah kini memakai komponen terdaftar. Kalender dan logika form kebiasaan dipindah ke modul eksternal.
- Menambahkan endpoint CSP report same-origin yang tidak memerlukan auth/CSRF, dibatasi 30 request/menit dan 16 KiB, menerima format laporan lama dan Reporting API, serta hanya mencatat directive/disposition/origin tersanitasi ke channel harian 14 hari.
- Menambahkan `docs/CSP.md` tentang mode, rollback, kompromi style, inventaris kode, serta rollout yang belum selesai.
- Uji browser lokal pada styleguide: modal dapat dibuka/ditutup di mode report maupun enforce; input Rupiah memformat nominal dan pratinjau tetap diperbarui pada enforce. Browser sweep seluruh halaman dan interaksi belum dilakukan.

### Verifikasi

- Tes CSP/header terarah: 10 lulus (50 assertion) setelah menambahkan laporan CSP batch dan policy local HMR.
- Suite lengkap terakhir: 155 lulus, 4 gagal pada empat tes baseline yang sudah tercatat (`AdminUserManagementTest` dan `UserTaskTest`); tidak ada kegagalan baru.
- `npm run build`, `php artisan view:cache`, Pint `--test`, pemeriksaan sintaks JavaScript, dan `git diff --check` lulus. Ukuran gzip build saat ini: JS 53,49 KB, CSS bersama 19,01 KB, CSS masuk 3,05 KB, CSS beranda 3,79 KB.
- Report mode tetap default `off`; tidak ada klaim bahwa laporan semua halaman bersih atau CSP siap enforce di produksi.

### Ditunda / risiko

- Mode `report` perlu dipantau di semua halaman user, admin, auth, error, dan styleguide serta setiap komponen interaktif; pelanggaran yang belum ditelusuri dapat merusak halaman bila enforce diaktifkan.
- Pengujian keyboard modal, dropdown di halaman terautentikasi, variasi form Rupiah, dan kalender lintas ukuran layar belum lengkap.
- Fase 3–6 masih berstatus parsial sesuai catatan di atas; keamanan inti di luar header CSP tetap terbuka.

### Langkah pertama fase berikutnya

Mulai Fase 8 dari audit aksesibilitas dan baseline tes yang sudah ada; lengkapi verifikasi lintas-halaman, fokus/keyboard, zoom/320px, reduced-motion/tema gelap, dan tabel hasil per halaman. Pertahankan keterbatasan baseline test dan jangan mengklaim Lighthouse tanpa pengukuran.

## Fase 8 — Aksesibilitas, konsistensi akhir, dan tes (belum selesai)

### Dikerjakan pada checkpoint ini

- Menambahkan tautan “Lompat ke konten” yang terlihat saat fokus, target `main#main-content` yang dapat menerima fokus pada layout publik/auth/user/admin/error/styleguide, dan tes strukturnya.
- Mengubah judul dekoratif panel login/register menjadi teks biasa agar alur auth hanya memiliki satu `h1`; tes memastikan satu `h1` pada beranda, login, dan daftar.
- Memperbaiki overflow horizontal login yang ditemukan pada 320 px dengan menjadikan body login kolom responsif; uji browser tidak menemukan overflow pada 320, 360, 390, 768, atau 1280 px.
- Memperpendek motion saat `prefers-reduced-motion` dari 100 ms menjadi 1 ms; browser emulasi mengukur `0.001s`. Simulasi CSS zoom 200% pada lebar viewport 640 px tidak menghasilkan overflow.
- Membuat matriks per halaman di `docs/AUDIT_POLISH.md`; setiap sel menyatakan status/bukti, dan halaman yang belum diuji browser tidak ditandai lulus.

### Verifikasi dan pekerjaan tertunda

- Tes `AccessibilityStructureTest`: 1 lulus (18 assertions), termasuk layout publik/auth/user/admin dan satu `h1` di beranda/login/daftar.
- Suite lengkap: 158 lulus, 4 gagal (750 assertions); keempat kegagalan sama dengan baseline yang sudah tercatat di `AdminUserManagementTest` dan `UserTaskTest`, tanpa kegagalan baru.
- Browser login: skip link fokus pada Tab pertama, Enter memfokuskan main, dan ukuran 320/360/390/768/1280 px tanpa overflow. Tema gelap hanya diemulasi, bukan audit kontras penuh.
- Belum audit semua route dan komponen dengan keyboard/screen reader, screenshot enam halaman, target sentuh/kontras seluruh halaman, tema gelap penuh, serta Lighthouse. Fase 8 tetap terbuka.
- Build terakhir lulus; ukuran gzip saat ini JS 53,49 KB, CSS bersama 19,09 KB, CSS masuk 3,06 KB, CSS beranda 3,79 KB.

### Langkah berikutnya

Selesaikan sisa verifikasi Fase 8 yang dapat dijalankan di environment ini; Lighthouse dan screenshot sebelum perubahan perlu dicatat sebagai keterbatasan bila alat/baseline tidak tersedia. Fase 9 belum boleh ditandai selesai sebelum prasyarat fase sebelumnya ditinjau.

## Fase 9 — PWA, backup, dan operasi (sebagian terverifikasi)

- Metadata/ikon PWA, offline fallback, cache aset statis berhash, request navigasi tanpa penyimpanan HTML, halaman `/up`, dan pembersihan cache dinamis saat logout tersedia.
- SQLite memakai WAL, `busy_timeout`, foreign keys, dan synchronous NORMAL. Backup terkompresi/terenkripsi, retensi, scheduler 02:30, restore dengan validasi integrity, dan cadangan otomatis sebelum restore tersedia.
- `EncryptedSqliteBackupTest` lulus pada Windows setelah fixture menutup PDO eksternal sebelum penggantian file; tes membuktikan data SQLite dan foto kembali ke snapshot.
- Belum diuji install nyata di Android/iOS, kondisi offline pada perangkat, transfer salinan backup ke storage luar server, atau simulasi restore produksi. Fase 9 belum ditandai selesai operasional.

## Serah-terima akhir

Laporan ringkas per fase, bukti yang tersedia, batas pengukuran, perubahan, dan keputusan terbuka ada di `docs/LAPORAN_POLISH.md`. Jangan mengubah status "sebagian" menjadi "selesai" tanpa bukti tambahan dari production/browser/perangkat yang disebutkan di laporan.
