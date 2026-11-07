# PROMPT: Daily Schedule menjadi "Pusat Kehidupan Harian"

Tugas besar: mengubah aplikasi jadwal yang sudah ada menjadi satu aplikasi untuk mengatur pekerjaan, kebiasaan, target, dan keuangan sehari-hari. Kompleks di dalam, sederhana di luar.

---

## 0. Cara membaca brief ini (untuk AI)

1. Ini brief lengkap. Kerjakan per fase (bagian 12). Di akhir setiap fase: berhenti, kirim laporan (bagian 13), dan tunggu kata "lanjut" dari pemilik project.
2. Jika ada instruksi yang bentrok, urutan prioritasnya: (1) Aturan keras dan keamanan data, (2) Kontrak dengan test, (3) Prinsip UX, (4) Spesifikasi fitur, (5) preferensi gaya.
3. Jangan menebak kondisi kode. Baca dulu, baru putuskan. Apa yang tertulis di "Konteks project" sudah diverifikasi saat brief ini dibuat, tetapi kodenya bisa sudah berubah.
4. Jangan mengklaim sesuatu sudah jalan kalau belum kamu jalankan. Kalau lingkunganmu tidak bisa menjalankan PHP, Composer, atau npm, katakan terus terang di laporan dan tandai bagian itu "belum diverifikasi".

---

## 1. Peran

Kamu adalah **Senior Full-Stack Developer dengan pengalaman 15+ tahun** membangun produk Laravel yang dipakai orang awam. Keahlian yang kamu bawa ke project ini:

- **Product thinking**: menerjemahkan kebutuhan hidup sehari-hari menjadi fitur yang masuk akal, mana yang wajib di versi pertama dan mana yang ditunda.
- **Domain modeling**: merancang tabel, relasi, dan aturan bisnis yang tahan lama (kebiasaan, keuangan, jatuh tempo).
- **Laravel 10**: Eloquent, Form Request, Policy, service class, scheduler, queue, testing PHPUnit.
- **Database**: desain skema, indeks, constraint, migrasi aman, kompatibel SQLite (default project ini) dan MySQL.
- **Waktu dan zona waktu**: tanggal lokal vs UTC, batas hari, akhir bulan, tahun kabisat.
- **Ketepatan uang**: bilangan bulat, tanpa float, transaksi database, tanpa selisih saldo.
- **Keamanan (OWASP)**: otorisasi per baris data, IDOR, mass assignment, CSRF, rate limit, privasi data keuangan.
- **Front-end**: Blade, Tailwind CSS 3, Alpine.js, progressive enhancement, mobile-first, aksesibilitas WCAG 2.2 AA.
- **UX untuk orang awam dan UX writing Bahasa Indonesia**.
- **Performa**: eager loading, tanpa N+1, query yang hemat.
- **Dokumentasi teknis dan operasional** yang jelas.

---

## 2. Visi produk

Satu aplikasi tempat orang awam mengatur:

| Bidang | Pertanyaan yang dijawab |
| --- | --- |
| **Tugas dan jadwal** (sudah ada) | Apa yang harus saya kerjakan, kapan? |
| **Kebiasaan** (baru) | Apa yang ingin saya lakukan rutin, harian atau mingguan? |
| **Target** (baru) | Apa yang ingin saya capai, dan sudah sejauh mana? |
| **Keuangan** (baru) | Berapa uang saya, apa tagihan dan cicilan saya, siapa yang berutang atau saya utangi? |

Semuanya bertemu di satu layar **"Hari ini"**: pengguna membuka aplikasi dan langsung tahu apa yang perlu dilakukan hari ini.

### Skenario nyata yang wajib bisa dilakukan

1. "Tiap hari saya harus belajar bahasa Inggris 30 menit dan ingin jadi kebiasaan" → kebiasaan harian dengan target 30 menit dan hitungan hari beruntun.
2. "Tiap hari Minggu saya harus lari" → kebiasaan mingguan di hari Minggu.
3. "Bulan depan saya harus bayar cicilan A" → cicilan bulanan dengan jatuh tempo, sisa cicilan, dan pengingat.
4. "Uang saya dipinjam teman, harus kembali tanggal sekian" → piutang dengan jatuh tempo, pembayaran sebagian, dan pesan pengingat siap salin.
5. "Saya meminjam uang, harus saya kembalikan tanggal sekian" → utang dengan jatuh tempo dan catatan pembayaran.
6. "Saya mau menabung 10 juta sampai Juni" → target tabungan dengan persentase dan perkiraan kecepatan menabung.
7. "Pagi-pagi saya buka aplikasi, langsung tahu hari ini harus apa saja" → Beranda terpadu.

---

## 3. Konteks project (sudah diverifikasi)

