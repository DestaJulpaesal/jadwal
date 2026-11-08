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

<div data-toast x-data="{ visible: true, timer: null, start() { this.timer = setTimeout(() => this.visible = false, {{ $duration }}) }, pause() { clearTimeout(this.timer) } }" x-init="start()" x-show="visible" x-transition.opacity x-on:mouseenter="pause()" x-on:mouseleave="start()" x-on:pointerdown="pause()" x-on:pointerup="start()" x-on:focusin="pause()" x-on:focusout="if (!$el.contains($event.relatedTarget)) start()" role="{{ $type === 'error' ? 'alert' : 'status' }}" aria-live="{{ $type === 'error' ? 'assertive' : 'polite' }}" class="fixed inset-x-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-50 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-card sm:bottom-auto sm:left-auto sm:right-4 sm:top-20 sm:max-w-sm {{ $tone }}">
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
    <x-button variant="ghost" icon="tutup" icon-only class="shrink-0" aria-label="Tutup pemberitahuan" title="Tutup pemberitahuan" @click="visible = false" />
</div>
