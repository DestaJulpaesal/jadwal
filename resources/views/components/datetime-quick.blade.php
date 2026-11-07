@props([
    'id',
    'name',
    'label',
    'type' => 'datetime-local',
    'value' => null,
    'help' => null,
    'shortcuts' => ['Hari ini', 'Besok', 'Akhir pekan', 'Minggu depan'],
])

<x-field :id="$id" :label="$label" :help="$help">
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
        {{ $attributes->merge(['class' => 'form-control']) }}>
    <div class="mt-2 flex flex-wrap gap-2" aria-label="Pintasan tanggal">
        @foreach ($shortcuts as $shortcut)
            <x-button type="button" size="sm" variant="secondary" data-datetime-shortcut="{{ \Illuminate\Support\Str::slug($shortcut) }}" data-target="{{ $id }}">
                {{ $shortcut }}
            </x-button>
        @endforeach
    </div>
</x-field>
