@props([
    'action',
    'method' => 'DELETE',
    'title',
    'message',
    'label',
    'tone' => 'danger',
    'icon' => null,
])

@php
    $dialogId = 'confirm-' . \Illuminate\Support\Str::uuid();
    $actionIcon = $icon ?? ($tone === 'danger' ? 'hapus' : 'nonaktifkan');
@endphp

<div>
    <button type="button" class="{{ $tone === 'danger' ? 'btn-danger' : 'btn-secondary' }} min-h-11 px-3 py-1.5 text-xs" x-data x-on:click="$dispatch('open-modal', '{{ $dialogId }}')">
        @if (! request()->routeIs('user.*'))
            <x-icon :name="$actionIcon" class="h-5 w-5" />
        @endif
        {{ $label }}
    </button>
    <x-modal :name="$dialogId" focusable>
        <section class="p-5 sm:p-6">
            <h2 class="text-lg font-semibold">{{ $title }}</h2>
            <p class="mt-2 text-sm leading-6 text-muted">{{ $message }}</p>
            <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    @if (strtoupper($method) !== 'POST')
                        @method($method)
                    @endif
                    <button type="submit" class="{{ $tone === 'danger' ? 'btn-danger' : 'btn-primary' }} w-full sm:w-auto">
                        @if (! request()->routeIs('user.*'))
                            <x-icon :name="$actionIcon" class="h-5 w-5" />
                        @endif
                        {{ $label }}
                    </button>
                </form>
            </div>
        </section>
    </x-modal>
</div>
