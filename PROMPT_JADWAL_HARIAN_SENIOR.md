# MASTER PROMPT — Jadwal Harian: Perbaikan, Fitur Baru, dan Hardening (level Senior Full-Stack)

Tempel seluruh isi file ini ke Claude Code dari root proyek `jadwal/`. Dokumen ini adalah **spesifikasi kerja**, bukan sekadar daftar tugas: ikuti urutan, gerbang kualitas, dan format laporan di dalamnya.

---

## 0. PERAN DAN MODE KERJA

Kamu adalah **Staff/Senior Full-Stack Engineer** (Laravel, SQLite, Blade/Alpine/Tailwind, PWA, keamanan aplikasi, UX) yang mengambil alih proyek ini untuk dibawa ke kondisi **siap produksi**. Standar yang diharapkan:

- **Berbasis bukti.** Jangan menebak. Setiap klaim bug harus direproduksi lewat tes yang gagal; setiap perbaikan dibuktikan tes yang hijau.
- **Minimal tapi tuntas.** Ubah sesedikit mungkin untuk menyelesaikan akar masalah, tapi jangan tambal gejala. Cari semua tempat dengan pola bug yang sama (`grep`) sebelum menutup sebuah butir.
- **Aman untuk data.** Aplikasi ini menyimpan data keuangan pribadi. Tidak boleh ada perubahan yang bisa menghilangkan atau mengubah data pengguna tanpa jalur pemulihan.
- **Jelas untuk orang berikutnya.** Kode, nama, pesan commit, dan dokumentasi harus bisa dipahami pengembang lain tanpa konteks percakapan ini.
- **Mandiri.** Jangan berhenti untuk bertanya hal yang bisa diputuskan secara konservatif. Tulis asumsi di `docs/PROGRESS.md` dan lanjutkan. Berhenti hanya pada "kondisi berhenti" (bagian 11).

---

## 1. PETA PROYEK DAN INVARIAN (WAJIB DIPAHAMI SEBELUM MENGUBAH APA PUN)

**Stack:** Laravel 10 · PHP ≥ 8.1 · SQLite (WAL, `busy_timeout` 5000, FK aktif) · Blade + `@alpinejs/csp` + Tailwind 3 + Vite 6 · PWA (service worker manual) · PHPUnit 10 (±145 tes).

**Struktur penting**
- `app/Http/Controllers/User/*` — modul pengguna (Task, Habit, Goal, Money, Obligation, Loan, Report, Search, Streak). `AdminController`, `AdminCategoryController`, `SettingsController`, `ProfileController`.
- `app/Services/*` — logika domain: `AgendaService`, `ObligationScheduler`, `WalletBalance`, `MonthlySummary`, `GoalProgress`, `HabitStreakCalculator`, `ActivityStreak`, `LoanBalance`, `EncryptedSqliteBackup`.
- `app/Policies/*` — `TaskPolicy`, `CategoryPolicy`, `OwnedModelPolicy` (dipetakan di `AuthServiceProvider`).
- `app/Support/*` — `Rupiah`, `UserClock`, `Feedback`, helper global.
- `resources/views/components/*` — design system Blade (`x-button`, `x-field`, `x-empty-state`, `x-confirm-action`, `x-segmented`, `x-tabs`, `x-progress-bar`, dst.). Token di `resources/css/tokens.css`.
- `lang/id/*` — seluruh teks UI dan pesan validasi (`validation.catalog.*`).

**Invarian yang tidak boleh dilanggar**
1. **Waktu:** kolom datetime tugas (`due_date`, `starts_at`, `ends_at`, `completed_at`) disimpan **UTC**; konversi ke/dari `users.timezone` hanya di batas I/O. Tanggal bisnis (`occurred_on`, `starts_on`, `ends_on`, `due_on`, `loan_date`, `log_date`) adalah **tanggal lokal user sebagai string `Y-m-d`** (lihat trait `CastsBusinessDates`). Jangan mencampur keduanya.
2. **Uang:** integer Rupiah (`bigInteger`), selalu lewat `App\Support\Rupiah`. Tidak ada float. Batas `Rupiah::MAX_AMOUNT`.
3. **Isolasi data:** semua query data pribadi harus berawal dari `$request->user()->relasi()` atau punya `where user_id`; setiap route model binding dilindungi `authorize()`/Policy. Rujukan ID di request (wallet, kategori, kebiasaan) harus divalidasi `Rule::exists(...)->where('user_id', ...)`.
4. **Sumber transaksi:** transaksi dengan `source_type` (`loan`, `loan_repayment`, `obligation_payment`) dimiliki entitas sumbernya; tidak boleh diedit/dihapus langsung dari UI transaksi. `MonthlySummary` dan laporan kategori mengecualikan `loan`/`loan_repayment`.
5. **Lazy loading dilarang** di non-produksi (`Model::preventLazyLoading`). Eager-load semua relasi.
6. **Teks UI** hanya lewat `lang/id/*`. Tidak ada string Indonesia baru yang di-hardcode di controller/service/JS (kecuali yang sudah ada dan memang masuk daftar pembersihan).
7. **SQLite:** `lockForUpdate()` adalah **no-op** di SQLite. Jangan mengandalkannya untuk mencegah balapan; andalkan unique index, pengecekan ulang di dalam transaksi, dan idempotensi.
8. **Migration:** tidak boleh mengedit migration lama yang sudah dipakai. Selalu tambah migration baru yang idempoten dan bisa jalan di DB kosong **dan** di DB berisi data. `PRAGMA foreign_keys` tidak berefek di dalam transaksi.
9. **CSP:** tidak ada inline script/style tanpa nonce; pakai `@alpinejs/csp` (tanpa ekspresi kompleks di atribut `x-*`; logika masuk `Alpine.data` di `resources/js/components.js`).

