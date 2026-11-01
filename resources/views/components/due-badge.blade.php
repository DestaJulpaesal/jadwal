@props(['days'])

@php
    [$label, $tone] = match (true) {
        $days < 0 => ['Terlambat '.abs($days).' hari', 'border-red-200 bg-red-50 text-danger'],
        $days === 0 => ['Jatuh tempo hari ini', 'border-amber-200 bg-amber-50 text-warning'],
        $days === 1 => ['Besok', 'border-amber-200 bg-amber-50 text-warning'],
        default => [$days.' hari lagi', 'border-line bg-white text-muted'],
    };
@endphp
<span {{ $attributes->class(['inline-flex min-h-7 items-center rounded-full border px-2.5 py-1 text-xs font-semibold', $tone]) }}>{{ $label }}</span>
