@props(['items', 'label'])

<nav {{ $attributes->merge(['class' => 'user-subnav']) }} aria-label="Sub-menu {{ $label }}" data-subnav>
    <div class="user-subnav-track">
        @foreach ($items as $item)
            @php($isActive = $item['active'] ?? (isset($item['active_pattern']) && request()->routeIs($item['active_pattern'])))
            <a href="{{ $item['url'] }}" class="user-subnav-link {{ $isActive ? 'user-subnav-link-active' : '' }}" @if ($isActive) aria-current="page" data-subnav-active @endif>
                @if (isset($item['concept']))
                    <x-concept-icon :name="$item['concept']" class="h-5 w-5 shrink-0" />
                @elseif (isset($item['icon']))
                    <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                @endif
                <span>{{ $item['label'] }}</span>
                @if (! empty($item['badge']))
                    <span class="user-subnav-badge">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </div>
</nav>
