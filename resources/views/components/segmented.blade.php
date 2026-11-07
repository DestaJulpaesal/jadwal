@props(['name', 'label', 'options', 'value' => null, 'help' => null, 'error' => null, 'required' => false, 'optional' => false])

<fieldset class="space-y-2" @if ($error) aria-describedby="{{ $name }}-error" @endif>
    <legend class="field-label">
        {{ $label }}
        @if ($required)
            <span class="font-normal text-muted">(wajib)</span>
        @elseif ($optional)
            <span class="font-normal text-muted">(opsional)</span>
        @endif
    </legend>
    @if ($help)
        <p class="field-help">{{ $help }}</p>
    @endif
    <div class="grid grid-cols-2 gap-2 sm:flex" role="radiogroup" aria-label="{{ $label }}">
        @foreach ($options as $optionValue => $optionLabel)
            <label class="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-lg border border-line bg-white px-3 text-sm font-medium has-[:checked]:border-primary-700 has-[:checked]:bg-primary-50">
                <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" class="sr-only"
                    @if ($required) required @endif
                    @checked((string) old($name, $value) === (string) $optionValue)>
                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
    @if ($error)
        <p id="{{ $name }}-error" class="field-error" role="alert">{{ $error }}</p>
    @endif
</fieldset>
