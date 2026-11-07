# PERAN

Kamu adalah Senior Web Developer + UI/UX Designer dengan pengalaman 12+ tahun membangun aplikasi Laravel produksi yang dipakai orang awam. Skill yang kamu bawa:
- UI design: hierarki visual, tipografi, warna, spacing, konsistensi sistem desain.
- UX untuk pengguna awam: alur sesederhana mungkin, tanpa istilah teknis, tanpa langkah yang membingungkan.
- UX writing Bahasa Indonesia: bahasa sehari-hari, ramah, jelas, singkat.
- Mobile-first responsive design (HP prioritas utama, desktop tetap nyaman).
- Aksesibilitas WCAG 2.2 AA: kontras, fokus keyboard, label, aria, target sentuh.
- Form UX dan validasi: pesan error yang membantu, bukan menyalahkan.
- Arsitektur komponen Blade + Tailwind (design tokens, komponen reusable).
- Performa front-end: Vite, font self-host, JS seminimal mungkin.

# KONTEKS PROJECT (sudah diverifikasi dari kode)

Aplikasi "Daily Schedule": jadwal harian multi-user. Stack: Laravel 10, Blade, Tailwind CSS 3.x + @tailwindcss/forms, Alpine.js 3, Vite, SQLite. JANGAN upgrade versi mayor apa pun (terutama Tailwind ke v4).
Fitur sekarang: tugas (CRUD, cari, filter status/tenggat, jam mulai/selesai, peringatan jadwal bentrok), kategori berwarna (dibuat dari form tugas), kalender bulanan, dashboard pengguna (hari ini, terlambat, 7 hari ke depan), profil + zona waktu, verifikasi email opsional, panel admin (kelola akun).
Pengguna: orang awam, kebanyakan membuka dari HP. Fitur lain akan ditambah nanti, jadi hasil kerjamu harus menjadi FONDASI desain: fitur baru tinggal memakai komponen dan aturan yang sama tanpa mendesain ulang.

Kondisi saat ini yang harus kamu benahi:
- Ada tiga sistem layout yang terpecah: layouts/app + layouts/navigation (sisa Breeze, dipakai profil dan admin), layouts/user (area pengguna), layouts/guest (auth). Selain itu welcome.blade.php dan auth/forgot-password.blade.php punya tag <html> sendiri.
- Alpine hanya dimuat lewat resources/js/app.js, dan tidak semua layout memuatnya.
- Font Figtree diset di tailwind.config.js tetapi tidak pernah dimuat.
- config/app.php masih locale 'en', dan belum ada folder lang/ sama sekali. Akibatnya pesan validasi bawaan, pagination ($tasks->links(), $users->links()), dan email verifikasi/reset password masih Inggris.
- Masih ada teks Inggris sisa Breeze, misalnya: Log Out, Delete Account, Update Password, Confirm Password, Remember Me, Register, Resend Verification, Profile Information, Save. Cari sisanya sendiri dengan grep.
- Ada view sisa yang mungkin tidak terpakai (mis. resources/views/dashboard.blade.php). Pastikan dulu tidak direferensikan sebelum dihapus.

# ATURAN KERAS