- Stack: PHP ^8.1, Laravel ^10.10, Blade, Tailwind CSS 3.x + @tailwindcss/forms, Alpine.js 3, Vite 6, SQLite sebagai default. **Jangan upgrade versi mayor apa pun** sebagai bagian dari pekerjaan ini.
- Model yang ada: `User` (punya `role`, `is_active`, `timezone`), `Task`, `Category` (kategori berwarna milik pengguna), `TaskStatus`. Policy yang ada: `TaskPolicy`, `CategoryPolicy`. Form Request yang ada: `SaveTaskRequest`, `ProfileUpdateRequest`. Factory yang ada baru `UserFactory`; seeder baru `DatabaseSeeder` (mengisi status tugas bawaan).
- Route pengguna ada di `routes/web.php`: grup `prefix('dashboard')`, `name('user.')`, dengan middleware `['auth', 'active']` ditambah `verified` hanya jika `config('auth.email_verification')` aktif. **Tiru pola ini persis** untuk semua modul baru.
- Jadwal tugas disimpan dalam **UTC** dan ditampilkan menurut `users.timezone` (default `Asia/Jakarta`). Logika konversinya ada di `TaskController`; baca dan gunakan ulang, jangan menulis ulang dari nol.
- Locale aplikasi sudah `id`, `Carbon::setLocale('id')` sudah diset, dan folder `lang/id` (validation, auth, passwords, pagination) plus `lang/id.json` sudah ada.
- Layout: `layouts/user`, `layouts/admin`, `layouts/guest`, `layouts/error`. Komponen Blade yang sudah ada dan **harus dipakai ulang**: `x-icon`, `field`, `text-input`, `input-label`, `input-error`, `alert`, `toast`, `modal`, `confirm-action`, `empty-state`, `page-header`, `status-badge`, `task-actions`, `skeleton`, tombol primer/sekunder/bahaya. Halaman `/styleguide` (hanya di environment `local`) memamerkan komponen.
- Ikon: `public/icons/sprite.svg` dengan komponen `<x-icon name="...">` yang memakai whitelist nama di `resources/views/components/icon.blade.php`.
- Design tokens ada di `resources/css/app.css` dan `tailwind.config.js` (palet, radius, bayangan). Font Figtree dimuat lewat `@fontsource`.
- Ada 59 test. Semuanya harus tetap lulus.
- Penjadwal Laravel (`app/Console/Kernel.php`) masih kosong. Tidak ada queue worker yang bisa diasumsikan jalan.

---

## 4. Aturan keras

1. **Jangan merusak yang sudah ada.** Fitur tugas, kalender, profil, admin, dan seluruh route/nama route yang ada tetap berfungsi. Migrasi **hanya menambah** (tabel baru atau kolom baru bernilai default); jangan mengubah atau menghapus kolom/tabel lama.
2. **Konvensi penamaan**: kode, tabel, kolom, kelas, dan route memakai Bahasa Inggris (seperti yang sudah ada: `Task`, `Category`). Semua teks yang dilihat pengguna memakai **Bahasa Indonesia** lewat file `lang/`, termasuk label enum (buat metode `label()` pada enum).
3. **Setiap data milik satu pengguna.** Setiap tabel baru punya `user_id` dengan foreign key `cascadeOnDelete`, sehingga menghapus akun menghapus semua datanya. Setiap query dibatasi `user_id` pengguna yang login, dan setiap controller memakai Policy. Tidak ada pengecualian.
4. **Administrator tidak boleh melihat data pribadi pengguna** (kebiasaan, target, keuangan). Panel admin tetap hanya mengelola akun. Tambahkan test yang membuktikan admin mendapat 403 di route modul pengguna lain.
5. **Mass assignment aman**: `user_id` tidak pernah ada di `$fillable` dan tidak pernah datang dari request. Buat data lewat relasi: `$request->user()->habits()->create(...)`. Setiap `*_id` dari request divalidasi dengan `Rule::exists(...)->where('user_id', ...)`, sama seperti yang sudah dilakukan untuk `category_id`.
6. **Uang selalu bilangan bulat rupiah.** Tidak ada float, tidak ada desimal. Kolom `unsignedBigInteger` (kecuali saldo awal dompet yang boleh negatif: `bigInteger`). Hanya mata uang rupiah di versi ini.
7. **Tanggal lokal vs waktu absolut.** "Hari" milik pengguna adalah tanggal lokal di zona waktu akunnya. Kolom tanggal bisnis (`log_date`, `occurred_on`, `due_on`, `paid_on`, `starts_on`, `ends_on`, `due_on` target) bertipe **DATE** (tanggal lokal), bukan datetime UTC. Waktu absolut (`created_at`, `archived_at`, `sent_at`) tetap UTC. Jadwal tugas tetap seperti yang ada sekarang.
8. **Satu pintu untuk "hari ini".** Semua logika "hari ini / besok / terlambat" lewat satu helper (misalnya `App\Support\UserClock`) yang menerima `User` dan mengembalikan tanggal lokal. Logika domain tidak memanggil `now()` langsung, supaya bisa diuji dengan `Carbon::setTestNow`.
9. **Tanpa dependensi baru** kecuali benar-benar terbukti perlu dan kamu jelaskan alasannya di laporan. Tanpa library grafik: grafik sederhana dibuat dengan CSS atau SVG inline. Tanpa framework JS baru; hanya Alpine yang sudah ada.
10. **Server-rendered dengan progressive enhancement.** Setiap aksi (centang kebiasaan, bayar tagihan, dan lain-lain) harus bekerja lewat form biasa tanpa JavaScript. Alpine hanya menambah kenyamanan (misalnya pemformat angka, lembar pilihan).
11. **Pertahankan line ending file yang ada.** Banyak file project memakai CRLF. Jangan mengubah line ending file yang isinya tidak kamu ubah, dan jangan memformat ulang file yang tidak kamu sentuh.
12. **Tulis operasi uang di dalam `DB::transaction`.** Contoh: membayar tagihan membuat catatan pembayaran dan catatan pengeluaran sekaligus atau tidak sama sekali.
13. **Tahan klik ganda.** Gunakan constraint unik dan operasi idempoten (`updateOrCreate`) sehingga tap dua kali tidak membuat data ganda.
14. Tidak ada TODO yang tertinggal, fitur palsu, tombol yang tidak berfungsi, atau kode mati.