---

## 2. ALUR KERJA, BRANCH, DAN GERBANG KUALITAS

**Branch & commit**
- Branch kerja: `chore/hardening-2026-11` dari branch utama. Upgrade framework di branch terpisah `chore/upgrade-laravel` (Fase 3.1).
- Satu commit per butir. Format: `fix(scope): ...`, `feat(scope): ...`, `test(scope): ...`, `chore(scope): ...`, `docs: ...`. Badan commit menjelaskan *mengapa* dan dampak data.

**Siklus per bug (TDD)**
1. Tulis tes yang **gagal** dan mereproduksi bug (jalankan, tunjukkan kegagalannya).
2. Perbaiki akar masalah.
3. Tes hijau. Jalankan seluruh suite.
4. `grep` pola serupa di seluruh repo dan perbaiki/dokumentasikan.
5. Commit.

**Siklus per fitur**
1. Tulis mini-spesifikasi di `docs/PROGRESS.md` (model data, route, aturan bisnis, kasus tepi).
2. Migration + model + Policy → Request class → controller/service → view → i18n → tes.
3. Uji manual UI dengan `php artisan serve` + `npm run dev` (mobile 360px dan desktop), termasuk mode gelap.

**Gerbang kualitas (harus lolos di akhir tiap fase, tidak boleh lanjut jika merah)**
```
vendor/bin/pint --test
php artisan test
npm run build
npm audit --audit-level=high
composer audit
```

**Playbook migration (wajib untuk setiap migration yang menyentuh data/skema)**
1. Salin DB lokal: `cp database/database.sqlite /tmp/pre.sqlite` (dan buat DB berisi data uji besar via seeder/factory).
2. Jalankan `php artisan migrate` pada salinan; verifikasi `PRAGMA foreign_key_check;` dan `PRAGMA integrity_check;` kosong/`ok`.
3. Bandingkan jumlah baris tabel terdampak sebelum/sesudah.
4. Pastikan `migrate:fresh --seed` dari nol juga lolos.
5. Tulis `down()` yang jujur (atau komentar eksplisit bila tidak reversibel).

---

## 3. FASE 0 — BASELINE, CI, DAN DOKUMENTASI

**0.1 Baseline.** `composer install`, `npm ci`, siapkan `.env` + SQLite, `php artisan key:generate`, `php artisan migrate --seed`, `php artisan test`, `npm run build`. Simpan hasilnya (jumlah tes, waktu, peringatan) di `docs/PROGRESS.md` bagian "Baseline".

**0.2 CI.** Dugaan: `tests/TestCase.php` tidak memanggil `withoutVite()` sementara layout memakai `@vite`, dan job PHP di `.github/workflows/ci.yml` tidak build aset → tes yang merender view gagal pada checkout bersih. Verifikasi dengan `rm -rf public/build && php artisan test`. Perbaiki di `TestCase::setUp()` (`$this->withoutVite();`). Pastikan `php artisan test` hijau tanpa `public/build`.

**0.3 Dokumen yang dirujuk README tapi tidak ada.** Buat dengan isi nyata (bukan placeholder), berdasarkan kode:
- `docs/ARSITEKTUR.md` — diagram alur (request → middleware → controller → service → model), daftar service dan tanggung jawabnya, aturan waktu & uang, model data (ERD teks), keputusan desain (kategori tugas global, sumber transaksi).
- `docs/DESIGN_SYSTEM.md` — token, komponen Blade + contoh pemakaian, panduan membuat halaman baru, aturan a11y.
- `docs/DEPLOY.md` — env produksi lengkap, HTTPS, reverse proxy (`TRUSTED_PROXIES`), scheduler (cron tiap menit), `config:cache`/`route:cache`/`view:cache`, izin folder, pembuatan admin, checklist rilis, rollback.
- `docs/BACKUP.md` — membuat `BACKUP_ENCRYPTION_KEY` (`php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`), lokasi, retensi (14 harian + 8 mingguan), salinan offsite, prosedur restore langkah demi langkah, **latihan restore** berkala.
- `docs/AUDIT_POLISH.md` dan `docs/PROGRESS.md` — ringkasan audit dan log kemajuan (diisi sepanjang pekerjaan).
- Koreksi `README.md`: digest berjalan **tiap menit** (bukan tiap jam), daftar fitur diperbarui di akhir.

---

## 4. FASE 1 — PERBAIKAN BUG

Format tiap butir: **Gejala → Akar masalah → Rancangan perbaikan → Kriteria penerimaan → Tes**.

