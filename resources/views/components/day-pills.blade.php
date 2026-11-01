@props(['name', 'days', 'selected' => []])

<fieldset class="space-y-2">
    <legend class="field-label">{{ $slot }}</legend>
    <div class="flex flex-wrap gap-2">
        @foreach ($days as $value => $label)
            <label class="inline-flex min-h-11 cursor-pointer items-center justify-center">
                <input class="peer sr-only" type="checkbox" name="{{ $name }}[]" value="{{ $value }}" @checked(in_array($value, old($name, $selected), true))>
                <span class="flex h-11 min-w-11 items-center justify-center rounded-full border border-line px-3 text-sm font-medium text-muted peer-checked:border-primary-700 peer-checked:bg-primary-50 peer-checked:text-primary-900 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary-700">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</fieldset>
