@props(['id', 'label', 'help' => null, 'error' => null, 'required' => false, 'optional' => false])

<div>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}
        @if ($required)
            <span class="font-normal text-muted">(wajib)</span>
        @elseif ($optional)
            <span class="font-normal text-muted">(opsional)</span>
        @endif
    </label>
    {{ $slot }}
    @if ($help)
        <p id="{{ $id }}-help" class="field-help">{{ $help }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="field-error" role="alert">{{ $error }}</p>
    @endif
</div>
