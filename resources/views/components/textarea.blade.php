@props(['id', 'name', 'label', 'help' => null, 'error' => null, 'rows' => 4, 'maxlength' => null, 'value' => null])

<x-field :id="$id" :label="$label" :help="$help" :error="$error">
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @if ($maxlength) maxlength="{{ $maxlength }}" @endif
        {{ $attributes->merge(['class' => 'form-control']) }}
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        @if ($help || $error) aria-describedby="{{ collect([$help ? $id.'-help' : null, $error ? $id.'-error' : null])->filter()->implode(' ') }}" @endif>{{ old($name, $value) }}</textarea>
    @if ($maxlength)
        <p class="mt-1 text-right text-xs text-muted" data-character-count-for="{{ $id }}">0/{{ $maxlength }}</p>
    @endif
</x-field>
