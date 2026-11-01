@props(['title', 'description' => null, 'eyebrow' => null])

<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if ($eyebrow)
            <p class="text-sm font-semibold text-primary-800">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ $title }}</h1>
        @if ($description)
            <p class="mt-2 text-muted">{{ $description }}</p>
        @endif
    </div>
    @if (trim($slot))
        <div class="flex flex-wrap gap-2">{{ $slot }}</div>
    @endif
</header>
