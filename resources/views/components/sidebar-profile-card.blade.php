@props(['user'])

<div>
    <a href="{{ route('profile.edit') }}" class="sidebar-profile-card w-full text-left" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
        <x-profile-avatar :user="$user" class="h-11 w-11 shrink-0" />
        <span class="sidebar-profile-copy">
            <span class="sidebar-profile-name">{{ $user->name }}</span>
            <span class="sidebar-profile-email">{{ $user->email }}</span>
        </span>
        <x-concept-icon name="pr" class="h-6 w-6 shrink-0" />
    </a>
</div>
