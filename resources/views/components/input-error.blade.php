@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'field-error space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-start gap-1.5">
                @if (! request()->routeIs('user.*'))
                    <x-icon name="galat" class="mt-0.5 h-4 w-4" />
                @endif
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
