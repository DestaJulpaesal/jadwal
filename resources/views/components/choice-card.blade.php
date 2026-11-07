@props(['name', 'options', 'value' => null, 'type' => 'radio'])

<div class="grid gap-2 sm:grid-cols-2">
    @foreach ($options as $optionValue => $option)
        @php
            $optionLabel = is_array($option) ? $option['label'] : $option;
            $description = is_array($option) ? ($option['description'] ?? null) : null;
            $icon = is_array($option) ? ($option['icon'] ?? null) : null;
        @endphp
        <label class="flex min-h-16 cursor-pointer items-start gap-3 rounded-xl border border-line bg-white p-3 has-[:checked]:border-primary-700 has-[:checked]:bg-primary-50">
            <input type="{{ $type }}" name="{{ $name }}" value="{{ $optionValue }}" class="mt-1 h-5 w-5 shrink-0"
                @checked((string) old($name, $value) === (string) $optionValue)>
            @if ($icon)
                <x-icon :name="$icon" class="mt-0.5 h-5 w-5" />
            @endif
            <span class="min-w-0">
                <span class="block font-semibold">{{ $optionLabel }}</span>
                @if ($description)
                    <span class="mt-1 block text-sm text-muted">{{ $description }}</span>
                @endif
            </span>
        </label>
    @endforeach
</div>
