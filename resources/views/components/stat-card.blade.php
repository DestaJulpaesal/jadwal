@props(['label', 'value', 'description' => null, 'icon' => null])

<article {{ $attributes->class(['surface-card p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm text-muted">{{ $label }}</p>
            <p class="mt-1 text-xl font-semibold text-ink">{{ $value }}</p>
        </div>
        @if ($icon)
            <x-icon :name="$icon" class="h-6 w-6 shrink-0 text-primary-700" />
        @endif
    </div>
    @if ($description)
        <p class="mt-2 text-sm text-muted">{{ $description }}</p>
    @endif
</article>
