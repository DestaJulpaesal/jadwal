@props(['id', 'name', 'label', 'help' => null, 'error' => null, 'rows' => 4, 'maxlength' => null, 'value' => null, 'required' => false, 'optional' => false])

<x-field :id="$id" :label="$label" :help="$help" :error="$error" :required="$required" :optional="$optional">
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        data-autoresize
        data-character-count-for="{{ $maxlength ? $id : '' }}"
        {{ $attributes->merge(['class' => 'form-control']) }}
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        @if ($help || $error) aria-describedby="{{ collect([$help ? $id.'-help' : null, $error ? $id.'-error' : null])->filter()->implode(' ') }}" @endif>{{ old($name, $value) }}</textarea>
    @if ($maxlength)
        <p class="mt-1 text-right text-xs text-muted" data-character-count-for="{{ $id }}">{{ strlen(old($name, $value ?? '')) }}/{{ $maxlength }}</p>
    @endif
</x-field>