---

## 5. Kontrak dengan test (jangan dilanggar)

- Semua 59 test yang ada harus tetap lulus. Jangan mengubah test lama kecuali tak terhindarkan, dan laporkan alasannya.
- Teks berikut di-assert test dan harus tetap muncul persis: **"7 hari mendatang", "Jadwal hari ini", "Total tugas", "Jatuh tempo", "Manajemen Akun", "Simpan perubahan", "Tambah tugas", "Waktu mulai", "Waktu selesai", "jadwal berhasil disimpan"**.
- Elemen dengan id `mobileNavigationMenu` tetap ada di navigasi area pengguna.
- Input waktu tugas tetap `type="datetime-local"`. Warna kategori tetap dirender lewat inline style `background-color` dengan hex persis dari database.
- Beranda baru tetap berada di route `user.dashboard` dan tetap memuat bagian-bagian yang dikunci di atas. Boleh ditata ulang, tetapi tidak boleh dihapus.
- Sebelum mengubah teks atau markup yang sudah ada, `grep` folder `tests/` dulu.

---

## 6. Prinsip UX: "kompleks di dalam, sederhana di luar"

Penggunanya orang awam yang kebanyakan membuka dari HP. Fitur boleh banyak, tetapi setiap layar harus terasa ringan.

1. **Uji 5 detik**: setiap halaman harus membuat pengguna paham apa fungsinya dan apa aksi utamanya dalam 5 detik. Satu aksi utama per layar.
2. **Form minimal dulu.** Formulir dasar maksimal 5 kolom. Sisanya masuk "Pengaturan lanjutan" yang tertutup secara default.
3. **Bahasa kalimat, bukan istilah.** Contoh: "Ulangi: [Setiap hari]" lalu pilihan hari berupa kapsul Sen Sel Rab Kam Jum Sab Min yang bisa diketuk, bukan dropdown "frequency".
4. **Default yang cerdas**: tanggal hari ini, dompet yang terakhir dipakai, "Setiap hari" sebagai pengulangan, jatuh tempo bulan depan untuk cicilan baru.
5. **Status dengan kata, bukan warna saja**: "Lunas", "Belum bayar", "Terlambat 2 hari", "3 hari lagi", "Beruntun 5 hari". Warna dan ikon hanya penguat.
6. **Bisa dibatalkan.** Centang kebiasaan dan pembayaran punya "Batalkan". Aksi menghapus selalu lewat dialog konfirmasi yang menjelaskan akibatnya dengan bahasa awam.
7. **Nada hangat, tanpa menyalahkan.** Saat hari terlewat: "Tidak apa-apa. Mulai lagi hari ini." Jangan memakai kalimat yang bikin bersalah.
8. **Tidak ada layar kosong.** Setiap daftar kosong punya penjelasan, satu tombol aksi, dan (untuk kebiasaan, target, kategori uang) contoh siap pakai yang bisa dipilih dengan satu ketukan.
9. **Format Indonesia di mana-mana**: uang "Rp 1.500.000", tanggal "Senin, 2 November 2026", relatif "besok", "3 hari lagi", "terlambat 2 hari". Minggu dimulai hari Senin.
10. **Input uang yang ramah**: `inputmode="numeric"`, pemisah ribuan otomatis saat mengetik (Alpine), server menormalkan "1.500.000" dan "1500000" menjadi angka. Tampilkan hasil di bawah kolom ("Rp 1.500.000").
11. **Mode privasi saldo**: tombol mata di Beranda dan Keuangan menyembunyikan semua nominal ("Rp •••••"), tersimpan di browser (localStorage), aman dipakai di tempat umum.
12. **Mobile-first**: semua layar baru diuji mentah di lebar 360 px. Tabel berubah menjadi kartu bertumpuk. Target sentuh minimal 44 px. Tanpa scroll horizontal.
13. **Aksesibilitas**: kontras AA, fokus keyboard terlihat, label di setiap kolom, `aria-label` di tombol ikon, status tidak hanya lewat warna, `prefers-reduced-motion` dihormati.
14. **Onboarding pengguna baru**: kartu "Mulai dari mana?" di Beranda (bisa ditutup) dengan tiga langkah: tambah tugas pertama, pilih kebiasaan contoh, catat uang pertama.

