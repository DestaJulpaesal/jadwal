@props(['tabs', 'active' => null])

<nav {{ $attributes->merge(['class' => 'flex gap-2 overflow-x-auto border-b border-line']) }} aria-label="Navigasi bagian">
    @foreach ($tabs as $tab)
        @php($isActive = isset($tab['key']) ? $active === $tab['key'] : ($active ?? request()->url()) === ($tab['url'] ?? null))
        <a href="{{ $tab['url'] }}" class="inline-flex min-h-11 shrink-0 items-center border-b-2 px-3 text-sm font-semibold {{ $isActive ? 'border-primary-700 text-primary-800' : 'border-transparent text-muted hover:text-ink' }}"
            @if ($isActive) aria-current="page" @endif>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
