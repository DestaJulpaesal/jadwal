@props(['title', 'description', 'actionUrl' => null, 'actionLabel' => null])

<div {{ $attributes->class(['px-5 py-10 text-center sm:px-8']) }}>
    @if (isset($icon))
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary-800">{{ $icon }}</div>
    @endif
    <h2 class="text-lg font-semibold">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted">{{ $description }}</p>
    @if ($actionUrl && $actionLabel)
        <x-button href="{{ $actionUrl }}" variant="primary" class="mt-5">{{ $actionLabel }}</x-button>
    @endif
    {{ $slot }}
</div>
