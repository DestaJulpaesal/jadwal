@props(['id', 'name', 'label', 'value' => '', 'help' => null, 'error' => null])

<x-field :id="$id" :label="$label" :help="$help" :error="$error">
    <div x-data="rupiahInput(@js(old($name, $value)))" class="mt-1">
        <div class="flex min-h-11 items-center rounded-lg border border-line bg-white focus-within:border-primary-600 focus-within:ring-1 focus-within:ring-primary-600">
            <span class="pl-3 text-sm font-semibold text-muted">Rp</span>
            <input id="{{ $id }}" name="{{ $name }}" type="text" inputmode="numeric" autocomplete="off" x-model="display" x-on:input="format" required class="min-w-0 flex-1 border-0 bg-transparent text-ink focus:ring-0" aria-invalid="{{ $error ? 'true' : 'false' }}" aria-describedby="{{ $id }}-preview {{ $id }}-help {{ $id }}-error">
        </div>
        <p id="{{ $id }}-preview" class="field-help" x-text="preview"></p>
    </div>
</x-field>
