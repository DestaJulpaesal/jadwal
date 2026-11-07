@props(['id', 'label', 'help' => null, 'error' => null])

<x-field :id="$id" :label="$label" :help="$help" :error="$error">
    <select id="{{ $id }}" {{ $attributes->merge(['class' => 'form-control']) }}
        aria-invalid="{{ $error ? 'true' : 'false' }}"
        @if ($help || $error) aria-describedby="{{ collect([$help ? $id.'-help' : null, $error ? $id.'-error' : null])->filter()->implode(' ') }}" @endif>
        {{ $slot }}
    </select>
</x-field>