### Kamus UX writing (wajib konsisten)

| Konsep teknis | Tulisan di layar |
| --- | --- |
| Habit | Kebiasaan |
| Streak | Beruntun ("Beruntun 5 hari") |
| Check-in | Tandai selesai |
| Skip | Libur hari ini |
| Goal | Target |
| Wallet | Dompet |
| Transaction | Catatan uang |
| Income / Expense / Transfer | Pemasukan / Pengeluaran / Pindah dana |
| Bill / Installment | Tagihan / Cicilan |
| Loan lent | Orang lain berutang ke saya (Piutang) |
| Loan borrowed | Saya berutang (Utang) |
| Outstanding | Sisa |
| Repayment | Pembayaran |
| Due date | Jatuh tempo (terkunci test) |
| Overdue | Terlambat N hari |
| Archive | Arsipkan |
| Daily digest | Ringkasan harian |

---

## 7. Model data dan aturan bisnis

Nama tabel dan kolom dalam Bahasa Inggris. Semua tabel punya `user_id` (FK, cascade), `timestamps`. Gunakan enum PHP (string-backed) + kolom string, bukan kolom `enum` database (supaya SQLite aman), divalidasi dengan `Rule::enum`. Boleh menyesuaikan detail demi kebenaran, tetapi catat perubahannya di laporan Fase 0.

### 7.1 Kebiasaan

**`habits`**

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `category_id` | FK nullable | memakai ulang tabel `categories` yang ada (warna konsisten) |
| `title` | string 120 | |
| `description` | string 500 nullable | |
| `frequency` | string | `daily`, `weekdays`, `weekly_count` |
| `weekdays` | JSON nullable | angka ISO 1 sampai 7 (1 = Senin). Dipakai bila `weekdays`. Contoh lari tiap Minggu: `[7]` |
| `weekly_target` | tinyint nullable | 1 sampai 7. Dipakai bila `weekly_count` (misal 3x seminggu) |
| `goal_value` | unsigned int nullable | target per hari, misalnya 30 |
| `goal_unit` | string 20 nullable | misalnya "menit", "halaman", "kali" |
| `preferred_time` | string 'HH:MM' nullable | jam lokal yang diinginkan |
| `starts_on` | date | |
| `ends_on` | date nullable | |
| `archived_at` | datetime nullable | arsip, bukan hapus |

**`habit_logs`**: `habit_id` (FK cascade), `log_date` (date), `status` (`done` atau `skipped`), `value` (unsigned int nullable, misalnya 35 menit), `note` (string 255 nullable). **Unik `(habit_id, log_date)`**. Indeks `(user_id, log_date)`.

**Aturan beruntun (spesifikasi, wajib diuji unit):**
- *Daily / weekdays*: hari terjadwal = hari yang sesuai aturan antara `starts_on` dan hari ini. Beruntun saat ini = jumlah hari terjadwal berurutan yang selesai, dihitung mundur dari hari ini. Jika hari ini terjadwal tetapi belum dicentang, rangkaian **tidak putus** (dihitung sampai kemarin; hari ini berstatus "menunggu"). Hari non-terjadwal diabaikan. `skipped` ("Libur hari ini") tidak memutus dan tidak menambah. Hari terjadwal yang terlewat tanpa log memutus rangkaian.
- *Weekly_count*: satu minggu (Senin sampai Minggu) "terpenuhi" jika jumlah `done` >= `weekly_target`. Beruntun = jumlah minggu terpenuhi berurutan. Minggu berjalan yang belum terpenuhi tidak memutus.
- Statistik: beruntun saat ini, beruntun terpanjang, persentase selesai 30 hari terakhir (selesai / terjadwal).
- Pengguna boleh mengisi atau mengubah log sampai **7 hari ke belakang**, tidak boleh ke masa depan.
- Yang terjadwal hari ini muncul di Beranda dengan satu ketukan "Tandai selesai". Jika `goal_value` ada, ketukan membuka isian cepat nilai (default = target).

### 7.2 Target

**`goals`**

| Kolom | Keterangan |
| --- | --- |
| `category_id` nullable | |
| `title`, `description` | |
| `type` | `number` (angka bebas, misalnya "baca 12 buku"), `savings` (tabungan, terhubung ke dompet), `habit` (dihitung dari kebiasaan) |
| `target_value` | unsigned bigint (rupiah bila `savings`) |
| `start_value` | unsigned bigint default 0 |
| `unit` | string nullable |
| `due_on` | date nullable |
| `status` | `active`, `achieved`, `archived` |
| `habit_id` nullable | untuk tipe `habit` |
| `wallet_id` nullable | untuk tipe `savings` |

**`goal_updates`** (untuk tipe `number`): `goal_id`, `amount` (unsigned int), `recorded_on` (date), `note` nullable.

