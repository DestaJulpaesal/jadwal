# Sistem desain Jadwal Harian

Fondasi visual ini dibuat untuk aplikasi sehari-hari yang digunakan terutama lewat ponsel. Gunakan komponen dan skala yang sama saat menambah fitur; hindari membuat pola alternatif.

## Arah visual

Ramah, tenang, terpercaya, dan mudah dibaca. Permukaan putih berada di atas latar abu-hijau yang lembut; hijau tua menandai tindakan utama. Hindari gradien dekoratif, bayangan tebal, dan ornamen yang tidak membantu pekerjaan.

## Token desain

Token didefinisikan di `tailwind.config.js` dan sebagai variabel CSS di `resources/css/app.css`.

| Peran | Nilai |
|---|---|
| Latar | `#f7f8f5` |
| Permukaan | `#ffffff` |
| Teks utama | `#1d2925` |
| Teks pendamping | `#52615b` |
| Garis/border | `#dce4df` |
| Primer | `#226b50` |
| Sukses | `#216747` |
| Peringatan | `#85520b` |
| Bahaya | `#a3312c` |
| Informasi | `#245d81` |

Semua token teks pada permukaan putih memiliki rasio kontras minimal 6,39:1 (primer), melampaui WCAG AA 4,5:1 untuk teks normal. Periksa kembali kontras jika menambahkan kombinasi baru; jangan memakai warna sebagai satu-satunya penanda status.

Figtree di-self-host melalui `@fontsource/figtree`; gunakan sans-serif sistem sebagai cadangan. Skala teks utama: 12–14 px untuk keterangan, 16 px untuk isi, 20–24 px untuk judul bagian, 32–48 px untuk judul halaman/landing. Maksimal satu keluarga font.

Gunakan skala Tailwind yang tersedia untuk jarak (kelipatan 4 px), sudut 8 px untuk kontrol dan 12 px untuk kartu, serta bayangan tipis `shadow-card` hanya pada permukaan yang perlu dipisahkan. Target sentuh minimal 44 px. Semua fokus keyboard memakai outline yang jelas dan gerakan mengikuti `prefers-reduced-motion`.

## Komponen Blade

- `<x-icon name="kalender" />`: gunakan ikon di `public/icons/sprite.svg`; garis mengikuti `currentColor` dan tiap ikon hanya memakai satu aksen datar dari palet khusus. Nama ikon harus berasal dari daftar pada `DAFTAR_IKON_CUSTOM.md`.
- `<x-primary-button>` dan `.btn-primary`: satu aksi utama per bagian.
- `<x-secondary-button>` dan `.btn-secondary`: aksi pendamping atau batal.
- `<x-danger-button>` dan `.btn-danger`: tindakan yang menghapus atau berdampak besar.
- `<x-input-label>`, `<x-text-input>`, `<x-input-error>`, `.form-control`, dan `<x-field>`: label selalu terlihat; bantuan dan error berada dekat kolom.
- `[data-password-toggle]`: tombol tampil/sembunyikan password dengan label aksesibel.
- `<x-status-badge>`: status memiliki teks dan warna.
- `<x-alert>` untuk pesan tetap; `<x-toast>` untuk konfirmasi singkat.
- `<x-confirm-action>` dan `<x-modal>` untuk menjelaskan konsekuensi sebelum tindakan penting.
- `<x-page-header>`, `<x-empty-state>`, dan `<x-skeleton>` untuk pola halaman yang konsisten.
- `<x-money-input>` untuk nilai Rupiah tanpa desimal; nilai saldo awal boleh negatif. Mode privasi hanya menyimpan pilihan tampil/sembunyi di `localStorage`, bukan nominal.
- Grafik laporan sederhana memakai batang CSS dengan label dan nilai teks; jangan menambahkan pustaka chart.
- Tabel desktop harus memiliki bentuk kartu/list pada ponsel; lihat `admin/users/index.blade.php`.
- Di ponsel, aksi ubah/hapus tugas berada di menu aksi; aksi selesai tetap punya target sentuh minimal 44 px.
- Pagination menggunakan view `resources/views/vendor/pagination/tailwind.blade.php`.

