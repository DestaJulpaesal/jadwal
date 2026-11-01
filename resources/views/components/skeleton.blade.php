@props(['width' => 'w-full', 'height' => 'h-4'])

<span aria-hidden="true" {{ $attributes->class(['block animate-pulse rounded-md bg-gray-200', $width, $height]) }}></span>
