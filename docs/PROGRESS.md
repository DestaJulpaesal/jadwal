# Progress polish

## Hardening prompt — 16 November 2026

### Baseline and scope

- Work is on branch `chore/hardening-2026-11`, based on `main`.
- The existing `docs/ARSITEKTUR.md`, `docs/DESIGN_SYSTEM.md`, `docs/DEPLOY.md`, `docs/BACKUP.md`, and `docs/AUDIT_POLISH.md` were present and contain project-specific guidance; they will be updated where the hardening work changes behavior.
- The worktree already contained user-requested deletions of the old root planning files and the nested `list/` application. These remain outside this work and must not be staged or committed with hardening changes.
- Baseline before the CI fix: `php artisan test` passed 185 tests (1,263 assertions, 250.46 seconds); `npm run build` passed; `npm audit --audit-level=high` reported zero vulnerabilities.
- Baseline `Pint --test` found 18 formatting issues in 206 PHP files. The generated `vendor/bin/pint` proxy is malformed on this Windows/PHP 8.4 environment, so the package executable was run directly as `php vendor/laravel/pint/builds/pint --test`. Those 18 style issues were fixed; the subsequent Pint check passes all 206 files.
- The production JS bundle is 54.76 KB gzip, above the 50 KB budget required by Phase 4.

### Fase 0 — Baseline and CI

- Confirmed the CI PHP job does not build Vite assets and the test base did not disable Vite. Reproduced a 500 rendering `/login` with both `public/build` and `public/hot` temporarily moved aside, then restored them.
- Added `withoutVite()` in `Tests\TestCase::setUp()`; the same login-render test passes with both Vite outputs absent.
- Corrected the README: the daily digest is checked by the Laravel scheduler every minute, not every hour.
- The full suite passes after the change: 185 tests, 1,263 assertions. `npm run build`, `npm audit --audit-level=high` (zero advisories), and Pint `--test` (206 files) pass.
- Ran `composer audit` using a checksum-verified temporary Composer PHAR. It reports 55 advisories across 15 packages, including high and critical severity; resolving these requires the separately specified Laravel/dependency upgrade work. Do not treat the hardening branch as production-ready until the upgrade branch is merged and audited.
- Phase 0 documentation already existed and was reviewed. The README schedule mismatch is corrected; Composer audit remains a gate failure.

### Execution order

1. Complete Fase 0 gates and CI baseline.
2. Address Fase 1 data-loss and correctness bugs in isolated, tested changes, beginning with the task/category foreign key.
3. Continue Fase 1 through its full regression gate before starting Fase 2.
4. Proceed to Fase 3 and Fase 4 only after their prerequisite phase gates pass; do not mark production/device-only checks complete without evidence.

### Fase 1 — 1.1 kehilangan data kategori (diterapkan; fase belum selesai)

- Tes feature awal mereproduksi bug: menghapus kategori global menghapus tugas milik dua pengguna.
- Migration baru membangun ulang `tasks` di luar transaksi SQLite, mengubah FK kategori menjadi `SET NULL`, mempertahankan sequence ID, FK user/status, dan semua indeks.
- Controller menghitung tugas, kebiasaan, dan target; dialog menampilkan jumlah pemakaian dan feedback menyebut item tetap ada tanpa kategori.
- Tes migrasi skema lama lulus (9 assertions), termasuk pengulangan migration yang idempoten; tes feature penghapusan lintas pengguna lulus (7 assertions).
- Playbook dijalankan pada snapshot konsisten lokal: 0 tugas sebelum/sesudah, `integrity_check=ok`, tanpa pelanggaran FK. Database kerja tidak dimigrasikan karena masih memiliki migration lain yang pending.
- Risiko migrasi lama `make_categories_global` direproduksi pada SQLite terisolasi: menjalankannya di transaksi dapat menghapus tugas melalui cascade. Perbaikan baru tidak memulihkan data historis. Bukti backup sebelum migration diperlukan untuk menilai dampak database produksi.
- Pemeriksaan read-only database kerja menunjukkan migration lama sudah tercatat dan saat ini tidak ada baris tugas; tanpa snapshot sebelum migration, riwayat kehilangan data tidak dapat disimpulkan.
- Pemeriksaan setelah perubahan: 187 tes lulus (1.279 assertions), Pint `--test` lulus untuk 209 file, build Vite lulus, dan `npm audit` melaporkan nol vulnerability. `composer audit` masih merah dengan 55 advisory.
- Butir 1.1 selesai di kode dan tes; butir 1.2–1.4 diterapkan sesudahnya. Butir 1.5–1.12 belum dikerjakan. Fase 1 tidak ditandai selesai dan gate Composer tetap terbuka.

### Fase 1 — 1.2 cicilan lunas dan pengingat tagihan (diterapkan)