Contoh kolom:

```blade
<x-field id="title" label="Judul tugas" help="Gunakan nama yang mudah dikenali." :error="$errors->first('title')">
    <input id="title" name="title" class="form-control" required maxlength="255"
        aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}">
</x-field>
```

Komponen shell admin mengambil pola navigasi samping dan header dari Flowbite Admin Dashboard, lalu ditulis ulang agar tetap kompatibel dengan Tailwind CSS 3. Proyek Flowbite Admin Dashboard berlisensi MIT; sumber: [GitHub](https://github.com/themesberg/flowbite-admin-dashboard), lisensi: [MIT](https://github.com/themesberg/flowbite-admin-dashboard/blob/main/LICENSE). Lisensi mewajibkan pemberitahuan hak cipta dan izin MIT disertakan jika bagian perangkat lunak didistribusikan. Versi `main` yang diperiksa bertanda 2.0.0 dan sudah memakai Tailwind 4 pada dependensi terbarunya; tidak ada dependensi Flowbite/JavaScript demo yang ditambahkan ke aplikasi ini.

## Pola halaman

- **Daftar:** judul dan aksi utama, filter berlabel, hasil dengan keadaan kosong yang memberi langkah berikutnya, aksi per baris, pagination.
- **Formulir:** ringkasan error bila diperlukan, label/help/error per kolom, nilai lama dipertahankan, tombol simpan dan batal.
- **Detail:** judul dan status, informasi dikelompokkan, aksi utama terlihat dan aksi berisiko dikonfirmasi.
- **Beranda:** ringkasan singkat yang dapat ditindaklanjuti; gunakan bahasa yang tidak menyalahkan untuk tugas lewat tenggat.
- **Kalender:** titik ringkas di grid ponsel dan agenda tanggal terpilih di bawahnya; grid penuh untuk layar besar.
- **Form keuangan:** tampilkan maksimal lima kolom dasar; rincian opsional berada dalam `<details>` dengan label yang jelas.
- **Laporan:** ringkasan angka, perbandingan bulan sebelumnya, dan kategori terbesar; nilai dan progress tetap tersedia sebagai teks, bukan warna saja.

## UX writing

Gunakan bahasa Indonesia sehari-hari, kalimat ringkas, dan kata kerja yang menjelaskan hasil. Tulis “Masuk”, “Daftar”, “Beranda”, “Keluar”, “Jatuh tempo”, “Zona waktu”, dan “Hapus akun”. Jangan menyalahkan pengguna; jelaskan cara memperbaiki kesalahan. Jangan gunakan placeholder sebagai pengganti label atau ikon/warna tanpa teks.

## Menambah halaman atau fitur

1. Pastikan route dan perilaku backend yang ada tidak diubah; gunakan layout `layouts.user`, `layouts.admin`, atau komponen `x-guest-layout`.
2. Mulai halaman dengan `<x-page-header>` dan gunakan token dari Tailwind config/CSS, bukan nilai warna atau radius baru.
3. Gunakan kembali komponen Blade untuk tombol, kolom, status, pesan, keadaan kosong, dan konfirmasi.
4. Pertahankan label terlihat, hubungan `aria-describedby`, `aria-invalid`, fokus keyboard, serta target sentuh minimal 44 px.
5. Untuk form, pertahankan nama field dan error bag; tambahkan atribut HTML yang membantu validasi awal tanpa menggantikan validasi server.
6. Sediakan keadaan memuat, berhasil, gagal, kosong, dan layar kecil. Tabel desktop perlu alternatif bertumpuk untuk ponsel.
7. Terjemahkan semua string dan format tanggal/waktu ke Bahasa Indonesia; periksa `lang/id` sebelum menambahkan terjemahan baru.
8. Periksa tampilan pada 360, 390, 768, dan 1280 px, keyboard, kontras, pengungkapan `<details>`, lalu jalankan `npm run build` dan `php artisan test`.

Pratinjau komponen hanya tersedia pada lingkungan lokal melalui `/styleguide`.