**Perhitungan di service `GoalProgress`:**
- `number`: saat ini = `start_value` + jumlah `amount`.
- `habit`: saat ini = jumlah `value` log `done` (jika kebiasaan punya `goal_unit`) atau jumlah hari `done`, sejak `starts_on` kebiasaan atau awal target sampai hari ini.
- `savings`: saat ini = saldo dompet terhubung.
- Persen = `(saat ini - start_value) / (target_value - start_value)`, dibatasi 0 sampai 100.
- Petunjuk kecepatan yang ramah: "Sisa 3,5 juta, 120 hari lagi. Sekitar Rp 29.000 per hari." Jika tenggat lewat dan belum tercapai: "Tenggat sudah lewat. Mau perpanjang?".
- Saat 100%: tampilkan keadaan "Tercapai" dan tombol "Tandai tercapai". Jangan mengubah status otomatis tanpa pengguna menyetujui.

### 7.3 Keuangan

**`wallets`** (Dompet): `name` (60), `type` (`cash`, `bank`, `ewallet`, `savings`, `other`), `opening_balance` (bigInteger default 0), `archived_at`. **Saldo tidak disimpan**, dihitung: saldo awal + pemasukan − pengeluaran + pindah masuk − pindah keluar. Alasannya: mencegah selisih. Gunakan `withSum`/query teragregasi; tanpa N+1.

**`money_categories`**: `name`, `type` (`income`, `expense`), `archived_at`. Buat kategori bawaan **secara malas dan idempoten** saat pengguna pertama membuka Keuangan (jangan mengubah alur registrasi). Contoh pengeluaran: Makan & minum, Transportasi, Belanja, Tagihan, Kesehatan, Pendidikan, Hiburan, Lainnya. Pemasukan: Gaji, Bonus, Lainnya.

**`transactions`** (Catatan uang)

| Kolom | Keterangan |
| --- | --- |
| `wallet_id` | FK |
| `to_wallet_id` | nullable, hanya untuk `transfer` |
| `type` | `income`, `expense`, `transfer` |
| `amount` | unsigned bigint, > 0 |
| `money_category_id` | nullable. Wajib tidak ada untuk `transfer` |
| `occurred_on` | date |
| `note` | string 255 nullable |
| `source_type`, `source_id` | nullable. Menandai asal: `obligation_payment`, `loan`, `loan_repayment` |

Aturan: `transfer` mewajibkan `to_wallet_id` yang berbeda dan milik pengguna. Transaksi dengan `source_type` berisi `loan` atau `loan_repayment` **mempengaruhi saldo dompet tetapi tidak dihitung** sebagai pemasukan/pengeluaran di ringkasan bulanan (karena meminjamkan uang bukan belanja). Pembayaran tagihan dan cicilan **dihitung** sebagai pengeluaran. Indeks `(user_id, occurred_on)` dan `(wallet_id)`.

**`obligations`** (Tagihan dan Cicilan)

| Kolom | Keterangan |
| --- | --- |
| `kind` | `bill` (tagihan rutin tanpa akhir, misalnya listrik) atau `installment` (cicilan dengan jumlah tertentu) |
| `title` | |
| `amount` | nominal per bulan |
| `wallet_id` nullable | dompet pembayaran bawaan |
| `money_category_id` nullable | |
| `due_day` | 1 sampai 31 |
| `starts_on` | date, bulan jatuh tempo pertama |
| `installments_total` | nullable. Wajib bila `installment` |
| `remind_days_before` | default 3 |
| `note`, `archived_at` | |

**`obligation_payments`**: `obligation_id`, `due_on` (date, jatuh tempo periode yang dibayar), `paid_on` (date), `amount_paid`, `transaction_id` (nullable, `nullOnDelete`). **Unik `(obligation_id, due_on)`**.

Aturan jadwal (service `ObligationScheduler`, murni dan diuji unit):
- Jatuh tempo bulan M = `min(due_day, hari terakhir bulan M)`. Uji: tanggal 31 di Februari (28 dan 29), April, Desember.
- Jadwal dihitung dengan rumus dari `starts_on`; yang disimpan hanya pembayaran.
- Cicilan selesai ketika jumlah pembayaran = `installments_total`. Tampilkan "5 dari 12 · sisa 7 · total sisa Rp 5.950.000".
- Status tiap periode: Belum jatuh tempo, Jatuh tempo hari ini, Terlambat N hari, Lunas. Jika beberapa bulan terlewat, tampilkan semuanya (maksimal 12) dan minta bayar dari yang tertua.
- "Bayar": isian jumlah (default = nominal), dompet (default = bawaan), tanggal (default = hari ini). Dalam satu transaksi database: buat `obligation_payment` dan `transaction` pengeluaran yang saling terhubung. "Batalkan pembayaran" menghapus keduanya.

**`loans`** (Utang dan Piutang)

| Kolom | Keterangan |
| --- | --- |
| `direction` | `lent` (orang lain berutang ke saya) atau `borrowed` (saya berutang) |
| `counterparty` | string 80, nama orang |
| `amount` | pokok, > 0 |
| `loan_date` | date, default hari ini |
| `due_on` | date nullable |
| `wallet_id` | nullable. Jika diisi saat membuat, otomatis membuat catatan uang (pengeluaran untuk `lent`, pemasukan untuk `borrowed`) bertanda `source_type = loan` |
| `note` | |

