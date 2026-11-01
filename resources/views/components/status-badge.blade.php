@props(['status', 'label' => null])

@php
    $normalized = mb_strtolower(trim($status));
    [$tone, $translated, $icon] = match ($normalized) {
        'selesai', 'aktif', 'active' => ['bg-green-50 text-success', $normalized === 'active' ? 'Aktif' : ucfirst($normalized), 'selesai'],
        'terlambat', 'overdue' => ['bg-amber-50 text-warning', $normalized === 'overdue' ? 'Terlambat' : ucfirst($normalized), 'terlambat'],
        'nonaktif', 'inactive' => ['bg-gray-100 text-muted', $normalized === 'inactive' ? 'Nonaktif' : ucfirst($normalized), 'nonaktifkan'],
        'gagal', 'danger' => ['bg-red-50 text-danger', ucfirst($normalized), 'galat'],
        default => ['bg-primary-50 text-primary-900', $status, null],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold', $tone]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="h-4 w-4" />
    @endif
    {{ $label ?? $translated }}
</span>
