@props(['title', 'description' => null])

<fieldset class="space-y-4">
    <legend class="text-lg font-semibold">{{ $title }}</legend>
    @if ($description)
        <p class="-mt-2 text-sm text-muted">{{ $description }}</p>
    @endif
    {{ $slot }}
</fieldset>
