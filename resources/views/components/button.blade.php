@props([
    'variant' => 'secondary',
    'size' => 'md',
    'icon' => null,
    'iconOnly' => false,
    'href' => null,
    'type' => null,
    'loadingText' => null,
])

@php
    $variants = ['primary', 'secondary', 'ghost', 'danger'];
    $sizes = ['sm', 'md', 'lg'];

    if (! in_array($variant, $variants, true)) {
        throw new InvalidArgumentException("Varian tombol [{$variant}] tidak dikenal.");
    }

    if (! in_array($size, $sizes, true)) {
        throw new InvalidArgumentException("Ukuran tombol [{$size}] tidak dikenal.");
    }

    if ($iconOnly && (! $attributes->has('aria-label') || ! $attributes->has('title'))) {
        throw new InvalidArgumentException('Tombol ikon harus memiliki aria-label dan title.');
    }

    $class = trim($attributes->get('class', ''));
    $legacyVariants = [
        'btn-primary' => 'primary',
        'btn-secondary' => 'secondary',
        'btn-danger' => 'danger',
        'btn-quiet' => 'ghost',
        'btn-icon' => 'ghost',
        'go' => 'primary',
        'global-search-button' => 'primary',
    ];

    foreach ($legacyVariants as $legacyClass => $legacyVariant) {
        if (preg_match('/(?:^|\s)'.preg_quote($legacyClass, '/').'(?=\s|$)/', $class)) {
            $variant = $legacyVariant;
            $iconOnly = $iconOnly || in_array($legacyClass, ['btn-icon', 'global-search-button'], true);
            $class = preg_replace('/(?:^|\s)'.preg_quote($legacyClass, '/').'(?=\s|$)/', '', $class);
        }
    }

    $slotMarkup = (string) $slot;
    $label = mb_strtolower(trim(strip_tags($slotMarkup)));

    if (! $icon && ! $iconOnly && ! str_contains($slotMarkup, '<svg')) {
        $icon = match (true) {
            str_contains($label, 'hapus') => 'hapus',
            str_contains($label, 'tambah'), str_contains($label, 'buat akun'), str_contains($label, 'daftar'), str_contains($label, 'catat') => 'tambah',
            str_contains($label, 'simpan'), str_contains($label, 'selesai'), str_contains($label, 'tandai'), str_contains($label, 'konfirmasi') => 'selesai',
            str_contains($label, 'ubah'), str_contains($label, 'edit') => 'ubah',
            str_contains($label, 'masuk') => 'kelola-akun',
            str_contains($label, 'keluar') => 'keluar',
            str_contains($label, 'cari') => 'cari',
            str_contains($label, 'filter'), str_contains($label, 'terapkan') => 'filter',
            str_contains($label, 'berikutnya') => 'berikutnya',
            str_contains($label, 'sebelumnya') => 'sebelumnya',
            str_contains($label, 'lanjut') => 'berikutnya',
            str_contains($label, 'batal'), str_contains($label, 'kembali'), str_contains($label, 'reset') => 'kembali',
            str_contains($label, 'tutup') => 'tutup',
            str_contains($label, 'password'), str_contains($label, 'kata sandi') => str_contains($label, 'sembunyikan') ? 'sembunyikan-password' : 'lihat-password',
            str_contains($label, 'detail'), str_contains($label, 'lihat') => 'info',
            default => $label !== '' ? 'info' : null,
        };
    }

    $class = trim(preg_replace('/\s+/', ' ', $class));
    $classes = 'button button--'.$variant.' button--'.$size.($iconOnly ? ' button--icon-only' : '');
    $attributes = $attributes->except(['class', 'href', 'type', 'variant', 'size', 'icon', 'iconOnly', 'loadingText']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => trim($classes.' '.$class)]) }}>
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5" />
        @endif
        {{ $slot }}
    </a>
@else
    <button @if ($type) type="{{ $type }}" @endif @if ($loadingText) data-loading-text="{{ $loadingText }}" @endif {{ $attributes->merge(['class' => trim($classes.' '.$class)]) }}>
        @if ($icon)
            <x-icon :name="$icon" class="h-5 w-5" />
        @endif
        {{ $slot }}
    </button>
@endif