- `ObligationSchedule` menjadi sumber tunggal untuk periode terjadwal dan belum dibayar. Ia menghormati tanggal mulai, pembayaran yang tercatat, dan batas jumlah cicilan; cicilan yang jumlah pembayarannya mencapai total tidak menghasilkan periode lanjutan.
- Agenda, daftar tagihan, validasi pembayaran tertua, dan rencana pengeluaran uang memakai service tersebut.
- `attentionFor()` kini memakai `remind_days_before` per tagihan (default 3 hari, validasi 0–30), menghapus kondisi kemarin yang mati, membatasi riwayat overdue dengan konstanta 12 periode, dan menggabungkan tunggakan menjadi satu baris per tagihan, mis. `Terlambat 3 periode` atau `12+`.
- Tes regresi mencakup mulai setelah tanggal jatuh tempo bulanan, periode yang sudah dibayar, cicilan tiga kali, cicilan lunas di agenda/rencana uang/digest, pengingat individual, dan satu baris tunggakan teragregasi.
- Gerbang setelah perubahan: 196 tes lulus (1.333 assertions), Pint `--test` lulus untuk 215 file, dan Vite build lulus. Bundle JavaScript tetap 54,76 KB gzip; `composer audit` masih melaporkan 55 advisory.
- Butir 1.2 selesai di kode dan tes. Butir 1.5–1.12 belum dikerjakan; Fase 1 belum selesai.

### Fase 1 — 1.3 timestamp penyelesaian tugas (diterapkan)

- Service `TaskCompletion` menjadi satu aturan untuk timestamp penyelesaian: transisi ke selesai mencatat UTC saat ini, transisi keluar mengosongkan nilai, dan perubahan lain saat tetap selesai mempertahankan timestamp awal.
- Controller memakainya saat membuat, memperbarui, dan menyelesaikan tugas lewat aksi cepat. Tidak ada migration data karena opsi tersebut tidak diwajibkan dan tidak aman dijalankan pada database kerja yang belum dicadangkan.
- Tes regresi membuktikan pembukaan kembali mengosongkan `completed_at`, penyelesaian ulang menetapkan waktu transisi, pembaruan judul mempertahankan timestamp, dan streak tidak menghitung tugas yang dibuka kembali.
- Gerbang setelah perubahan: 189 tes lulus (1.297 assertions), tes terfokus Task/Streak lulus (31 tes, 154 assertions), dan Pint `--test` lulus untuk 210 file.
- Butir 1.3 selesai di kode dan tes; butir 1.5–1.12 belum dikerjakan. Fase 1 belum selesai.

### Fase 1 — 1.4 rentang bulan pada tanggal akhir bulan (diterapkan)

- `MonthRange::fromString()` memvalidasi format `YYYY-MM` dan memakai `!Y-m`; rentang bulan bersifat immutable dan menyediakan batas awal/akhir serta navigasi bulan sebelumnya/berikutnya.
- Controller uang dan laporan menggunakan satu rentang untuk query dan perbandingan. Query kategori pengeluaran teratas dipusatkan di `MonthlyCategoryReport`, mengelompokkan berdasarkan ID kategori dan memakai label tanpa kategori dari katalog bahasa. Cabang route mati dan parsing bulan di view dihapus.
- Tes unit mencakup akhir bulan, Februari kabisat, navigasi, dan format tidak valid. Tes feature mereproduksi bulan November pada 31 Desember, Februari pada 31 Maret, dan April pada 31 Mei; seluruh laporan dan bulan pembanding cocok.
- Pemeriksaan `createFromFormat('Y-m')` pada `app/` dan `resources/` tidak menemukan pemakaian.
- Gerbang setelah perubahan: 192 tes lulus (1.319 assertions), Pint `--test` lulus untuk 213 file, dan Vite build lulus. Bundle JavaScript tetap 54,76 KB gzip (ambang prompt 50 KB); `composer audit` tetap merah dengan 55 advisory.
- Butir 1.4 selesai di kode dan tes; butir 1.5–1.12 belum dikerjakan. Fase 1 belum selesai.

### Fase 1 — 1.5 preset kategori dan GET read-only (diterapkan)

- `MoneyController::index` tidak lagi membuat kategori pada GET. Preset disiapkan satu kali melalui `MoneyCategoryPresetSeeder` saat registrasi/pembuatan admin dan local seeder.
- Migration menambah `users.money_presets_seeded_at`, mengisi kategori preset untuk pengguna lama secara idempoten, dan hanya memproses baris yang belum ditandai. Rollback menghapus marker tetapi mempertahankan kategori karena dapat sudah dipakai atau diubah nama.
- Tes membuktikan preset tersedia setelah registrasi, nama preset yang diubah tidak muncul lagi saat halaman uang dibuka, GET tidak menulis ke `money_categories`, dan backfill dapat dijalankan ulang tanpa duplikasi atau kehilangan kategori.
- Migration diuji pada SQLite memory terisolasi, termasuk hitungan baris, `foreign_key_check`, dan `integrity_check`; `migrate:fresh --seed` serta status migration juga lolos pada berkas SQLite sementara. Database kerja tidak dimigrasikan.
- Gerbang setelah perubahan: 198 tes lulus (1.346 assertions), Pint `--test` lulus untuk 219 file, dan Vite build lulus. Composer audit tetap merah dengan 55 advisory.
- Butir 1.5–1.12 sudah diterapkan dan memiliki tes cakupan terkait; Fase 1 tetap belum boleh dinyatakan selesai sebelum gerbang dependency dan cakupan audit/test menyeluruh terpenuhi.

### Fase 1 — 1.6–1.12 (perbaikan diterapkan; gerbang fase masih terbuka)

