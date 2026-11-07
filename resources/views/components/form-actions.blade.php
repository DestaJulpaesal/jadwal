@props(['cancelUrl', 'cancelLabel' => 'Batal', 'submitLabel' => 'Simpan', 'submitIcon' => 'selesai'])

<div class="sticky bottom-0 z-10 -mx-4 mt-6 flex flex-col-reverse gap-2 border-t border-line bg-white/95 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] backdrop-blur sm:mx-0 sm:flex-row sm:justify-end sm:rounded-xl sm:border-0 sm:bg-transparent sm:p-0">
    <x-button :href="$cancelUrl" variant="secondary" icon="kembali" class="w-full sm:w-auto">{{ $cancelLabel }}</x-button>
    <x-button type="submit" variant="primary" :icon="$submitIcon" class="w-full sm:w-auto">{{ $submitLabel }}</x-button>
</div>
