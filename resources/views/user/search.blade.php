@extends('layouts.user')

@section('title', 'Pencarian')

@section('content')
    <div class="mx-auto max-w-6xl space-y-5">
        <header>
            <p class="text-sm font-semibold text-primary-800">Pencarian pribadi</p>
            <h1 class="mt-1 text-2xl font-semibold">Hasil pencarian</h1>
            @if ($query !== '')
                <p class="mt-2 text-sm text-muted">Hasil untuk “{{ $query }}”.</p>
            @endif
        </header>

        @if ($searchErrors->has('q'))
            <x-alert type="danger">{{ $searchErrors->first('q') }}</x-alert>
        @endif

        @if ($query !== '' && ! $searchErrors->has('q'))
            @php($hasResults = $sections->contains(fn ($section) => $section['records']->isNotEmpty()))
            @if (! $hasResults)
                <section class="surface-card p-6 text-center">
                    <h2 class="text-lg font-semibold">Belum ada hasil yang cocok.</h2>
                    <p class="mt-2 text-sm text-muted">Coba kata kunci lain atau periksa kembali ejaannya.</p>
                </section>
            @else
                @foreach ($sections as $section)
                    @if ($section['records']->isNotEmpty())
                        <section class="surface-card global-search-section overflow-hidden">
                            <h2 class="flex items-center gap-2 border-b-2 border-line px-5 py-4 text-lg font-semibold">
                                <x-concept-icon :name="$section['icon']" class="h-6 w-6" />
                                {{ $section['title'] }}
                                <span class="ml-auto text-sm text-muted">{{ $section['records']->count() }}</span>
                            </h2>
                            <ul class="divide-y divide-line">
                                @foreach ($section['records'] as $record)
                                    <li>
                                        <a href="{{ $record['url'] }}" class="global-search-result flex min-h-16 items-center gap-3 px-5 py-3">
                                            <span class="min-w-0 flex-1">
                                                <span class="block break-words font-bold">{{ $record['title'] }}</span>
                                                <span class="mt-1 block break-words text-sm text-muted">{{ $record['description'] }}</span>
                                            </span>
                                            <span class="text-lg" aria-hidden="true">›</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                @endforeach
            @endif
        @endif
    </div>
@endsection