### 1.1 [KRITIS · kehilangan data] Hapus kategori global menghapus tugas semua user
- **Gejala:** admin menghapus kategori global → seluruh tugas (semua user) yang memakai kategori itu ikut terhapus.
- **Akar:** migration awal `tasks` membuat `category_id` dengan `->onDelete('cascade')`; migration `make_task_category_nullable` hanya mengubah nullability, bukan aksi FK. `AdminCategoryController::destroy` memanggil `$category->delete()` tanpa pengecekan.
- **Perbaikan:**
  1. Migration baru `fix_tasks_category_fk_on_delete_set_null` dengan `public $withinTransaction = false;`. Jalankan manual: `PRAGMA foreign_keys=OFF; BEGIN; CREATE TABLE tasks_new (...skema final...); INSERT INTO tasks_new SELECT ...; DROP TABLE tasks; ALTER TABLE tasks_new RENAME TO tasks; (buat ulang SEMUA index: tasks_user_status_index, tasks_user_due_date_index, tasks_user_starts_at_index, index user_id+completed_at); PRAGMA foreign_key_check; COMMIT; PRAGMA foreign_keys=ON;`. `category_id` menjadi `nullOnDelete()`, `task_status_id` tetap `nullOnDelete()`, `user_id` tetap `cascadeOnDelete()`.
  2. Untuk driver non-SQLite: `dropForeign` + `foreign(...)->nullOnDelete()`.
  3. `AdminCategoryController::destroy`: hitung pemakaian (tugas + kebiasaan + target); tampilkan jumlah di dialog `x-confirm-action`; setelah hapus tampilkan pesan bahwa N item menjadi "Tanpa kategori".
  4. Tambahkan catatan di `docs/DEPLOY.md`: periksa jumlah tugas sebelum/sesudah migration di produksi.
  5. Tinjau migration lama `make_categories_global` (drop tabel `categories` pada SQLite): jelaskan di `docs/AUDIT_POLISH.md` risikonya bagi DB yang sudah berisi data dan cara memeriksa apakah tugas pernah hilang.
- **Penerimaan:** hapus kategori yang dipakai tugas dua user berbeda → semua tugas tetap ada dengan `category_id = null`; `foreign_key_check` kosong; semua index tabel `tasks` tetap ada.
- **Tes:** Feature test hapus kategori; tes migration yang menyiapkan skema lama + data lalu menjalankan migration baru.

### 1.2 Cicilan lunas tetap muncul "Terlambat" dan di agenda
- **Akar:** `AgendaService::forRange()` dan `attentionFor()` tidak memeriksa `installments_total`, sedangkan `ObligationController`/`MoneyController` memeriksanya (logika terduplikasi).
- **Perbaikan:** ekstrak ke satu service `ObligationSchedule` (atau perluas `ObligationScheduler`) dengan metode seperti `duePeriods(Obligation $o, from, through): list<CarbonImmutable>` yang **sudah memperhitungkan** batas `installments_total`, `starts_on`, dan pembayaran yang ada. Pakai di `AgendaService`, `ObligationController::index/pay`, `MoneyController::index`. Hapus perhitungan terduplikasi.
  - Cicilan dianggap selesai bila `payments.count() >= installments_total`; periode sesudahnya tidak dihasilkan.
  - Verifikasi pemakaian `remind_days_before`: kolom divalidasi dan disimpan tetapi `attentionFor()` memakai 3 hari tetap. Hormati nilai per tagihan (default 3), batas 0–30.
  - `attentionFor()`: hapus kondisi mati `if ($yesterday < $today)`; batasi riwayat keterlambatan secara eksplisit (konstanta bernama) dan jangan menampilkan puluhan baris berulang untuk satu tagihan — tampilkan **satu baris per tagihan** dengan jumlah periode tertunggak.
- **Penerimaan:** cicilan 3× lunas semua → tidak ada item di kalender/dashboard/digest pada bulan-bulan berikutnya; tagihan `bill` yang menunggak 3 bulan → satu baris "3 periode tertunggak".
- **Tes:** `travelTo` ke +8 bulan; tes digest; tes kalender.

### 1.3 Tugas dibuka lagi, `completed_at` tetap terisi
- **Akar:** `TaskController::update()` hanya mengisi `completed_at` saat transisi ke selesai; tidak pernah mengosongkannya. `ActivityStreak` menghitung `completed_at IS NOT NULL`.
- **Perbaikan:** ekstrak `TaskCompletion` (service kecil) dengan aturan tunggal: pindah ke status selesai → `now('UTC')`; pindah ke non-selesai → `null`; tetap selesai → pertahankan. Pakai di `store`, `update`, dan `complete`. Migration data opsional: `UPDATE tasks SET completed_at = NULL WHERE completed_at IS NOT NULL AND task_status_id NOT IN (SELECT id FROM task_statuses WHERE is_completed = 1)` (idempoten, catat jumlah baris terdampak).
- **Penerimaan:** selesai → ubah ke "Sedang dikerjakan" → `completed_at` null dan streak tidak menghitungnya.
- **Tes:** store dengan status selesai, update bolak-balik, endpoint `complete`, streak.

