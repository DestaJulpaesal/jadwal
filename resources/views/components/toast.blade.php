@props(['type' => 'success'])

@php
    $tone = $type === 'danger' ? 'border-red-200 bg-red-50 text-danger' : 'border-green-200 bg-green-50 text-success';
@endphp

<div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 6000)" x-transition.opacity role="{{ $type === 'danger' ? 'alert' : 'status' }}" aria-live="{{ $type === 'danger' ? 'assertive' : 'polite' }}" class="fixed inset-x-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-50 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-card sm:bottom-auto sm:left-auto sm:right-4 sm:top-20 sm:max-w-sm {{ $tone }}">
    <p class="flex-1">{{ $slot }}</p>
    <button type="button" class="min-h-11 min-w-11 shrink-0 rounded-lg" aria-label="Tutup pemberitahuan" @click="visible = false">
        @if (request()->routeIs('user.*'))
            Tutup
        @else
            <x-icon name="tutup" class="mx-auto h-5 w-5" />
        @endif
    </button>
</div>
