@props(['user', 'class' => 'h-12 w-12'])

@php
    $nameParts = preg_split('/\s+/', trim($user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $initials = collect(array_slice($nameParts, 0, 2))
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class([$class, 'profile-avatar']) }} aria-hidden="true">
    @if ($user->profile_photo_path)
        <img src="{{ route('profile.photo', ['v' => $user->updated_at?->timestamp]) }}" alt="" loading="lazy">
    @else
        <span>{{ $initials }}</span>
    @endif
</span>