- **1.6 Kebiasaan:** indeks menyediakan tab Aktif, Akan datang, Berakhir, dan Arsip; aksi check-in hanya tersedia di tab aktif dan server menolak check-in untuk kebiasaan yang belum mulai, berakhir, atau diarsipkan. Dashboard tidak diubah dan tetap hanya menampilkan kebiasaan aktif terjadwal hari ini.
- **1.7 Target:** tenggat lampau yang tidak berubah tidak lagi menghalangi edit; tenggat lampau baru tetap ditolak. `habit_id` kini menolak kebiasaan yang diarsipkan. Perbaikan target menurun dan tes regresinya diselesaikan bersama butir 2.2.
- **1.8–1.9 Keuangan/digest:** pesan piutang memakai sisa pokok. Digest mengisolasi kegagalan per pengguna, mencatat hanya `user_id` dan kelas exception, membatasi retry ke tiga kegagalan per tanggal lokal, tidak mengirim email kosong, dan tetap melanjutkan penerima berikutnya.
- **1.10 Akses/proxy:** `TRUSTED_PROXIES` menerima daftar IP/CIDR, wildcard `*`, atau default kosong; dokumentasi deployment diperbarui. Guest redirect memilih dashboard sesuai peran, redirect usang dihapus, admin dilarang dari policy tugas maupun model privat, dan semua route tugas diberi throttle.
- **1.11 SQLite:** `App\Support\Db::immediate()` memakai `BEGIN IMMEDIATE` pada SQLite untuk operasi goal, pembayaran utang/piutang/tagihan, dan seeding preset; driver lain memakai transaksi Laravel. Tes batas progres dan sisa pinjaman melengkapi tes transaksi berurutan yang merepresentasikan state sama.
- **1.12 Pembersihan:** wildcard `%`, `_`, `!`, dan `\` dicari sebagai teks literal; string status/jatuh tempo dan jenis agenda dipusatkan di `lang/id/ui.php`; validasi dashboard yang tidak dipakai dan field kategori habit yang tidak diproses dihapus. `WalletBalance::forWallet()` dipertahankan karena masih dipakai di `MoneyController`, `GoalProgress`, dan tes saldo. Relasi user pada `GoalProgress` memakai eager-loaded relation. `ActivityStreak` hanya membaca 400 hari terbaru; angka rekor di UI menjelaskan batas ini. View dashboard admin sengaja membungkus partial indeks akun.
- Validasi setelah semua butir fase: `php artisan test` **212 lulus (1.411 assertions)**; Pint **226 file lulus**; `npm run build` lulus (JS gzip **54,76 KB**, CSS terbesar gzip **19,75 KB**); `npm audit --audit-level=high` **0 vulnerability**.
- **Gerbang belum hijau:** audit Composer yang sudah dijalankan pada baseline melaporkan **55 advisory pada 15 paket**, termasuk severity tinggi/kritis. Composer CLI belum tersedia untuk audit ulang. Atas permintaan pengguna, pekerjaan Fase 2 dilanjutkan tanpa perubahan dependensi dan tanpa menjalankan migration pada database kerja; gerbang audit/upgrade tetap terbuka.

### Fase 2.1 — Siklus hidup entitas (diterapkan; tes terintegrasi lulus)

- Dompet, kategori uang, tagihan/cicilan, utang/piutang, kebiasaan, dan target kini menyediakan arsip/pulihkan. Target memiliki tab Berjalan, Tercapai, dan Arsip; kebiasaan mempertahankan tab aktif/akan datang/berakhir/arsip. Penghapusan permanen entitas lifecycle hanya tersedia dari tab Arsip; penghapusan transaksi manual juga selalu meminta konfirmasi.
- Transaksi manual dapat dihapus dan saldo dihitung ulang; transaksi turunan tidak dapat dihapus langsung dan mengarahkan pengguna ke pembatalan pembayaran pada entitas asal. Tagihan/pinjaman dengan pembayaran, dompet dengan transaksi, dan kategori yang masih dipakai ditolak saat dihapus. Kategori tetap dapat diubah namanya saat diarsipkan.
- Mutasi baru di-throttle dan semua aksi owner-scoped melalui policy yang ada serta pemeriksaan pemilik; tes antar-pengguna meliputi setiap jenis entitas. Agenda dan pengingat mengabaikan data terarsip; hasil pencarian yang cocok menampilkan label “Diarsipkan”.
- Kebijakan saldo: dompet terarsip dikecualikan dari total saldo aktif, tetapi saldo terhitung dan riwayat transaksinya tetap disimpan serta terlihat pada tab Arsip. Penghapusan permanen tanpa transaksi menghapus saldo awal; saldo nonnol ditampilkan sebagai peringatan.
- Tidak diperlukan migration untuk butir ini. Tes terfokus lifecycle, pencarian, kebiasaan, dan jadwal tagihan lulus; suite penuh pasca Fase 2.3 juga lulus. Pemeriksaan UI viewport masih perlu dilakukan. Composer audit tetap blocker untuk penutupan keseluruhan prompt.

### Fase 2.2 — Target dua arah dan koreksi progres (diterapkan; teruji)

- Migration menambah `goals.direction` dengan default `increase`; `goal_updates.amount` menjadi signed pada MySQL (SQLite menerima nilai negatif pada kolom integer yang ada). Rollback menolak berjalan bila sudah ada progres negatif. Migration hanya dijalankan dalam database tes terisolasi, bukan database kerja.
- `GoalProgress` menghitung persen, sisa nilai, dan progres harian untuk arah naik maupun turun. Validasi menjaga urutan nilai awal/target dan membatasi progres agar tidak melewati target searah atau kurang dari nol.
- Catatan progres menerima delta bertanda, dan target menampilkan riwayat; hanya catatan terbaru yang dapat di-undo dengan konfirmasi dan policy `GoalUpdate`. Undo yang membuat target tercapai kembali menjadi status Berjalan.
- Tes arah turun 80→70, koreksi, batas target, undo terbaru, transisi status, validasi rentang, kepemilikan, dan regresi target naik lulus (`GoalDirectionTest`: 3 tes, 34 assertion).

### Fase 2.3 — Anggaran bulanan per kategori (diterapkan; gerbang UI masih terbuka)

- Tabel `budgets` menyimpan batas per pengguna, kategori pengeluaran, dan bulan unik. `SaveBudgetRequest` membatasi kategori aktif milik pengguna, mencegah duplikat, dan memvalidasi nominal positif.
- `BudgetProgress::forMonth` menghitung pengeluaran manual termasuk `source_type = NULL`, mengecualikan pinjaman/pembayaran/penyesuaian/transfer, serta membatasi hasil ke pemilik dan rentang bulan. Pemuatan kategori dilakukan sekaligus; tes jumlah query tetap tiga saat kategori bertambah (tanpa N+1).
- CRUD anggaran tersedia di halaman Keuangan dan Laporan, termasuk indikator progres dengan warna dan label status serta aksi salin bulan sebelumnya yang idempoten dan tidak menimpa nilai yang ada. Kategori yang masih memiliki anggaran tidak dapat dihapus permanen.
- Dashboard menambahkan peringatan untuk batas ≥80% dan ≥100% tanpa menampilkan nominal pada item perhatian; peringatan mengikuti pilihan bagian Keuangan dashboard.
- `BudgetProgressTest`: 7 tes, 47 assertion; mencakup batas tanggal awal/akhir bulan, sumber transaksi yang dikecualikan, kategori tanpa anggaran, isolasi pemilik, CRUD, salin idempoten, dashboard, proteksi kategori, dan jumlah query. Migration diuji pada database tes terisolasi oleh suite; database kerja tidak dimigrasikan.
- Suite penuh pada implementasi backend: **228 lulus (1.567 assertion)**; tes fokus 7/47 diulang dan lulus sesudahnya. Pint untuk PHP terkait, kompilasi Blade, dan Vite build lulus. JS gzip **54,76 KB**, masih melewati target prompt 50 KB. Pemeriksaan manual UI 360/1280px dan mode terang/gelap belum dilakukan; audit Composer tetap blocker.

### Fase 2.4 — Ekspor data CSV (sudah tersedia dan diverifikasi)

- Implementasi yang sudah ada menyediakan ekspor transaksi dengan rentang maksimal lima tahun, tugas, kebiasaan, dan log kebiasaan melalui stream; berkas memakai BOM UTF-8 dan header Indonesia.
- Query dibatasi ke pengguna aktif, transaksi bersumber user lain disaring dari relasi, teks CSV dengan prefix formula dinetralkan, route dibatasi lima permintaan per menit, dan log hanya menyimpan ID pengguna serta jenis ekspor.
- Verifikasi fokus: `DataExportTest` lulus (6 tes), termasuk isi kolom, injeksi CSV, rentang tanggal, rate limit, dan akses.

### Fase 2.5 — Tugas berulang (sudah tersedia dan diverifikasi)

- Implementasi yang sudah ada membuat kejadian berikutnya secara idempoten ketika tugas diselesaikan lewat aksi cepat maupun edit, mempertahankan waktu lokal dan durasi, serta menangani beberapa hari mingguan dan jangkar tanggal bulanan tanpa overflow.
- Verifikasi fokus: `TaskRecurrenceTest` lulus (7 tes). Tambahan tes di checkpoint ini membuktikan jam lokal tetap 09.30 pada `America/New_York` saat transisi DST; tes yang ada mencakup `Asia/Jakarta` dan `Asia/Jayapura`, akhir bulan, batas pengulangan, dan idempotensi.

### Fase 2.6 — Penyempurnaan tugas (sudah tersedia dan diverifikasi)

- Implementasi yang sudah ada menyediakan pengurutan default, sort pilihan, filter kategori, aksi massal yang atomik dan terotorisasi per tugas, serta tautan hasil pencarian tugas langsung ke edit.
- Verifikasi fokus: `TaskManagementTest` dan `GlobalSearchTest` lulus (9 tes), termasuk isolasi kepemilikan dan penolakan aksi massal campuran.

### Fase 2.7 — Penyesuaian saldo dompet (diterapkan)

- Aksi pada dompet aktif membandingkan saldo aktual dengan saldo hitung di dalam `Db::immediate`; selisih positif/negatif dicatat sebagai transaksi pemasukan/pengeluaran bertipe `adjustment`. Nominal tetap Rupiah integer, selisih nol tidak membuat transaksi, dan selisih di atas batas satu transaksi ditolak.
- Penyesuaian ditampilkan dengan label khusus, tidak dapat diedit, tetapi dapat dihapus dengan konfirmasi seperti transaksi manual. Nilainya tetap memengaruhi saldo dompet, tetapi dikecualikan dari ringkasan bulanan, laporan kategori, dan anggaran. Saldo pada formulir menghormati mode privasi.
- Tidak ada migration. `WalletAdjustmentTest` lulus (6 tes, 42 assertion), mencakup hitung saldo dua arah, nol, laporan, edit/hapus, validasi batas, dan akses dompet lain/arsip.
- Cek terfokus untuk ekspor, tugas berulang, penyempurnaan tugas, pencarian, serta penyesuaian saldo seluruhnya lulus. Pint file terkait dan Vite build lulus; bundle JS gzip **55,00 KB**, tetap di atas anggaran 50 KB.
- Gate penuh Fase 2 belum lulus. Pemeriksaan UI manual mobile/desktop mode terang/gelap belum dilakukan; audit dependency dan satu temuan format tetap blocker.

### Pemeriksaan menyeluruh checkpoint awal — 18 November 2026 (sebelum hardening frontend)

- `php artisan test`: **253 lulus (1.737 assertion)** pada checkpoint ini; hasil final sesudah hardening dicatat pada bagian Fase 4.1–4.4 di bawah.
- Build Vite lulus dengan JS gzip **55,00 KB**, masih melewati target 50 KB pada checkpoint ini; ukuran terbaru dicatat pada bagian Fase 4.1–4.4.
- Pint pada seluruh repo menemukan satu masalah urutan import di `app/Providers/AuthServiceProvider.php`; file tersebut tidak diubah dalam pekerjaan penyesuaian saldo.
- `npm audit fix` memperbarui dependency yang kompatibel di lockfile, tetapi `npm audit --audit-level=high` masih gagal dengan 12 high dan 3 moderate. Advisory Picomatch yang memicu temuan high menyatakan belum ada versi patched; tidak melakukan downgrade `--force` yang berisiko.
- Composer CLI tidak tersedia; audit dijalankan dengan Composer 2.11.2 PHAR sementara yang checksum installer-nya diverifikasi. Hasilnya **55 advisory pada 15 paket**, termasuk high/kritis; upgrade framework tetap terbuka.
- Uji tampilan manual 360/1280px serta mode terang/gelap belum dilakukan; jangan anggap pemeriksaan browser sebagai selesai.

### Commit hardening

- `667ed4f` — `fix(ci): isolate feature tests from Vite assets`.
- `6199b82` — `style: normalize PHP baseline formatting`.
- `f3d8e77` — `docs: record hardening baseline and correct digest schedule`.
- `ad73739` — `fix(categories): preserve tasks when deleting global categories`.
- `10b8d70` — `fix(tasks): clear completion timestamp when reopened`.
- `3e386fc` — `fix(reports): use safe calendar month ranges`.
- `14de85c` — `fix(obligations): cap completed installment schedules`.
- `548b57a` — `fix(money): seed category presets once per user`.
- `a96c2d8` — `test(tasks): cover recurrence across DST`.
- `bff1b38` — `feat(money): add wallet balance adjustments`.
- `69890ac` — `chore(deps): refresh npm lockfile` (audit masih terblokir advisory upstream).

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

- Metadata/ikon PWA, offline fallback, cache aset statis berhash, request navigasi tanpa penyimpanan HTML, dan halaman `/up` tersedia. Tidak ada cache dinamis yang dibuat atau perlu dihapus saat logout.
- SQLite memakai WAL, `busy_timeout`, foreign keys, dan synchronous NORMAL. Backup terkompresi/terenkripsi, retensi, scheduler 02:30, restore dengan validasi integrity, dan cadangan otomatis sebelum restore tersedia.
- `EncryptedSqliteBackupTest` lulus pada Windows setelah fixture menutup PDO eksternal sebelum penggantian file; tes membuktikan data SQLite dan foto kembali ke snapshot.
- Belum diuji install nyata di Android/iOS, kondisi offline pada perangkat, transfer salinan backup ke storage luar server, atau simulasi restore produksi. Fase 9 belum ditandai selesai operasional.

## Hardening lanjutan — Fase 4.1–4.4 (diterapkan; gerbang keseluruhan masih terbuka)

- **4.1 Bundle:** Axios dihapus dari dependency dan bootstrap; aplikasi tetap memakai `fetch`. CSS publik dipisah dari CSS legacy aplikasi; komponen tombol dipakai bersama agar tampilan login tetap konsisten. CSS legacy yang tidak direferensikan dihapus setelah inventaris source. `npm run check:bundle` memeriksa ukuran gzip halaman publik dan aplikasi dalam byte, dan dijalankan di CI. Build terakhir: JS **33,49 KB**, CSS halaman terberat **19,86 KB**.
- **4.2 Service worker:** versi cache berasal dari SHA-256 byte `manifest.json`; versi kandidat dicatat saat install, lalu activate memilih cache tersebut dan menghapus cache aset versi lama. Hanya aset berhash serta fallback offline disimpan; halaman navigasi tetap network-only. Pesan `CLEAR_DYNAMIC_CACHES` dihapus karena cache dinamis tidak pernah dibuat.
- **4.3 Aksi optimistik:** respons 401/419 menampilkan pesan sesi berakhir beserta tautan masuk; 422 menampilkan kesalahan validasi pertama atau instruksi pemeriksaan data. Pesan/status memakai data-atribut terlokalisasi, kegagalan jaringan memakai pesan lokal, fokus tombol dipulihkan, status sukses memakai `aria-live`, dan hitungan kebiasaan dashboard diperbarui.
- **4.4 Privasi:** preferensi server ditetapkan pada elemen `<html>` sebelum stylesheet; CSS menyembunyikan nominal sampai inisialisasi JS selesai. Pilihan pengguna dibaca/disimpan pada `sessionStorage` dalam `try/catch` dan kegagalan storage dicatat secara eksplisit.
- Tes penuh terbaru setelah perbaikan aksesibilitas: **256 lulus (1.761 assertion)**; tes terarah terbaru `AccessibilityStructureTest`: **2 lulus (27 assertion)**; Pint **247 file lulus**; build Vite dan pemeriksaan budget lulus. Screenshot beranda dicek pada 360px dan login pada 360/1280px.
- **Gate tetap merah:** setelah `npm audit fix` non-forced, audit npm melaporkan **13 high dan 0 moderate**. Advisori Picomatch yang tersisa belum memiliki versi patched menurut [GHSA-fc5q-39xc-vjpf](https://github.com/advisories/GHSA-fc5q-39xc-vjpf); `--force` mengusulkan downgrade Vite dan tidak diterapkan. Audit Composer terakhir tetap **55 advisory pada 15 paket**. Uji mode gelap terautentikasi dan interaksi optimistic di browser belum dilakukan.
- CSS 4.7 baru mencakup inventaris awal dan penghapusan selector yang tidak muncul di source runtime; verifikasi visual per halaman dan migrasi CSS legacy lanjutan belum selesai.
- Masih terbuka: upgrade Laravel/dependensi Fase 3, Fase 4.5–4.7, verifikasi perangkat PWA dan seluruh matriks UI/aksesibilitas, serta blocker audit dependency. Prompt keseluruhan belum selesai.

## Fase 4.5 — Fokus dan reduced motion (perbaikan terarah; audit menyeluruh terbuka)

- Browser check pada halaman login mengonfirmasi `scroll-behavior: smooth` tetap aktif saat `prefers-reduced-motion: reduce`. Aturan kini mengubahnya menjadi `auto`.
- Fokus keyboard memakai token tema: `#226b50` pada terang dan `#b7d4c2` pada gelap. Rasio kontras terhadap surface masing-masing **6,39:1** dan **8,34:1**. Token juga dipakai oleh tombol, halaman publik, dan aksi dashboard yang sebelumnya menetapkan warna fokus sendiri.
- `AccessibilityStructureTest` diperluas untuk mengunci aturan fokus/reduced-motion; tes terarah **2 lulus (27 assertion)**. Browser memverifikasi skip link menerima fokus, scroll otomatis nonaktif di reduced motion, dan warna fokus aktual sesuai kedua tema.
- Ini hanya perbaikan fondasi yang dapat diverifikasi lokal; audit kontras seluruh komponen/halaman, keyboard dan screen reader lintas route, label/form, target sentuh, serta modal focus trap tetap terbuka.

