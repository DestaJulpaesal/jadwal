@props(['name', 'title'])

<x-modal :name="$name" maxWidth="md" focusable>
    <section class="fixed inset-x-0 bottom-0 max-h-[85dvh] overflow-y-auto rounded-t-2xl border border-line bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-card sm:static sm:mx-auto sm:mt-[10vh] sm:rounded-2xl sm:p-6">
        <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-line sm:hidden" aria-hidden="true"></div>
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">{{ $title }}</h2>
            <button type="button" class="{{ request()->routeIs('user.*') ? 'btn-secondary' : 'btn-icon' }}" aria-label="Tutup" x-on:click="$dispatch('close')">
                @if (request()->routeIs('user.*'))
                    Tutup
                @else
                    <x-icon name="tutup" class="h-5 w-5" />
                @endif
            </button>
        </div>
        {{ $slot }}
    </section>
</x-modal>
