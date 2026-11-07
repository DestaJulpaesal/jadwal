# Ikon Daily Schedule (set custom)

Set ikon buatan khusus untuk project ini. Bukan dari library pihak ketiga, jadi tidak ada lisensi atau atribusi eksternal.

## Gaya ikon

1. Kotak 24x24 dengan garis sederhana `currentColor` setebal 1.8 dan ujung membulat.
2. Ikon outline tanpa blok warna, bayangan, atau teks dekoratif.
3. Warna ikon mengikuti warna teks (`text-*`) agar konsisten dengan konteks tombol dan navigasi.

## Cara memakai

Sprite dan komponen Blade sudah tersedia di root project:

- `public/icons/sprite.svg` berisi semua ikon khusus.
- `resources/views/components/icon.blade.php` menyediakan `<x-icon>`.
- `/styleguide` menampilkan pratinjau set ikon di lingkungan lokal.

Pakai di Blade:

```blade
<x-icon name="tambah" />
<x-icon name="terlambat" class="h-5 w-5" />
```

## Daftar ikon

### Navigasi

| Nama | Dipakai untuk |
| --- | --- |
| `beranda` | Ringkasan jadwal hari ini |
| `tugas` | Daftar semua tugas |
| `kalender` | Jadwal per bulan |
| `profil` | Data akun dan zona waktu |
| `menu` | Buka sidebar admin di layar kecil |
| `kembali` | Kembali ke halaman sebelumnya |
| `sebelumnya` | Bulan atau halaman sebelumnya |
| `berikutnya` | Bulan atau halaman berikutnya |

### Aksi

| Nama | Dipakai untuk |
| --- | --- |
| `tambah` | Buat tugas baru |
| `ubah` | Ubah isi tugas |
| `hapus` | Hapus (selalu minta konfirmasi) |
| `belum-selesai` | Kotak centang tugas yang belum beres |
| `selesai` | Tugas sudah selesai |
| `cari` | Cari tugas dari judul |
| `filter` | Saring menurut status atau tenggat |
| `menu-aksi` | Menu Ubah dan Hapus di tiap baris |
| `tutup` | Tutup dialog atau pesan |
| `lihat-password` | Tampilkan password |
| `sembunyikan-password` | Sembunyikan password |
| `keluar` | Keluar dari akun |

### Status

| Nama | Dipakai untuk |
| --- | --- |
| `terlambat` | Tugas lewat dari tenggat |
| `bentrok` | Jadwal bertabrakan dengan tugas lain |
| `galat` | Pesan kesalahan di form |
| `info` | Petunjuk di bawah kolom isian |

### Admin dan akun

| Nama | Dipakai untuk |
| --- | --- |
| `kelola-akun` | Daftar pengguna (admin) |
| `nonaktifkan` | Matikan akses akun tanpa menghapus |
| `zona-waktu` | Pilih WIB, WITA, atau WIT |

## Aturan pemakaian

1. Ukuran: `h-5 w-5` di dalam teks atau badge, `h-6 w-6` (bawaan) untuk tombol dan navigasi.
2. Navigasi dan aksi penting wajib ikon plus teks. Tombol yang hanya berisi ikon wajib `label` (aria-label) dan area ketuk minimal 44x44 px.
3. Ikon status (`terlambat`, `bentrok`, `galat`) tidak boleh berdiri sendiri: selalu ditemani teks.
4. Di HP, aksi `ubah` dan `hapus` masuk ke `menu-aksi`. Yang tampil langsung di baris tugas hanya kotak centang (`belum-selesai` / `selesai`).
5. `hapus` dan `nonaktifkan` selalu lewat dialog konfirmasi yang menjelaskan akibatnya dengan bahasa awam.
6. Satu makna, satu ikon di seluruh halaman.
7. Folder `public/icons/png/` (128 px, transparan) hanya untuk email atau presentasi. Di website pakai sprite SVG.