## Fase 3.2 — Fallback error tanpa nonce CSP (perbaikan terarah)

- Layout dasar sekarang menambahkan atribut `nonce` hanya bila middleware sudah membagikan `$cspNonce`. Halaman error tetap dapat dirender ketika `SecurityHeaders` gagal sebelum nonce tersedia.
- Tes regresi menggunakan mode CSP invalid, memastikan nonce memang belum dibagikan, respons 500 memakai halaman error aplikasi, dan inline script tidak memperoleh atribut nonce kosong. Tes CSP report yang sudah ada tetap memastikan nonce hadir pada request normal.
- `.env.example` kini memulai CSP pada `report`; `docs/CSP.md` menjelaskan review channel `csp` dan syarat sebelum `enforce`. Observasi CSP lintas-halaman di deployment dan keputusan beralih ke `enforce` tetap terbuka.
- Verifikasi terbaru: suite penuh **263 lulus (1.793 assertion; 340,80 detik)**, Pint **248 file lulus**, `php artisan view:cache`, `git diff --check`, dan parse YAML workflow CI berhasil. Build dan budget gzip lulus (**JS 33,49 KB; CSS aplikasi terberat 19,86 KB**).

## Fase 3.3 — Backup, status, dan restore (diterapkan; latihan produksi masih terbuka)