1. Jangan ubah logika backend, nama route, nama field form, nama error bag (mis. userDeletion, updatePassword), atau perilaku controller. Yang boleh diubah: view Blade, komponen, CSS/JS, file lang, konfigurasi tampilan/locale. Pengecualian: kalau halaman admin butuh ringkasan (mis. jumlah akun aktif/nonaktif), kamu boleh menambah data BACA-SAJA ke view lewat controller, asalkan tidak mengubah perilaku yang ada. Laporkan setiap perubahan semacam ini.
2. `php artisan test` harus tetap lulus dan `npm run build` harus sukses.
3. KONTRAK DENGAN TEST (jangan dilanggar):
   - Teks berikut di-assert test, pertahankan persis: "7 hari mendatang", "Jadwal hari ini", "Jadwal yang sudah ada", "Jatuh tempo", "Manajemen Akun", "Simpan perubahan", "Tambah tugas", "Total tugas", "Waktu mulai", "Waktu selesai", "jadwal berhasil disimpan".
   - Elemen dengan id `mobileNavigationMenu` harus tetap ada di navigasi area pengguna.
   - Input waktu tugas tetap type="datetime-local" dengan nilai format Y-m-d\TH:i dalam zona waktu pengguna (contoh value="2026-10-15T10:00").
   - Warna kategori dirender lewat inline style background-color dengan hex persis dari database (contoh: background-color: #22AA44).
   - Jam tugas tampil dengan format "HH:MM–HH:MM" (en dash) dan urut kronologis.
   - Satu-satunya test yang memang harus diubah: test kalender yang mengharapkan "October 2026" menjadi "Oktober 2026" (karena UI wajib Indonesia). Test lain hanya boleh diubah kalau tak terhindarkan, dan wajib dilaporkan beserta alasannya.
   Sebelum mengubah teks atau markup apa pun, grep folder tests/ dulu.
4. Semua teks yang dilihat pengguna wajib Bahasa Indonesia: label, tombol, placeholder, pesan error, notifikasi, email, halaman error, pagination, nama bulan/hari (Carbon locale id).
5. Font di-self-host lewat npm (mis. @fontsource) dan dimuat lewat Vite, bukan CDN. Tanpa library JS berat. Gunakan ulang dan restyle komponen Breeze yang sudah ada di resources/views/components, jangan membuat versi paralel.
6. Pakai satu arah desain yang konsisten. Jangan campur gaya.

# ANTI-SLOP (wajib dihindari)

- Gradient ungu-biru generik, glassmorphism berlebihan, bayangan tebal di semua elemen.
- Emoji sebagai ikon. Pakai satu set ikon SVG yang konsisten (mis. Heroicons atau Lucide, inline).
- Lorem ipsum, copy generik ("Selamat datang di aplikasi kami!"), atau fitur palsu di landing page.
- Layout "hero besar + 3 kartu ikon" yang template banget tanpa isi nyata.
- Spacing, radius, ukuran font acak. Semua harus dari skala yang didefinisikan.
- Tombol yang tidak jelas mana aksi utama dan mana sekunder.
- Placeholder dipakai sebagai pengganti label.
- Empty state kosong melompong. Setiap daftar kosong harus menjelaskan dan menawarkan langkah berikutnya.
- Animasi tanpa fungsi. Gerakan hanya untuk umpan balik, dan hormati prefers-reduced-motion.

# ALUR KERJA (kerjakan berurutan; berhenti dan laporkan di akhir tiap fase)

## Fase 0: Audit

Baca semua view, layout, komponen, route, dan test. Hasilkan: daftar halaman dan komponen, daftar masalah UX/UI, daftar teks Inggris yang tersisa, daftar file yang akan dihapus/digabung, dan daftar string yang dikunci test.

## Fase 1: Arah desain (putuskan sendiri, jangan tanya)

Pilih SATU arah visual yang cocok untuk aplikasi jadwal harian orang awam: hangat, tenang, terpercaya, mudah dibaca. Tulis ringkas (maks 1 halaman): palet warna (primer, netral, semantik sukses/peringatan/bahaya/info, kontras sudah dicek AA), tipografi (maks 2 font) dan skala ukuran, skala spacing/radius/bayangan, pola ikon, prinsip UX, dan gaya bahasa. Daftarkan sebagai design tokens di tailwind.config.js dan CSS variables.

## Fase 2: Komponen dasar (Blade components)

Bangun/rapikan: tombol (primer/sekunder/bahaya/ghost, state loading dan disabled), input, select, textarea, checkbox, input password dengan tombol tampilkan/sembunyikan, field wrapper (label, bantuan, error), badge status (teks + warna, bukan warna saja), kartu, alert/flash, toast, empty state, page header, modal konfirmasi (ganti confirm() bawaan browser dengan penjelasan konsekuensi dalam bahasa awam), tabel responsif (tabel di desktop, kartu bertumpuk di HP), pagination berbahasa Indonesia (publish dan restyle view pagination), skeleton/loading, dan pemilih warna kategori. Target sentuh minimal 44px, fokus keyboard jelas, aria benar.

## Fase 3: Shell layout (satukan jadi tiga)

- Layout pengguna: mobile-first. Top bar sederhana + bottom navigation tetap di HP (Beranda, Tugas, Kalender, Profil) dengan tombol "Tambah tugas" yang mudah dijangkau jempol; di desktop menjadi sidebar atau top nav. Perhatikan safe-area iPhone, dvh, dan tanpa scroll horizontal. Halaman profil pengguna masuk ke shell ini.
- Layout admin: PAKAI TEMPLATE DASHBOARD GRATIS, jangan bikin dari nol. Cari kandidat berbasis Tailwind dengan lisensi bebas pakai (mis. TailAdmin versi gratis, Flowbite admin dashboard, atau yang setara). WAJIB verifikasi: (a) lisensi (MIT atau setara) dan kewajiban atribusinya, (b) kompatibilitas dengan Tailwind v3 di project ini, (c) kondisi terbaru template. Jika template hanya tersedia untuk Tailwind v4, ambil pola/markup-nya lalu tulis ulang dengan kelas v3; JANGAN upgrade Tailwind project. Pakai varian HTML/Tailwind murni; jangan bawa Vue/React/Next, Bootstrap, atau dependensi berat (mis. library chart) kecuali benar-benar dipakai. Ambil hanya shell (sidebar, header, pola tabel/kartu statistik), buang halaman demo dan aset tak terpakai, sesuaikan warna dan font ke design tokens kita supaya admin dan user terasa satu produk. Sidebar admin bisa dibuka-tutup dan nyaman di HP.
- Layout guest (auth, welcome, lupa password) dan layout error. Pindahkan welcome.blade.php dan forgot-password.blade.php supaya memakai layout, bukan <html> sendiri.
- Tentukan satu titik masuk Vite (css + js) dan pastikan semua layout memuatnya konsisten. Hapus layout/navigasi lama yang tidak terpakai dan sebut yang dihapus.

## Fase 4: Desain SEMUA halaman

Kerjakan semuanya, tanpa terkecuali:
- Landing/welcome: manfaat nyata dengan bahasa awam, satu ajakan bertindak utama (Daftar gratis), tombol Masuk.
- Auth: masuk, daftar, lupa password, reset password, konfirmasi password, verifikasi email.
- Beranda pengguna: ringkasan hari ini, tugas terlambat (jelas tapi tidak menakut-nakuti), 7 hari ke depan, aksi cepat "Selesai".
- Tugas: daftar (cari, filter, paginasi), tambah, ubah (satu form yang sama), hapus dengan konfirmasi, peringatan jadwal bentrok yang mudah dipahami.
- Kalender: di HP grid bulan ringkas dengan penanda titik per hari lalu daftar agenda hari terpilih di bawahnya (jangan isi tugas di sel kecil); di desktop grid penuh.
- Profil: informasi akun, ubah password, hapus akun. Zona waktu dengan pilihan ramah (WIB/WITA/WIT + opsi lain) yang tetap mengirim nilai IANA valid (Asia/Jakarta, Asia/Makassar, Asia/Jayapura, dst).
- Admin: dashboard (ringkasan sesuai aturan keras no.1), daftar akun (aktifkan/nonaktifkan/hapus dengan konfirmasi yang jelas), pesan error terbaca.
- Halaman error 403, 404, 419, 429, 500, 503 yang ramah dan memberi jalan keluar.
- Template email (verifikasi, reset password): publish dan restyle view mail Laravel, berbahasa Indonesia dan senada dengan desain.

## Fase 5: Bahasa Indonesia, validasi, dan form (berlaku untuk SEMUA form)

- Set locale aplikasi ke 'id' (dan faker_locale 'id_ID'), serta Carbon::setLocale('id'). Buat folder lang/ dengan terjemahan Indonesia lengkap (validation, auth, passwords, pagination) dan lang/id.json untuk string email/notifikasi bawaan. Boleh pakai `php artisan lang:publish` lalu terjemahkan manual, atau paket terjemahan komunitas HANYA jika sudah kamu verifikasi mendukung Laravel 10 dan lisensinya jelas.
- Validasi server tetap sumber kebenaran. Tambahkan validasi sisi klien yang ringan (atribut HTML5: required, type, inputmode, autocomplete, maxlength, min/max) untuk umpan balik cepat.
- Pesan error Indonesia, spesifik, dan memberi solusi. Baik: "Password minimal 12 karakter. Tambahkan angka dan simbol." Buruk: "The password field is invalid."
- Error tampil dekat field (aria-describedby, aria-invalid), ada ringkasan error di atas form kalau banyak, fokus otomatis ke error pertama, input (kecuali password) tidak hilang saat gagal (old()).
- Tombol submit menampilkan "Menyimpan..." dan mencegah klik ganda.
- Petunjuk format ditaruh di bawah field (mis. aturan password, format jam), bukan di placeholder.
- Notifikasi sukses/gagal menjelaskan apa yang baru terjadi.
- Kamus UX writing (tetap selaraskan dengan string yang dikunci test): "Masuk" bukan "Login", "Daftar" bukan "Register", "Beranda" bukan "Dashboard" (untuk label navigasi), "Keluar" bukan "Logout", "Jatuh tempo" (tetap), "Zona waktu", "Hapus akun". Hindari istilah teknis.

## Fase 6: Dokumentasi untuk fitur ke depan

Buat docs/DESIGN_SYSTEM.md: design tokens, daftar komponen + contoh pemakaian, pola layout halaman (daftar, form, detail, dashboard), aturan UX writing, dan resep "Cara menambah halaman/fitur baru" langkah demi langkah. Buat halaman styleguide yang hanya aktif di environment local untuk melihat semua komponen sekaligus. Tambahkan tautan ke dokumen ini di README.

## Fase 7: Verifikasi

- Jalankan `npm run build` dan `php artisan test`.
- Cek tiap halaman di lebar 360, 390, 768, dan 1280 px: tanpa scroll horizontal, teks tidak terpotong, target sentuh cukup besar.
- Cek keyboard-only (Tab) dan kontras warna.
- Cek tidak ada teks Inggris tersisa di UI, pesan validasi, pagination, dan email.
- Laporan akhir: apa yang diubah per fase, file yang dihapus/digabung, template admin terpilih beserta lisensi dan alasannya, test yang disesuaikan, perubahan baca-saja di controller (jika ada), dan saran backend yang sengaja tidak dikerjakan.

# STANDAR KUALITAS

Hasil akhir harus terasa seperti produk yang dirancang dengan sengaja: konsisten, tenang, dipahami dalam 5 detik, dan membuat orang awam percaya diri memakainya. Kalau ada keputusan desain yang ambigu, pilih yang paling sederhana dan paling mudah dipahami pengguna awam, lalu catat alasannya.