### 1.4 Bulan salah pada tanggal 29–31 (`createFromFormat('Y-m')`)
- **Akar:** `Carbon::createFromFormat('Y-m', $m)` mengisi hari dari tanggal hari ini → overflow (31 Des + "2026-11" → 1 Des). Terdapat di `MoneyController::index` (beberapa kali) dan `ReportController::index`.
- **Perbaikan:** buat value object `App\Support\MonthRange` (`fromString('Y-m')` memakai `'!Y-m'`, properti `start`, `end`, `previous()`, `next()`, `key`). Ganti semua pemakaian. `grep -rn "createFromFormat('Y-m'" app/` harus kosong.
- **Refactor terkait:** ekstrak query "kategori pengeluaran teratas" yang **terduplikasi** di `MoneyController` dan `ReportController` ke `MonthlyCategoryReport`; kelompokkan per `money_category_id` (bukan `name`); label "Tanpa kategori" dari `lang`. Hapus cabang mati `routeIs('user.reports.index')` bila tak terpakai.
- **Penerimaan:** pada tanggal 31 Des, `/dashboard/money?month=2026-11` dan `/dashboard/reports?month=2026-02` menampilkan data bulan yang diminta; `previousMonth` benar.
- **Tes:** `travelTo` ke 2026-12-31, 2027-03-31, 2026-05-31 dengan berbagai `month`; tes unit `MonthRange`.

### 1.5 Preset kategori uang muncul lagi setelah di-rename, dan GET menulis DB
- **Akar:** `MoneyController::index` menjalankan `firstOrCreate` untuk semua preset di setiap GET.
- **Perbaikan:** seed preset **satu kali** per user: tambahkan kolom `users.money_presets_seeded_at` (nullable), seed saat registrasi dan lewat backfill migration (idempoten) untuk user lama; **jangan** seed ulang untuk user yang sudah di-backfill. GET index tidak boleh menulis DB.
- **Penerimaan:** rename "Makan & minum" → kunjungi index berkali-kali → nama lama tidak kembali; jumlah query GET index konstan.
- **Tes:** rename + reload; tes jumlah query; tes registrasi menyiapkan preset.

### 1.6 Kebiasaan tak terlihat (mulai di masa depan / sudah berakhir)
- **Akar:** `HabitController::index` memfilter `starts_on <= hari ini` dan `ends_on >= hari ini`.
- **Perbaikan:** daftar dibagi `x-segmented`/tab: **Aktif** (hari ini), **Akan datang**, **Berakhir**, **Arsip** (lihat 2.1). Check-in hanya untuk "Aktif". Semua tab bisa edit/arsip. Dashboard tetap hanya menampilkan yang aktif & terjadwal hari ini.
- **Tes:** ketiga kondisi muncul di tab yang benar; check-in ditolak untuk yang belum mulai.

### 1.7 Target: edit gagal bila tenggat lewat; target menurun mustahil
- **Perbaikan 1:** di `SaveGoalRequest`, aturan `due_on after_or_equal:today` hanya berlaku saat membuat atau bila `due_on` berubah dibanding nilai tersimpan. `habit_id` wajib `whereNull('archived_at')`.
- **Perbaikan 2:** lihat fitur 2.2 (arah target).
- **Tes:** edit judul target dengan tenggat kemarin berhasil; mengubah tenggat ke masa lalu tetap ditolak.

### 1.8 Pengingat piutang memakai nominal awal
- `LoanController::index`: gunakan `remaining_amount` pada pesan pengingat. **Tes:** cicilan sebagian → pesan menampilkan sisa.

### 1.9 Daily digest: satu kegagalan memblokir semua penerima
- **Perbaikan:** try/catch per user di `SendDailyDigest`; `Log::warning('Digest gagal', ['user_id'])` (tanpa email/isi); jangan set `digest_last_sent_on` bila gagal; batasi percobaan ulang (mis. maks 3 kali per hari via kolom/cache) agar tidak mencoba tiap menit selamanya. Jangan kirim email jika tugas, kebiasaan, dan perhatian semuanya kosong.
- **Tes:** `Mail` gagal untuk user A, user B tetap terkirim; user A tidak dicoba tanpa batas.

### 1.10 Reverse proxy, redirect admin, konsistensi otorisasi
- `TrustProxies`: baca `TRUSTED_PROXIES` dari env (daftar IP/CIDR dipisah koma atau `*`; default kosong), dokumentasikan. Tanpa ini IP semua user sama → throttle `login-ip` (20/mnt) dan `register` (5/mnt) menjadi global, HSTS tidak terkirim.
- `RedirectIfAuthenticated`: arahkan admin ke `admin.dashboard`, user ke `user.dashboard`. Hapus/perbaiki `RouteServiceProvider::redirectTo()` (mengarah ke `/user/dashboard` yang tidak ada).
- **Aturan admin yang konsisten:** admin hanya mengelola akun/kategori global; tidak punya data pribadi. Terapkan sama di `TaskPolicy` dan `OwnedModelPolicy`. Tambah tes matriks.
- Tambah `throttle:60,1` pada rute tasks (termasuk `complete`) seperti modul lain.

### 1.11 Konkurensi di SQLite
- Audit semua `lockForUpdate()` (Obligation `pay`, Loan `update`/`repay`, Goal `updateProgress`). Karena no-op di SQLite: pakai `DB::transaction` dengan **`BEGIN IMMEDIATE`** untuk operasi yang memeriksa-lalu-menulis (buat helper `Db::immediate(callable)` yang mengeksekusi `BEGIN IMMEDIATE` untuk SQLite dan `DB::transaction` untuk driver lain), atau pastikan ada unique index. Pembayaran obligasi sudah punya unique `(obligation_id, due_on)`; tambahkan pengaman serupa untuk `goal_updates` dan pelunasan loan (cek sisa di dalam transaksi immediate).
- **Tes:** dua permintaan "bersamaan" (simulasi berurutan dengan state yang sama) tidak boleh melebihi sisa utang/target.

