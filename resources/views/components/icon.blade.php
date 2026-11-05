@props(['name', 'class' => 'h-6 w-6'])

@php
    $icons = [
        'beranda', 'tugas', 'kalender', 'profil', 'menu', 'kembali', 'sebelumnya', 'berikutnya',
        'tambah', 'ubah', 'hapus', 'belum-selesai', 'selesai', 'cari', 'filter', 'menu-aksi',
        'tutup', 'lihat-password', 'sembunyikan-password', 'keluar', 'terlambat', 'bentrok',
        'galat', 'info', 'kelola-akun', 'nonaktifkan', 'zona-waktu', 'kebiasaan', 'keuangan',
        'target', 'tagihan', 'utang', 'notifikasi',
    ];

    if (! in_array($name, $icons, true)) {
        throw new InvalidArgumentException("Ikon [{$name}] tidak terdaftar.");
    }
@endphp

<svg {{ $attributes->class([$class, 'shrink-0']) }} aria-hidden="true" focusable="false">
    <use href="{{ asset('icons/sprite.svg#icon-'.$name) }}"></use>
</svg>
