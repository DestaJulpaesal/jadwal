@props(['id', 'label', 'help' => null, 'error' => null, 'required' => false, 'optional' => false])

<x-field :id="$id" :label="$label" :help="$help" :error="$error" :required="$required" :optional="$optional">
    <select id="{{ $id }}" @if ($required) required @endif {{ $attributes->merge(['class' => 'form-control']) }}
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        @if ($help || $error) aria-describedby="{{ collect([$help ? $id.'-help' : null, $error ? $id.'-error' : null])->filter()->implode(' ') }}" @endif>
        {{ $slot }}
    </select>
</x-field>