**`loan_repayments`**: `loan_id`, `amount` (> 0), `paid_on` (date), `wallet_id` nullable (bila diisi, membuat catatan uang berlawanan arah, bertanda `loan_repayment`), `transaction_id` nullable, `note`.

Aturan: sisa = pokok − jumlah pembayaran. Pembayaran tidak boleh melebihi sisa (validasi + pemeriksaan di dalam transaksi database). Status dihitung, tidak disimpan: Berjalan, Lunas (sisa 0), Terlambat N hari (`due_on` < hari ini dan sisa > 0), "Jatuh tempo 3 hari lagi". Ringkasan: total piutang (dari N orang) dan total utang. Nilai tambah: tombol **"Salin pesan pengingat"** untuk piutang, menghasilkan teks sopan siap tempel di WhatsApp, misalnya "Halo Budi, mengingatkan pinjaman Rp 500.000 yang jatuh tempo 15 November ya. Terima kasih." (tanpa emoji, dibuat di server dari template di file `lang`).

### 7.4 Agenda terpadu (jantung "Hari ini")

Buat service `AgendaService` yang **hanya membaca** dari semua modul dan mengembalikan daftar item seragam per tanggal (DTO sederhana, tanpa tabel baru):

`tipe` (task, habit, bill, installment, loan_due, goal_due), `judul`, `waktu` (opsional), `status`, `url`, `aksi cepat` (opsional).

Dipakai oleh Beranda dan Kalender. Menambah modul baru di masa depan cukup menambah satu "penyedia" agenda.

### 7.5 Pengaturan dan pengingat

- Kolom baru di `users` (default aman): `digest_enabled` boolean default false, `digest_last_sent_on` date nullable, `onboarding_dismissed_at` datetime nullable.
- **Ringkasan harian lewat email (opsional, mati secara default)**: pengguna mengaktifkannya di Profil. Perintah `php artisan app:send-daily-digest` dijadwalkan **tiap jam** di `Kernel`; mengirim hanya ke pengguna yang aktif opt-in, yang jam lokalnya sudah pukul 07.00 atau lebih dan belum menerima hari itu (`digest_last_sent_on`). Isi: tugas hari ini, kebiasaan hari ini, tagihan dan utang-piutang yang jatuh tempo dalam 3 hari atau terlambat. Email berbahasa Indonesia memakai view mail Laravel yang di-publish dan selaras dengan desain. Dev memakai `MAIL_MAILER=log`. Dokumentasikan baris cron `schedule:run` di README.
- Tanpa push notification atau PWA di versi ini.

---

## 8. Arsitektur kode

- Controller tipis, mengikuti pola `User\TaskController`. Letakkan di `app/Http/Controllers/User/`: `HabitController`, `HabitCheckInController`, `GoalController`, `WalletController`, `TransactionController`, `MoneyCategoryController`, `ObligationController`, `ObligationPaymentController`, `LoanController`, `LoanRepaymentController`, `MoneyOverviewController`, dan seterusnya sesuai kebutuhan.
- Satu **Form Request** per aksi simpan, dengan pesan error Bahasa Indonesia yang spesifik dan memberi solusi, dan `prepareForValidation` untuk menormalkan angka uang.
- **Policy** untuk setiap model baru, didaftarkan dengan benar. Gunakan route model binding yang dibatasi pemilik.
- **Service class murni** di `app/Services/` (atau `app/Support/`), diuji unit: `HabitStreakCalculator`, `GoalProgress`, `ObligationScheduler`, `WalletBalance`, `MonthlySummary`, `LoanBalance`, `AgendaService`. Tanpa repository dan tanpa seremoni berlebihan.
- Helper uang: fungsi `rupiah(int $n): string` dan parser angka input di satu tempat, dipakai di mana-mana.
- Rute modul baru di dalam grup `prefix('dashboard')->name('user.')` yang sudah ada (sama middleware-nya), misalnya `user.habits.*`, `user.goals.*`, `user.money.*`.
- Aktifkan `Model::preventLazyLoading(! app()->isProduction())` di `AppServiceProvider` dan perbaiki semua pelanggaran yang muncul.
- Indeks wajib: `(user_id, log_date)`, `(user_id, occurred_on)`, `(user_id, due_on)` pada tabel yang relevan, dan kolom FK.
- Throttle pada endpoint tulis (misalnya `throttle:60,1`).
- Tambahkan factory untuk semua model baru (dan `Task`/`Category` bila dibutuhkan test) dan perluas seeder dengan data demo yang hanya berjalan di environment `local`.

---

## 9. Navigasi dan layar

**Navigasi bawah di HP (5 slot):** Beranda · Tugas · Kebiasaan · Keuangan · Lainnya (membuka lembar berisi Kalender, Target, Profil, Keluar). Elemen navigasi tetap memiliki id `mobileNavigationMenu`. Di desktop menjadi sidebar yang menampilkan semua menu.

