@props([
    'id',
    'name',
    'label',
    'type' => 'datetime-local',
    'value' => null,
    'help' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'timeShortcuts' => [],
    'durationStart' => null,
    'durationEnd' => null,
    'shortcuts' => ['Hari ini', 'Besok', 'Akhir pekan', 'Minggu depan'],
])

<x-field :id="$id" :label="$label" :help="$help" :error="$error" :required="$required" :optional="$optional">
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @if ($required) required @endif autocomplete="off"
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        @if ($help || $error) aria-describedby="{{ collect([$help ? $id.'-help' : null, $error ? $id.'-error' : null])->filter()->implode(' ') }}" @endif
        {{ $attributes->merge(['class' => 'form-control']) }}>
    <div class="mt-2 flex flex-wrap gap-2" aria-label="Pintasan tanggal dan jam">
        @foreach ($shortcuts as $shortcut)
            <x-button type="button" size="sm" variant="secondary" data-datetime-shortcut="{{ \Illuminate\Support\Str::slug($shortcut) }}" data-target="{{ $id }}">
                {{ $shortcut }}
            </x-button>
        @endforeach
        @foreach ($timeShortcuts as $time => $shortcut)
            <x-button type="button" size="sm" variant="secondary" data-time-shortcut="{{ $time }}" data-target="{{ $id }}">
                {{ $shortcut }}
            </x-button>
        @endforeach
        @if ($durationStart && $durationEnd)
            @foreach ([30 => '30 mnt', 60 => '1 jam', 120 => '2 jam'] as $minutes => $shortcut)
                <x-button type="button" size="sm" variant="secondary" data-duration-minutes="{{ $minutes }}" data-start-target="{{ $durationStart }}" data-end-target="{{ $durationEnd }}">
                    {{ $shortcut }}
                </x-button>
            @endforeach
        @endif
    </div>
</x-field>
