@props(['tone' => 'neutral'])

@php
    $tones = [
        'neutral' => 'bg-surface-raised text-ink',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        'info' => 'bg-info-soft text-info',
    ];

    if (! array_key_exists($tone, $tones)) {
        throw new InvalidArgumentException("Warna lencana [{$tone}] tidak dikenal.");
    }
@endphp

<span {{ $attributes->class(['inline-flex min-h-6 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold', $tones[$tone]]) }}>
    {{ $slot }}
</span>
