@props(['name', 'class' => 'h-6 w-6'])

@php
    $symbols = ['tk', 'fl', 'wl', 'cl', 'bl', 'home', 'bk', 'rc', 'us', 'ey', 'dt', 'sc', 'ml', 'lk', 'fp', 'mascot', 'spark'];

    if (! in_array($name, $symbols, true)) {
        throw new InvalidArgumentException("Ikon konsep [{$name}] tidak terdaftar.");
    }
@endphp

<svg {{ $attributes->class([$class, 'concept-icon']) }} aria-hidden="true" focusable="false" viewBox="0 0 48 48">
    <use href="#{{ $name }}"></use>
</svg>