- `EncryptedSqliteBackup::status()` melaporkan jumlah arsip terenkripsi, total byte, nama/umur arsip terbaru, dan tidak membuat direktori yang belum ada.
- `app:backup-status` berakhir gagal dan mencatat `critical` bila arsip tidak ada atau lebih tua dari 36 jam; dijadwalkan setiap hari pukul 03:30 setelah backup pukul 02:30. Dokumen backup/deploy diperbarui.
- Restore kini checkpoint WAL, menolak sidecar yang bukan file biasa, menghapus `-wal`/`-shm` sebelum pertukaran database, dan memastikan tidak ada sidecar tersisa setelah install. Tes integrasi membuktikan data/foto dipulihkan dan sidecar lama dibersihkan di database sementara.
- Tes terarah terbaru: `EncryptedSqliteBackupTest` **4 lulus (24 assertion)**. Latihan restore produksi/kuartalan, transfer arsip ke lokasi offsite, dan bukti backup production >36 jam masih menjadi pekerjaan operasional.

## Fase 3.5 — Robots, cache aset, dan konfigurasi host (diterapkan; Apache produksi belum diverifikasi)

- `robots.txt` sekarang melarang `/dashboard`, `/admin`, `/profile`, dan `/settings`.
- `public/.htaccess` menetapkan cache immutable satu tahun hanya untuk nama aset Vite ber-hash di `/build/assets/`; panduan deploy mencantumkan modul Apache yang dibutuhkan dan aturan yang sama.
- Boot production menulis warning tanpa membocorkan URL bila `APP_URL` menunjuk ke `localhost`, alamat loopback IPv4/IPv6, atau subdomain `.localhost`. Tes mencakup kasus localhost.
- Tes terarah: `PwaAndHealthTest` **3 lulus (41 assertion)**; `SecurityHeadersTest` **9 lulus (45 assertion)**. Konfigurasi Apache belum dijalankan pada server Apache production.

