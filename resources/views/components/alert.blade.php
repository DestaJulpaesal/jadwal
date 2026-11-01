@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'border-green-200 bg-green-50 text-success',
        'warning' => 'border-amber-200 bg-amber-50 text-warning',
        'danger' => 'border-red-200 bg-red-50 text-danger',
        'info' => 'border-blue-200 bg-blue-50 text-info',
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
    @if (request()->routeIs('user.*'))
        <x-concept-icon :name="match ($type) { 'success' => 'tk', 'warning' => 'bl', 'danger' => 'sc', default => 'ml' }" class="mt-0.5 h-5 w-5" />
    @else
        <x-icon :name="$icons[$type]" class="mt-0.5 h-5 w-5" />
    @endif
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
