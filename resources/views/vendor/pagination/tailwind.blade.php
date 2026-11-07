@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-muted">
            Menampilkan <span class="font-semibold text-ink">{{ $paginator->firstItem() ?? 0 }}</span>
            sampai <span class="font-semibold text-ink">{{ $paginator->lastItem() ?? 0 }}</span>
            dari <span class="font-semibold text-ink">{{ $paginator->total() }}</span> data
        </p>
        <div class="flex items-center justify-between gap-1">
            @if ($paginator->onFirstPage())
                <span class="button button--secondary button--md min-h-11 cursor-not-allowed px-3 opacity-50" aria-disabled="true">Sebelumnya</span>
            @else
                <x-button href="{{ $paginator->previousPageUrl() }}" rel="prev" variant="secondary" icon="sebelumnya">Sebelumnya</x-button>
            @endif
            <div class="hidden items-center gap-1 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-2 text-muted" aria-hidden="true">{{ $element }}</span>
                    @elseif (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex h-11 min-w-11 items-center justify-center rounded-lg bg-primary-700 px-3 text-sm font-semibold text-white" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}" class="inline-flex h-11 min-w-11 items-center justify-center rounded-lg px-3 text-sm font-medium text-muted hover:bg-primary-50 hover:text-primary-900">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>
            @if ($paginator->hasMorePages())
                <x-button href="{{ $paginator->nextPageUrl() }}" rel="next" variant="secondary" icon="berikutnya">Berikutnya</x-button>
            @else
                <span class="button button--secondary button--md min-h-11 cursor-not-allowed px-3 opacity-50" aria-disabled="true">Berikutnya</span>
            @endif
        </div>
    </nav>
@endif