## Fase 3.6 — CI (sebagian diterapkan; Larastan dan CI remote terbuka)

- Workflow PHP kini menjalankan `migrate:fresh --seed --force` pada SQLite setelah menyiapkan database.
- Job terpisah menjalankan `TaskCategoryForeignKeyMigrationTest` untuk fixture skema/data lama. Cache Composer, cache npm, dan pemeriksaan budget bundle tetap tersedia.
- YAML workflow berhasil diparse lokal. Migrasi fresh dan seeding juga berhasil pada berkas SQLite sementara, dengan `PRAGMA integrity_check` menghasilkan `ok`; database kerja tidak disentuh.
- Belum ada job Larastan level 5/baseline dan belum ada hasil run GitHub Actions pada checkout bersih. Gate keseluruhan juga tetap merah sampai audit dependensi lulus.

## Serah-terima akhir

Laporan ringkas per fase, bukti yang tersedia, batas pengukuran, perubahan, dan keputusan terbuka ada di `docs/LAPORAN_POLISH.md`. Jangan mengubah status "sebagian" menjadi "selesai" tanpa bukti tambahan dari production/browser/perangkat yang disebutkan di laporan.

## Fase 3.1 — Lompatan Laravel 10 ke 11 (checkpoint sementara; sudah dilampaui)