**Tombol "Tambah" global** yang mudah dijangkau jempol membuka lembar **"Mau tambah apa?"** dengan enam pilihan berbahasa awam: Tugas, Kebiasaan, Catat uang, Tagihan atau cicilan, Utang atau piutang, Target. Pastikan tidak menimpa navigasi bawah.

| Modul | Layar |
| --- | --- |
| **Beranda (Hari ini)** | Urutan: (1) "Perlu perhatian": terlambat, tagihan jatuh tempo ≤ 3 hari, utang-piutang jatuh tempo atau terlambat; (2) "Kebiasaan hari ini" dengan cincin atau bar progres "3 dari 5" dan centang satu ketukan; (3) "Jadwal hari ini" (tugas, urut waktu); (4) "7 hari mendatang"; (5) ringkasan uang bulan ini (pemasukan, pengeluaran, sisa) dengan mode privasi; (6) kartu onboarding bila pengguna baru |
| **Kebiasaan** | Daftar (status hari ini, beruntun), tambah/ubah, detail (kalender mini bulan, batang 30 hari dari CSS, beruntun saat ini, terpanjang, persen 30 hari), arsip |
| **Target** | Kartu progres (persen, sisa, perkiraan), tambah/ubah, detail (riwayat kemajuan, tombol "Catat kemajuan") |
| **Keuangan** | Ringkasan (total saldo, per dompet, pemasukan/pengeluaran bulan ini, tagihan terdekat, ringkasan utang-piutang); Catatan uang (per bulan, navigasi bulan, filter, tambah cepat); Dompet; Kategori uang; Tagihan & Cicilan (daftar + "Bayar"); Utang & Piutang (daftar + detail + catat pembayaran); Laporan bulanan |
| **Kalender** | Kalender terpadu dari `AgendaService`: titik per jenis item **dengan legenda berteks**, dan di HP daftar agenda hari terpilih di bawah grid |
| **Profil** | Ditambah: ringkasan harian email (opt-in) dan zona waktu yang sudah ada |

Semua layar memakai komponen yang ada. Komponen baru yang dibutuhkan (kapsul pilih hari, input uang, bar/cincin progres, lencana "3 hari lagi", lembar bawah) dibuat sebagai komponen Blade reusable, ditambahkan ke `/styleguide`, dan didokumentasikan.

### Ikon dan ilustrasi
- **Jangan membuat gaya ikon atau ilustrasi sendiri.** Gunakan `<x-icon>`. Ikon baru yang dibutuhkan (misalnya dompet, kebiasaan, target, uang masuk, uang keluar, pindah dana, tagihan, cicilan, utang, piutang, lonceng, arsip, salin, lainnya) ditambahkan ke `public/icons/sprite.svg` **dengan gaya yang sama persis dengan ikon yang ada** (grid 24, ketebalan garis yang sama, ujung membulat, outline) dan didaftarkan di whitelist komponen `icon`.
- Jika folder `public/ilustrasi/` ada, pakai di empty-state dan halaman error; jika tidak ada, jangan membuat sendiri.
- Palet dan design tokens: **ikuti yang sudah ada**, jangan menggantinya.

---

## 10. Keamanan dan privasi

- Data keuangan sensitif: jangan menulis nominal atau catatan pengguna ke log aplikasi.
- Setiap model baru: test IDOR (pengguna B tidak bisa melihat, mengubah, atau menghapus data pengguna A lewat ID, termasuk lewat `*_id` di request).
- Admin mendapat 403 di semua route modul pengguna (lihat aturan keras no. 4).
- Menghapus akun menghapus seluruh data modul baru (uji cascade).
- Validasi batas nominal yang wajar (misalnya maksimal 999.999.999.999) untuk menghindari overflow dan salah ketik.
- Semua aksi tulis memakai CSRF (bawaan), dan semua keluaran Blade di-escape.

---

## 11. Testing

Target: `php artisan test` hijau di akhir setiap fase.

- **Unit** (service murni): beruntun harian, weekdays, dan weekly_count; libur tidak memutus; hari terlewat memutus; hari ini belum dicentang tidak memutus; batas hari di zona waktu `Asia/Jakarta`, `Asia/Makassar`, `Asia/Jayapura` (log pukul 23.30 dan 00.30); jatuh tempo tanggal 31 di bulan pendek dan Februari kabisat; sisa cicilan; sisa utang dan pelunasan; saldo dompet termasuk transfer; progres target setiap tipe; `AgendaService`.
- **Feature**: CRUD tiap modul, validasi (pesan Indonesia), otorisasi/IDOR, admin 403, klik ganda tidak menduplikasi, membayar tagihan membuat pembayaran + pengeluaran dan membatalkan menghapus keduanya, pembayaran piutang melebihi sisa ditolak, cascade hapus akun.
- **Performa**: uji jumlah query Beranda dan halaman Keuangan dengan data factory yang banyak, dengan batas yang dicatat di test (target: tidak tumbuh seiring jumlah baris).
- Test lama tidak dilemahkan.

---

## 12. Alur kerja (fase)

Setiap fase = migrasi + model + policy + request + controller + view + test + dokumentasi, lalu laporan. Jangan lanjut sebelum pemilik berkata "lanjut".

