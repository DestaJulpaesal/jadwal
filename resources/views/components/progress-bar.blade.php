@props(['value', 'label', 'description' => null])

@php($percentage = max(0, min(100, (int) round($value))))
<div {{ $attributes }}>
    <div class="mb-1 flex items-center justify-between gap-3 text-sm">
        <span class="font-medium">{{ $label }}</span>
        <span>{{ $percentage }}%</span>
    </div>
    <div class="h-2.5 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentage }}">
        <div class="h-full rounded-full bg-primary-700 transition-[width]" style="width: {{ $percentage }}%"></div>
    </div>
    @if ($description)
        <p class="mt-1 text-sm text-muted">{{ $description }}</p>
    @endif
</div>