- Upgrade dilakukan pada branch terpisah `chore/upgrade-laravel`. Dependensi mengikuti panduan resmi Laravel 11: PHP minimum 8.2, framework 11, Breeze 2, Sanctum 4, Collision 8, dan `doctrine/dbal` dihapus. Struktur bootstrap Laravel 10 dipertahankan karena tetap didukung.
- Matriks CI berubah ke PHP 8.2–8.4. `config.platform.php` dikunci ke 8.2.0 supaya lockfile tidak memilih paket Symfony yang hanya bisa dipasang di PHP 8.4 pada runner lokal.
- Carbon 3 mengembalikan beda tanggal sebagai angka pecahan/bertanda dan menolak `timezone(null)`. Model `User` kini membawa zona waktu default Jakarta; hitungan hari/periode menjadi integer dengan tanda eksplisit; durasi tugas berulang dihitung dari timestamp agar jadwal lokal tetap benar melewati DST.
- Listener verifikasi email otomatis Laravel 11 kini tetap mengikuti `AUTH_EMAIL_VERIFICATION`. Tes rollback jadwal juga menurunkan indeks fase berikutnya sebelum migration kolom, tanpa mengubah migration historis; tes header PWA tidak bergantung pada kapitalisasi charset.
- Verifikasi: `php artisan test --compact` **263 lulus (1.792 assertion)**; Pint lulus; `migrate:fresh --seed --force` di SQLite sementara menghasilkan `integrity=ok` dan nol pelanggaran FK; `composer validate --strict` dan pemeriksaan platform lock lulus. `npm run build`, bundle budget (JS 33,49 KB/CSS 19,86 KB gzip), serta `npm audit --audit-level=high` lulus.
- **Gate Composer pada checkpoint Laravel 11 berwarna merah:** `composer audit` melaporkan 5 advisori untuk `laravel/framework`, termasuk CVE-2026-48019 yang mencakup seluruh rentang Laravel 11 dan tidak memiliki versi perbaikan Laravel 11. Resolver normal menolak framework 11; `--no-security-blocking` hanya dipakai untuk menguji hop perantara sesuai urutan upgrade. Checkpoint Laravel 11 itu tidak layak dirilis/di-merge; hop Laravel 12 di bawah ini berhasil membersihkan advisori. Rangkaian kemudian dilanjutkan ke Laravel 13, dan audit akhir kini bersih.

## Fase 3.1 — Lompatan Laravel 11 ke 12 (checkpoint; sudah dilampaui)

- Framework dinaikkan ke Laravel 12 dan PHPUnit ke 11; PHP minimum/`config.platform.php` tetap 8.2.0 pada hop ini agar lockfile tetap kompatibel dengan matriks CI 8.2–8.4. Composer memilih Laravel 12.73.0 dan PHPUnit 11.5.58.
- Migrasi perilaku Laravel 11 dan perbaikan Carbon 3 tetap dipertahankan. Provider data pada smoke test rute menggunakan atribut PHPUnit 11, menghapus peringatan metadata doc-comment yang sudah deprecated.
- Verifikasi: `php artisan test --compact` **263 lulus (1.792 assertion)**; tes provider rute setelah migrasi ke atribut PHPUnit **15 lulus** tanpa peringatan deprecation; Pint, `composer validate --strict`, `composer check-platform-reqs --lock`, `composer audit`, `npm run build`, pemeriksaan budget bundle (JS 33,49 KB/CSS 19,91 KB gzip), dan `npm audit --audit-level=high` lulus. `migrate:fresh --seed --force` di SQLite sementara menghasilkan `integrity=ok` dan nol pelanggaran FK.
- Pada checkpoint ini, lompatan berikutnya adalah Laravel 13 dengan minimum PHP 8.3; hasil dan gerbang akhir tercatat di bagian berikut.

