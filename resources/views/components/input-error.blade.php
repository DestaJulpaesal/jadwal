@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'field-error space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5">
                <x-icon name="galat" class="mt-0.5 h-4 w-4" />
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
