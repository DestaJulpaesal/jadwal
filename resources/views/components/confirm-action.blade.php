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
    <x-button :variant="$tone === 'danger' ? 'danger' : 'secondary'" size="sm" :icon="$actionIcon" x-data x-on:click="$dispatch('open-modal', '{{ $dialogId }}')">
        {{ $label }}
    </x-button>
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
                    <x-button type="submit" :variant="$tone === 'danger' ? 'danger' : 'primary'" :icon="$actionIcon" class="w-full sm:w-auto">
                        {{ $label }}
                    </x-button>
                </form>
            </div>
        </section>
    </x-modal>
</div>
