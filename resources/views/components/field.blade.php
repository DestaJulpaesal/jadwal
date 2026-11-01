@props(['id', 'label', 'help' => null, 'error' => null])

<div>
    <label for="{{ $id }}" class="field-label">{{ $label }}</label>
    {{ $slot }}
    @if ($help)
        <p id="{{ $id }}-help" class="field-help">{{ $help }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="field-error" role="alert">{{ $error }}</p>
    @endif
</div>
