@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'border-line bg-success-soft text-success',
        'warning' => 'border-line bg-warning-soft text-warning',
        'danger' => 'border-line bg-danger-soft text-danger',
        'info' => 'border-line bg-info-soft text-info',
    ];
    $icons = [
        'success' => 'selesai',
        'warning' => 'bentrok',
        'danger' => 'galat',
        'info' => 'info',
    ];
    $role = $type === 'danger' ? 'alert' : 'status';
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$styles[$type]]) }} role="{{ $role }}">
    <x-icon :name="$icons[$type]" class="mt-0.5 h-5 w-5" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