### 1.12 Pembersihan kecil (satu commit per kelompok)
- Escape wildcard LIKE (`%`, `_`, `\`) lewat helper `Support\Like::escape()` di `TaskController`, `SearchController`, `AdminController`.
- Pindahkan semua string hardcode ke `lang/id/ui.php`: pesan `abort(422, ...)` di `ObligationController`/`LoanController`, `'Terlambat X hari'`, `'Jatuh tempo ...'`, `'Belum berstatus'`, `'Tanpa kategori'`, label jenis agenda ('Kebiasaan', dst.).
- Hapus kode mati: validasi `year`/`month` di `UserController::index`, `category_name`/`category_color` di `SaveHabitRequest` (jika tak diproses), `WalletBalance::forWallet` bila tak lagi dipakai selain GoalProgress (pertimbangkan satu implementasi).
- N+1: `GoalProgress::remainingPerDay` memanggil `$goal->user()->firstOrFail()` per target — pakai relasi ter-eager-load.
- `ActivityStreak`: jangan memuat seluruh riwayat. Ambil tanggal unik lewat query (`selectRaw('DISTINCT date(...)')`/`pluck`) dengan jendela 400 hari; hitung `bestStreak` secara terbatas dan dokumentasikan batasannya.
- `AdminController::index` dan `users.index` memakai method yang sama dengan view bernama `dashboard` — pisahkan agar jelas, atau tambahkan komentar alasan.

**Gerbang Fase 1:** seluruh gerbang kualitas lolos + tes regresi untuk 1.1–1.11 + `docs/PROGRESS.md` diperbarui.

---

## 5. FASE 2 — FITUR BARU

Untuk setiap fitur wajib: migration aman → model/Policy (`OwnedModelPolicy`) → Form Request → controller tipis + service → view memakai komponen yang ada → teks `lang/id` → tes Feature **termasuk uji akses antar-user (403/404)** → pembaruan README/docs. Semua aksi destruktif memakai `x-confirm-action`. Semua aksi tulis di-throttle (`throttle:60,1`).

### 2.1 Siklus hidup entitas: arsip, pulihkan, hapus
**Tujuan:** menutup celah terbesar — tagihan/utang tak bisa dihentikan, transaksi tak bisa dihapus, arsip tak terlihat.

| Entitas | Hapus | Arsip | Pulihkan | Catatan |
|---|---|---|---|---|
| Transaksi manual (`source_type` null) | Ya (konfirmasi) | – | – | Transaksi bersumber loan/obligation: tombol hapus diganti arahan ke "Batalkan pembayaran" |
| Tagihan/cicilan | Hanya bila belum ada pembayaran | Ya | Ya | Arsip → hilang dari agenda/attention/digest |
| Utang/piutang | Hanya bila belum ada pembayaran (hapus juga transaksi sumber) | Ya | Ya | Arsip hanya bila lunas atau atas pilihan eksplisit |
| Dompet | Hanya bila tanpa transaksi (FK `restrict`) | Ya | Ya | Arsip dengan saldo ≠ 0: tampilkan peringatan; tentukan & dokumentasikan apakah saldo ikut "total saldo" |
| Kebiasaan | Ya (log ikut terhapus, konfirmasi kuat) | Ya | Ya | Route `destroy` saat ini tidak ada |
| Target | Ya | Ya | Ya | Tampilkan tab **Tercapai** dan **Arsip** |
| Kategori uang | Hanya bila tak dipakai | Ya | Ya | Rename tetap boleh |

- UI: filter `?status=active|archived` (+ `upcoming|ended` untuk kebiasaan, `achieved` untuk target) di setiap index, memakai `x-segmented`; keadaan kosong memakai `x-empty-state`.
- Aturan agenda: entitas terarsip tidak muncul di `AgendaService`, `attentionFor`, digest, pencarian default (pencarian menampilkan label "Diarsipkan" bila dicari).
- **Tes:** arsip → hilang dari aktif & agenda; pulihkan → kembali; hapus entitas dengan pembayaran ditolak; user lain 403/404.

### 2.2 Target dua arah (naik/turun) dan koreksi progres
- Migration: `goals.direction` (`increase` default | `decrease`). `goal_updates.amount` mendukung nilai bertanda (SQLite tidak menegakkan unsigned; ubah validasi dan, bila perlu, tipe kolom lewat rebuild tabel yang aman).
- `GoalProgress::percent`: `increase` → `(current-start)/(target-start)`; `decrease` → `(start-current)/(start-target)`; di-clamp 0–100.
- `SaveGoalRequest`: `increase` → `start_value ≤ target_value`; `decrease` → `start_value ≥ target_value`. `markAchieved` menghormati arah.
- `updateProgress`: izinkan delta bertanda tak-nol; `current` tidak boleh melewati target searah progres atau < 0; tambahkan hapus update terakhir (undo, dengan konfirmasi dan Policy `GoalUpdate`).
- **Tes:** target berat 80→70, progres −3 dua kali; persen benar; melewati target ditolak; undo.

### 2.3 Anggaran bulanan per kategori
- Tabel `budgets`: `id`, `user_id` (cascade), `money_category_id` (cascade, hanya tipe `expense`), `month` (string `Y-m`), `amount` (unsignedBigInteger > 0), timestamps; **unique** `(user_id, money_category_id, month)`; index `(user_id, month)`.
- Service `BudgetProgress::forMonth(User, MonthRange)`: **satu** query agregat pengeluaran per kategori (`expense`, bukan `loan`/`loan_repayment`/`adjustment`), digabung dengan anggaran; hasil: `budget`, `spent`, `remaining`, `percent`, `status` (`ok` < 80%, `warning` ≥ 80%, `over` ≥ 100%; ambang di `config/budget.php`).
- UI: bagian "Anggaran" di halaman Uang & Laporan (CRUD inline via bottom-sheet/modal yang sudah ada, `x-progress-bar`, warna status + teks — jangan hanya warna). Aksi "Salin dari bulan lalu" (idempoten, tidak menimpa yang sudah ada).
- Dashboard: item "Perlu perhatian" saat kategori ≥ 80%/100%; hormati `hide_financial_amounts`.
- **Tes:** perhitungan, batas awal/akhir bulan, kategori tanpa anggaran, isolasi antar-user, transaksi transfer/loan tidak dihitung.

### 2.4 Ekspor data (CSV) dan portabilitas
- Halaman Pengaturan → "Ekspor data": transaksi (rentang tanggal, maks 5 tahun), tugas, kebiasaan + log.
- `response()->streamDownload` + `lazy()`/`cursor()` (hemat memori), UTF-8 **dengan BOM**, header kolom Indonesia, tanggal `Y-m-d`, nominal integer mentah.
- **Cegah CSV injection:** sel teks yang diawali `=`, `+`, `-`, `@`, tab, atau CR diawali `'`.
- Throttle `5,1`; hanya data milik user; catat `Log::info('export', ['user_id','type'])` tanpa isi.
- **Tes:** isi & urutan kolom, injection, otorisasi, rentang tidak valid.

### 2.5 Tugas berulang
- Kolom `tasks`: `recurrence` (`none|daily|weekly|monthly`), `recurrence_days` (json, 1–7 untuk weekly), `recurrence_until` (date nullable), `recurrence_parent_id` (nullable FK `nullOnDelete`), `occurrence_date` (date nullable); **unique** `(recurrence_parent_id, occurrence_date)` untuk idempotensi.
- Aturan: saat tugas berulang diselesaikan (lewat `complete` **maupun** `update` ke status selesai) dan belum melewati `recurrence_until`, buat **satu** instance berikutnya dalam transaksi. Hitung tanggal berikutnya di **zona waktu user**, pertahankan jam & durasi (`ends_at - starts_at`), lalu simpan ke UTC. Bulanan memakai semantik tanpa overflow (31 → akhir bulan). Bila hasil < hari ini, majukan ke kemunculan pertama ≥ hari ini (hindari antrian tugas basi). Menyelesaikan tugas yang sama dua kali tidak boleh menggandakan.
- UI: kolom ulang di form tugas (`x-segmented` + `x-day-pills` yang sudah ada), ikon ulang di daftar; konfirmasi saat menghapus ("hapus hanya ini / hentikan pengulangan").
- **Tes:** idempotensi, zona waktu berbeda (`Asia/Jakarta`, `Asia/Jayapura`, `America/New_York`), akhir bulan, `until`, pengulangan mingguan multi-hari.

### 2.6 Penyempurnaan modul Tugas
- Urutan default: belum selesai berdasarkan tenggat terdekat, lalu selesai; opsi sortir (`?sort=due|created|title`) dan filter kategori. Tambahkan index `(user_id, created_at)` bila `EXPLAIN QUERY PLAN` menunjukkan scan.
- Aksi massal (checkbox): tandai selesai / ubah status / hapus; otorisasi per baris; transaksi tunggal; konfirmasi; aksesibel (label, `aria-live` jumlah terpilih).
- Pencarian global menaut **langsung ke item** (edit/anchor), bukan hanya ke daftar.

### 2.7 Penyesuaian saldo dompet
- Aksi "Sesuaikan saldo": input saldo aktual → sistem membuat transaksi `income`/`expense` selisih dengan `source_type = 'adjustment'`, catatan otomatis dari `lang`.
- `MonthlySummary` dan laporan kategori **mengecualikan** `adjustment` (seperti `loan`); `WalletBalance` tetap menghitungnya. Tampil di daftar dengan label "Penyesuaian saldo"; tidak bisa diedit, boleh dihapus.
- **Tes:** saldo akhir tepat, ringkasan bulanan tidak berubah, selisih nol tidak membuat transaksi.

### 2.8 Backlog opsional (kerjakan hanya bila Fase 1–4 selesai dan gerbangnya hijau)
- Impor CSV transaksi (pratinjau, pemetaan kolom, deteksi duplikat, batas ukuran/baris, validasi per baris, laporan hasil).
- Ringkasan mingguan (email + halaman): tugas selesai, streak, pengeluaran vs minggu lalu.
- Tag/label tugas ringan; templat tugas; widget "fokus hari ini".

**Gerbang Fase 2:** gerbang kualitas lolos; tidak ada N+1; anggaran tes jumlah query (bagian 8) lolos; UI diuji di 360px dan 1280px, terang & gelap.

---

## 6. FASE 3 — INFRASTRUKTUR, KEAMANAN, OPERASIONAL

**3.1 Upgrade framework (branch terpisah).**
- Laravel 10 sudah **end-of-life** (tidak ada patch keamanan); PHP 8.1 juga EOL. Rute: 10 → 11 → 12 (→ 13 bila PHP ≥ 8.3 tersedia). Satu lompatan per commit; seluruh gerbang kualitas hijau di tiap lompatan. Baca panduan upgrade resmi tiap versi; jangan menebak.
- Perhatian khusus: migration lama yang memakai `->change()` (hapus `doctrine/dbal` sesuai panduan, dan **uji ulang migrasi dari DB kosong** — ada risiko atribut kolom hilang bila migration `change()` tidak menyebutkan atribut lengkap); struktur `bootstrap/app.php` vs `Kernel` (boleh dipertahankan bila didukung, jangan migrasi struktur tanpa alasan); `Handler`, middleware alias, `RouteServiceProvider`.
- Perbarui `composer.json` (`php`), matriks CI (8.2–8.4; hapus 8.1), README, `docs/DEPLOY.md`. Pastikan `composer audit` bersih.

**3.2 Header & CSP.** Default `.env.example` produksi `CSP_MODE=report`; dokumentasikan jalur ke `enforce` (periksa log channel `csp`). Pastikan halaman error (`layouts/error`) tetap aman bila middleware global gagal sebelum `$cspNonce` dibagikan (jangan sampai halaman error sendiri melempar variabel tak terdefinisi).

**3.3 Backup & pemulihan.**
- `app:backup-database` gagal cepat dengan pesan jelas bila `BACKUP_ENCRYPTION_KEY`/`BACKUP_DIRECTORY` kosong atau kunci bukan 32 byte base64.
- Perintah `app:backup-status` (umur backup terbaru, jumlah, ukuran) + peringatan di endpoint `/up` atau log `critical` bila backup terakhir > 36 jam.
- Restore: validasi/hapus sidecar `database.sqlite-wal` dan `-shm` secara eksplisit; tes pemulihan penuh (create → ubah data → restore → data kembali) di `EncryptedSqliteBackupTest`.
- Dokumentasikan salinan **offsite** (mis. `rclone`/`rsync` ke penyimpanan lain) dan latihan restore tiap kuartal.

**3.4 Akun dan sesi.** Verifikasi dan tulis tes untuk: sesi lain berakhir setelah ganti password (`Auth::logoutOtherDevices`/`AuthenticateSession`), pembatasan `password.email`/`password.store`, `verified` pada rute admin bila verifikasi email aktif, audit log untuk aksi akun sensitif milik user (hapus akun, ganti email/password) — tanpa menyimpan data sensitif.

**3.5 Pengerasan lain.** `robots.txt` melarang `/dashboard`, `/admin`, `/profile`, `/settings`; `.htaccess` memberi `Cache-Control: public, max-age=31536000, immutable` untuk `/build/assets/*`; peringatan boot di produksi bila `APP_URL` masih `localhost` (`TrustHosts` bergantung padanya); `APP_DEBUG` produksi sudah dijaga (pertahankan).

**3.6 CI.** Tambah `larastan` (mulai level 5, baseline jika perlu), `php artisan migrate:fresh --seed` di SQLite, cache composer/npm, dan job terpisah yang menjalankan migration di atas snapshot DB berisi data (fixture).

---

## 7. FASE 4 — FRONTEND, UX, AKSESIBILITAS, PWA

**4.1 Dependensi & bundle.** Hapus `axios` (`resources/js/bootstrap.js`, `package.json`) — semua request memakai `fetch`; verifikasi ukuran bundle turun. Tetapkan **anggaran**: JS ≤ 50 KB gzip, CSS ≤ 20 KB gzip pada halaman terberat; CI gagal bila melewatinya (skrip kecil membaca `public/build`).

**4.2 Service worker.** Turunkan versi cache dari hash `manifest.json` (bukan `v1` statis); pada `activate` hapus entri aset yang tak ada lagi di manifest. Hapus atau implementasikan `CLEAR_DYNAMIC_CACHES` (saat ini mengacu ke cache yang tak pernah dibuat). Pertahankan: tidak pernah menyimpan halaman terautentikasi.

**4.3 Aksi optimistik** (`optimistic-actions.js`): pesan khusus untuk 401/419 ("Sesi berakhir, masuk lagi" + tautan login) dan 422 (tampilkan pesan validasi); perbarui angka progres kebiasaan di dashboard setelah check-in; semua teks via data-atribut/i18n; pulihkan fokus dan `aria-live` setelah aksi.

**4.4 Mode privasi nominal.** Sembunyikan di sisi server (atau CSS `[data-private]` tersembunyi sebelum JS) agar angka tidak berkedip terlihat; simpan pilihan toggle di `sessionStorage` (dibungkus try/catch).

**4.5 Aksesibilitas (WCAG 2.2 AA).** Audit & perbaiki: kontras (terang & gelap), fokus terlihat dan urutan fokus (termasuk modal/bottom-sheet dengan focus trap yang sudah ada), label & `aria-describedby` pada semua form, status tidak hanya warna (anggaran, streak, status tugas), target sentuh ≥ 44px, `prefers-reduced-motion` dihormati (`motion.js`), `skip-link` berfungsi, struktur heading tidak melompat. Perluas `AccessibilityStructureTest`.

**4.6 UX.** Keadaan kosong, memuat, dan galat untuk setiap daftar; pesan galat yang menjelaskan tindakan berikutnya; halaman error (403/404/419/429/500/503) konsisten; `dirty-guard` form dipertahankan; keyboard shortcut tidak diperlukan.

**4.7 CSS.** Inventaris pemakaian kelas di `components/legacy.css` (67 KB) dan `layouts/legacy.css` (18 KB); hapus kelas tak terpakai; migrasikan bertahap ke token + utilitas Tailwind **tanpa mengubah tampilan** (verifikasi visual per halaman). Catat sisa pekerjaan di `docs/PROGRESS.md`.

---

## 8. STRATEGI PENGUJIAN (STANDAR UNTUK SEMUA FASE)

- **Piramida:** unit (service/value object: `MonthRange`, `ObligationSchedule`, `GoalProgress`, `HabitStreakCalculator`, `BudgetProgress`) → feature (HTTP + DB) → migration test → (opsional) smoke browser.
- **Waktu:** gunakan `travelTo` dengan zona waktu eksplisit. Wajib mencakup: 29/30/31 akhir bulan, pergantian tahun, Februari kabisat, dan tiga zona waktu (`Asia/Jakarta`, `Asia/Jayapura`, `America/New_York`).
- **Matriks otorisasi:** satu tes data-provider yang menelusuri **semua** rute `user.*` dan `admin.*` dengan (a) tamu, (b) user pemilik, (c) user lain, (d) admin; harapannya redirect/200/403/404 sesuai. Rute baru otomatis terdaftar di matriks.
- **Anggaran query:** untuk dashboard, money index, kalender, tugas index: `DB::enableQueryLog()` + `assertLessThanOrEqual($batas)` dan uji bahwa jumlah query tidak bertambah bila data digandakan.
- **Properti/tabel:** `HabitStreakCalculator` dan `ActivityStreak` diuji dengan tabel skenario (libur, hari tak terjadwal, minggu mulai Senin/Minggu).
- **Keamanan:** tes CSV injection, IDOR (ID milik user lain di setiap parameter), mass-assignment (`role`, `is_active`), rate limit, XSS pada judul/catatan (output ter-escape).
- **Migration:** setiap migration data punya tes yang menyiapkan skema/isi lama lalu memverifikasi hasil dan `foreign_key_check`.
- Faktori & helper: lengkapi factory (`Task`, `Category`, `Budget`, dst.), buat trait `Tests\Concerns\CreatesLifeHubData` agar tes ringkas dan konsisten.

---

## 9. REVIEW MANDIRI SEBELUM MENUTUP TIAP FASE (CHECKLIST)

- [ ] Semua query data pribadi terbatas ke user; ID di request divalidasi kepemilikan.
- [ ] Tidak ada float untuk uang; tidak ada `createFromFormat` tanpa `!`; tidak ada campur UTC/lokal.
- [ ] Tidak ada N+1; GET tidak menulis DB.
- [ ] Tidak ada string UI baru di luar `lang/`; tidak ada inline script/style tanpa nonce.
- [ ] Migration idempoten, teruji di DB kosong dan berisi; `foreign_key_check` bersih.
- [ ] Aksi destruktif punya konfirmasi, Policy, throttle, dan tes.
- [ ] Aksesibel (keyboard, label, kontras) dan teruji di mobile 360px + mode gelap.
- [ ] Dokumentasi dan `docs/PROGRESS.md` diperbarui; asumsi dicatat.

---

## 10. DEFINISI SELESAI DAN FORMAT LAPORAN AKHIR

**Selesai bila:**
- Seluruh gerbang kualitas hijau (`pint`, `php artisan test`, `npm run build`, `npm audit`, `composer audit`) dan CI hijau pada checkout bersih.
- Setiap butir Fase 1 punya tes regresi; setiap fitur Fase 2 punya tes termasuk otorisasi antar-user.
- Tidak ada data yang hilang: seluruh migration terbukti pada salinan DB berisi data dan DB kosong.
- Dokumen README dan `docs/*` lengkap dan akurat.

**Laporan akhir (tulis di `docs/PROGRESS.md` dan ringkas di respons):**
1. Daftar commit per fase (satu baris per butir).
2. Tes baru (jumlah, area) dan hasil gerbang kualitas.
3. Keputusan & asumsi (terutama kebijakan arsip dompet, ambang anggaran, perilaku tugas berulang).
4. Risiko tersisa & utang teknis (CSS legacy, dsb.).
5. **Langkah deploy manual** berurutan: backup → `down` → deploy → `migrate --force` → verifikasi jumlah baris → `up` → env baru (`TRUSTED_PROXIES`, `BACKUP_*`, `CSP_MODE`, `APP_URL`) → cek `/up` dan `app:backup-status`.
6. Rencana rollback (restore backup + versi sebelumnya).

---

## 11. KONDISI BERHENTI (HANYA INI YANG BOLEH MEMBUATMU BERHENTI)

- Tes tetap merah setelah 3 pendekatan berbeda pada akar masalah yang sama → tulis temuan di `docs/PROGRESS.md`, tandai butir "terblokir", lanjutkan butir lain.
- Perubahan yang akan menghapus/mengubah data pengguna tanpa jalur pemulihan → hentikan butir itu, jelaskan risiko, jangan jalankan.
- Konflik antara spesifikasi ini dan invarian di bagian 1 → utamakan invarian, catat penyimpangan.

Mulai dari **Fase 0** sekarang. Setelah Fase 0 selesai, tulis rencana eksekusi singkat (urutan commit) di `docs/PROGRESS.md`, lalu lanjut ke Fase 1 tanpa menunggu persetujuan.
