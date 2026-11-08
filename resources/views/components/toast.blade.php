@props(['type' => 'success', 'action' => null])

@php
    $type = $type === 'danger' ? 'error' : $type;
    $tones = [
        'success' => 'border-green-200 bg-green-50 text-success',
        'error' => 'border-red-200 bg-red-50 text-danger',
        'warning' => 'border-amber-200 bg-amber-50 text-warning',
        'info' => 'border-blue-200 bg-blue-50 text-info',
    ];
    $tone = $tones[$type] ?? $tones['info'];
    $duration = $action ? 8000 : 6000;
@endphp

<div data-toast data-toast-duration="{{ $duration }}" role="{{ $type === 'error' ? 'alert' : 'status' }}" aria-live="{{ $type === 'error' ? 'assertive' : 'polite' }}" class="pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-card {{ $tone }}">
    <span aria-hidden="true">
        @if ($type === 'success')
            <x-icon name="selesai" class="h-5 w-5" />
        @elseif ($type === 'error')
            <x-icon name="galat" class="h-5 w-5" />
        @elseif ($type === 'warning')
            <x-icon name="terlambat" class="h-5 w-5" />
        @else
            <x-icon name="info" class="h-5 w-5" />
        @endif
    </span>
    <p class="flex-1">{{ $slot }}</p>
    @if (is_array($action) && isset($action['label'], $action['url']))
        <a class="shrink-0 font-semibold underline" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
    @endif
    <x-button variant="ghost" icon="tutup" icon-only class="shrink-0" data-toast-dismiss aria-label="Tutup pemberitahuan" title="Tutup pemberitahuan" />
</div>