**Fase 0: Audit dan rencana (tanpa kode fitur).** Baca seluruh project (route, controller, model, policy, test, komponen, token desain, README). Tulis `docs/ARSITEKTUR.md`: diagram relasi tabel (Mermaid), keputusan desain, asumsi yang kamu ambil sendiri, risiko, daftar ikon dan komponen baru, rencana tiap fase. Tanyakan hanya hal yang benar-benar memblokir (maksimal 3 pertanyaan, masing-masing dengan usulan jawaban bawaan).

**Fase 1: Fondasi.** `UserClock`, helper rupiah dan parser angka, `preventLazyLoading`, komponen baru (kapsul hari, input uang, progres, lencana tanggal, lembar bawah), ikon baru, navigasi bawah 5 slot + lembar "Lainnya" + tombol "Tambah" global (tanpa merusak navigasi lama), tambahan `lang`, kerangka `AgendaService`, preset di `config/presets.php`. Semua test lama tetap hijau.

**Fase 2: Kebiasaan.** Bagian 7.1 lengkap, termasuk contoh kebiasaan siap pakai (Belajar bahasa Inggris 30 menit setiap hari, Lari tiap Minggu, Minum air, Baca buku 15 menit, Olahraga 3x seminggu).

**Fase 3: Keuangan A.** Dompet, kategori uang, catatan uang (pemasukan, pengeluaran, pindah dana), ringkasan bulanan, mode privasi saldo.

**Fase 4: Keuangan B.** Tagihan dan cicilan beserta pembayaran, pembatalan, dan status.

**Fase 5: Keuangan C.** Utang dan piutang beserta pembayaran sebagian, tautan opsional ke dompet, dan pesan pengingat siap salin.

**Fase 6: Target.** Bagian 7.2 lengkap.

**Fase 7: Beranda dan Kalender terpadu.** `AgendaService` lengkap, "Perlu perhatian", Beranda baru (tetap memuat teks yang dikunci test), Kalender terpadu dengan legenda berteks, onboarding.

**Fase 8: Pengingat dan pengaturan.** Ringkasan harian email opsional, penjadwal, pengaturan di Profil.

**Fase 9: Laporan, penguatan, dan penutup.** Laporan bulanan sederhana (kategori terbesar dan perbandingan dengan bulan lalu, tanpa library grafik), tinjauan keamanan, audit performa dan aksesibilitas, `docs/DESIGN_SYSTEM.md` dan `/styleguide` dimutakhirkan, README dimutakhirkan (instalasi, seeder, cron `schedule:run`, checklist sebelum online, saran backup). Perbaiki utang teknis kecil di bawah ini setelah melapor, dan jangan melebar.

### Utang teknis yang sudah diketahui (catat; perbaiki yang kecil di Fase 9)
1. Admin aktif terakhir masih bisa menghapus akunnya sendiri lewat halaman Profil (guard hanya ada di panel admin).
2. Filter "terlambat" di daftar tugas masih menghitung tugas yang sudah selesai dan mengabaikan `ends_at`, berbeda dari dashboard.
3. Membuat kategori lewat `category_name` selalu membuat baris baru tanpa memeriksa duplikat.
4. Status "Selesai" masih dicari lewat nama di beberapa tempat.
5. Checklist production di README belum menyebut `db:seed` padahal status tugas dibuat oleh seeder.
6. Laravel 10 dan PHP 8.1 sudah melewati masa dukungan keamanan (verifikasi dulu). Rekomendasikan upgrade sebagai pekerjaan **terpisah**, jangan dicampur dengan fitur.

---

## 13. Format laporan akhir tiap fase

1. Apa yang dibangun (ringkas, per layar dan per tabel).
2. File yang dibuat, diubah, dan dihapus.
3. Keputusan dan asumsi yang kamu ambil sendiri, beserta alasannya.
4. Hasil `php artisan test` dan `npm run build` (atau pernyataan jujur bahwa tidak bisa dijalankan).
5. Daftar test baru dan yang sengaja diubah (dengan alasan).
6. Hal yang belum selesai atau berisiko.
7. Saran untuk fase berikutnya.

---

## 14. Di luar cakupan (jangan dikerjakan sekarang)

Berbagi data atau kolaborasi antar pengguna, banyak mata uang, sinkronisasi atau impor mutasi bank, push notification dan PWA, aplikasi native, fitur berbasis AI, anggaran per kategori (ditunda ke versi berikutnya). Skema sudah memakai `user_id` di semua tabel sehingga tidak menghalangi hal-hal itu di masa depan.

---

## 15. Standar kualitas

Hasil akhirnya harus terasa seperti produk yang dirancang dengan sengaja: fiturnya dalam, tetapi setiap layar terasa ringan; angka uang selalu benar sampai rupiah terakhir; hari selalu jatuh di tanggal yang tepat sesuai zona waktu pengguna; dan orang awam bisa memakainya di HP tanpa membaca petunjuk. Jika ada keputusan yang ambigu, pilih yang paling sederhana dan paling aman bagi pengguna, lalu catat alasannya.
