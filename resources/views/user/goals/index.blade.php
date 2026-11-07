@extends('layouts.user')

@section('title', 'Target')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold text-primary-800">Langkah demi langkah</p><h1 class="mt-1 text-2xl font-semibold">Target</h1></div>
            <x-button href="{{ route('user.goals.create') }}" variant="primary" icon="tambah">Buat target</x-button>
        </header>
        <section class="space-y-4">
            @forelse ($goals as $goal)
                <article class="surface-card p-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                        <div class="min-w-0 flex-1">
                            <h2 class="font-semibold">{{ $goal->title }}</h2>
                            @if($goal->description)<p class="mt-1 text-sm text-muted">{{ $goal->description }}</p>@endif
                            <p class="mt-2 text-sm text-muted">{{ $goal->current_value }} / {{ $goal->target_value }} {{ $goal->unit }}</p>
                            <x-progress-bar class="mt-2 max-w-xl" :value="$goal->progress_percent" :label="'Progres '.$goal->title" />
                        </div>
                        <div class="flex flex-wrap items-end gap-2">
                            <x-button href="{{ route('user.goals.edit', $goal) }}" variant="secondary">Ubah</x-button>
                            @if ($goal->type->value === 'number' && $goal->progress_percent < 100)
                                <form method="POST" action="{{ route('user.goals.updates.store', $goal) }}" class="flex flex-wrap items-end gap-2">
                                    @csrf
                                    <div><label class="field-label" for="progress-{{ $goal->id }}">Tambah progres</label><input id="progress-{{ $goal->id }}" name="amount" type="number" min="1" required class="form-control"></div>
                                    <input type="hidden" name="recorded_on" value="{{ $today }}">
                                    <x-button variant="primary">Catat</x-button>
                                </form>
                            @elseif ($goal->progress_percent >= 100)
                                <form method="POST" action="{{ route('user.goals.achieve', $goal) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-button variant="primary">Tandai tercapai</x-button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @if($goal->due_on)<p class="mt-3 text-xs text-muted">Target tanggal {{ $goal->due_on->translatedFormat('j F Y') }}</p>@endif
                    @if($goal->remaining_per_day)<p class="mt-2 text-sm text-muted">Sisa {{ rupiah(max(0, $goal->target_value - $goal->current_value)) }}, sekitar {{ rupiah($goal->remaining_per_day) }} per hari.</p>@endif
                </article>
            @empty
                <div class="surface-card p-8 text-center text-muted">Belum ada target aktif.</div>
            @endforelse
        </section>
    </div>
@endsection
