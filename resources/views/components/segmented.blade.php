@props(['name', 'label', 'options', 'value' => null, 'help' => null])

<fieldset class="space-y-2">
    <legend class="field-label">{{ $label }}</legend>
    @if ($help)
        <p class="field-help">{{ $help }}</p>
    @endif
    <div class="grid grid-cols-2 gap-2 sm:flex" role="radiogroup" aria-label="{{ $label }}">
        @foreach ($options as $optionValue => $optionLabel)
            <label class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border border-line bg-white px-3 text-sm font-medium has-[:checked]:border-primary-700 has-[:checked]:bg-primary-50">
                <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" class="sr-only"
                    @checked((string) old($name, $value) === (string) $optionValue)>
                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
</fieldset>
