@props(['cancelUrl', 'cancelLabel' => 'Batal', 'submitLabel' => 'Simpan', 'submitIcon' => 'selesai', 'loadingLabel' => 'Menyimpan…'])

<div class="form-actions-bar sticky -mx-5 mt-6 flex flex-col-reverse gap-2 border-t border-line bg-surface/95 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] backdrop-blur sm:mx-0 sm:flex-row sm:justify-end sm:rounded-xl sm:border-0 sm:bg-transparent sm:p-0">
    <x-button :href="$cancelUrl" variant="secondary" icon="kembali" data-cancel-form class="w-full sm:w-auto">{{ $cancelLabel }}</x-button>
    <x-button type="submit" variant="primary" :icon="$submitIcon" :loading-text="$loadingLabel" class="w-full sm:w-auto">{{ $submitLabel }}</x-button>
</div>
