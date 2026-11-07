@props(['type' => 'success', 'action' => null])

@php
    $tone = $type === 'danger' ? 'border-red-200 bg-red-50 text-danger' : 'border-green-200 bg-green-50 text-success';
@endphp

<div x-data="{ visible: true, timer: null, start() { this.timer = setTimeout(() => this.visible = false, 6000) }, pause() { clearTimeout(this.timer) } }" x-init="start()" x-show="visible" x-transition.opacity x-on:mouseenter="pause()" x-on:mouseleave="start()" x-on:focusin="pause()" x-on:focusout="start()" role="{{ $type === 'danger' ? 'alert' : 'status' }}" aria-live="{{ $type === 'danger' ? 'assertive' : 'polite' }}" class="fixed inset-x-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-50 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-card sm:bottom-auto sm:left-auto sm:right-4 sm:top-20 sm:max-w-sm {{ $tone }}">
    <p class="flex-1">{{ $slot }}</p>
    @if (is_array($action) && isset($action['label'], $action['url']))
        <a class="shrink-0 font-semibold underline" href="{{ $action['url'] }}">{{ $action['label'] }}</a>
    @endif
    <x-button variant="ghost" icon="tutup" icon-only class="shrink-0" aria-label="Tutup pemberitahuan" title="Tutup pemberitahuan" @click="visible = false" />
</div>
