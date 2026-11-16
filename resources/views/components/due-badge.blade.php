@props(['days'])

@php
    [$label, $tone] = match (true) {
        $days < 0 => [__('ui.agenda.late_days', ['days' => abs($days)]), 'border-red-200 bg-red-50 text-danger'],
        $days === 0 => [__('ui.agenda.due_today'), 'border-amber-200 bg-amber-50 text-warning'],
        $days === 1 => [__('ui.agenda.tomorrow'), 'border-amber-200 bg-amber-50 text-warning'],
        default => [__('ui.agenda.due_in_days', ['days' => $days]), 'border-line bg-white text-muted'],
    };
@endphp
<span {{ $attributes->class(['inline-flex min-h-7 items-center rounded-full border px-2.5 py-1 text-xs font-semibold', $tone]) }}>{{ $label }}</span>