## Fase 3.1 — Lompatan Laravel 12 ke 13

- Framework dinaikkan ke Laravel 13.43.0, Tinker ke 3.2.0, dan PHPUnit ke 12.5.44. PHP minimum serta `config.platform.php` menjadi 8.3/8.3.0; matriks CI kini PHP 8.3–8.4. README dan panduan deployment diselaraskan.
- Middleware CSRF aplikasi kini mewarisi `PreventRequestForgery`, sehingga pemeriksaan origin Laravel 13 aktif tanpa memakai alias deprecated. Config cache melarang unserialisasi kelas karena cache aplikasi hanya menyimpan scalar; prefix cache, Redis, dan nama cookie sesi tetap mengikuti format eksplisit sebelumnya.
- `config/session.php` menetapkan serialisasi `php` untuk mempertahankan kompatibilitas sesi aktif saat deploy; ini sengaja menghindari perubahan format ke JSON yang akan meminta pengguna masuk lagi.
- Verifikasi: `php artisan test --compact` **263 lulus (1.792 assertion)**; Pint, `composer validate --strict`, `composer check-platform-reqs --lock`, `composer audit` tanpa advisori, `npm run build`, budget bundle (JS 33,49 KB/CSS 19,91 KB gzip), dan `npm audit --audit-level=high` (nol advisori) lulus. Migrasi + seeding SQLite sementara memberi `integrity=ok` dan nol pelanggaran FK; fixture migrasi dengan data lama juga termasuk dalam suite.
- Gerbang telah lulus secara lokal pada PHP 8.4.27; GitHub Actions dan runtime PHP 8.3 belum dijalankan dari checkout bersih karena branch belum dipush. Jalankan CI hosted pada branch sebelum merge.

## Redesign halaman tugas — 19 November 2026

- Daftar tugas mendapat tab cepat untuk Semua, Hari ini, Akan datang, Terlambat, dan Selesai beserta jumlah masing-masing.
- Pencarian dan filter lanjutan dipindahkan ke panel yang dapat ditutup sehingga daftar lebih ringkas dan mudah dipindai.
- Header mobile menjaga tombol Tambah tugas tetap mudah dijangkau; kartu tugas mempertahankan checkbox massal, status, jadwal, kategori, penyelesaian cepat, edit, dan hapus.
- Server development dan cache Laravel dibersihkan/dipanas-kan setelah reload yang lambat. Pengukuran login berulang setelah cache stabil berkisar sekitar 0.3–0.6 detik tanpa proxy; jalur dashboard guest juga sekitar 0.3–0.4 detik setelah permintaan pertama.
- Tes fitur TaskManagement dan UserTask: **32 lulus, 166 assertion**. Suite penuh sebelumnya: **263 lulus, 1.795 assertion**.
- Build Vite dan JavaScript lulus; pemeriksaan CSS tetap melaporkan aplikasi 21.79 KB gzip terhadap budget lama 20 KB, sehingga penyempurnaan bundle/CSS legacy masih terbuka.

## Penutupan operasional — 19 November 2026

- Larastan 3.15 ditambahkan pada level 5 beserta `phpstan.neon`; CI menjalankan Larastan pada job PHP.
- Ditambahkan throttle terpisah untuk permintaan reset kata sandi (`password.email`, `password.store`) dengan batas lima percobaan per menit per IP.
- Ditambahkan tabel audit akun yang menyimpan hanya aksi, waktu, IP, dan user-agent. Perubahan email, perubahan kata sandi, dan penghapusan akun kini mencatat aksi; tidak ada email, kata sandi, isi formulir, atau data keuangan yang disimpan.
- Backup timestamped dibuat sebelum migration, dan migration `2026_11_19_210000_create_user_audit_logs_table` berhasil dijalankan pada database kerja.
- Verifikasi terbaru: **263 tes lulus dengan 1.795 assertion**; Pint lulus untuk 248 file; Larastan level 5 lulus; `composer audit` tidak menemukan advisori; `npm audit --audit-level=high` menemukan nol advisori; build Vite dan batas bundle lulus (JS 33,49 KB gzip, CSS 19,91 KB gzip).
- Tes profil/password terarah **15 lulus dengan 77 assertion**, termasuk pencatatan perubahan email, kata sandi, dan penghapusan akun.
- Pemulihan error login: migration anggaran sudah tercatat dan tabel `budgets` terbukti tersedia pada database aktif; `PRAGMA integrity_check` mengembalikan `ok`, tidak ada migration tertinggal.
- Pekerjaan yang masih memerlukan bukti di luar repo: run GitHub Actions pada checkout bersih, hasil Apache production, smoke browser terautentikasi di mode gelap, Lighthouse/screen-reader seluruh route, install PWA pada perangkat nyata, transfer backup offsite, dan latihan restore produksi.
