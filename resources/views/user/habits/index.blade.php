@extends('layouts.user')

@section('title', 'Kebiasaan')

@section('content')
    <div class="mx-auto max-w-5xl space-y-5">
        <header class="concept-card concept-card-blue page-heading mb-0">
            <div>
                <p class="k">Ritme harian</p>
                <h1 class="mt-1 text-3xl">Kebiasaan</h1>
                <p class="mt-2 text-sm">{{ \Illuminate\Support\Carbon::parse($today)->translatedFormat('l, j F Y') }}</p>
            </div>
            <a href="{{ route('user.habits.create') }}" class="btn-primary"><x-concept-icon name="spark" class="h-5 w-5" />Tambah</a>
        </header>
        <div class="habit-stats habit-overview">
            <article class="habit-stat"><b>{{ $habits->count() }}</b><span>Kebiasaan aktif</span></article>
            <article class="habit-stat"><b>{{ $habits->filter(fn ($habit) => $habit->today_log?->status->value === 'done')->count() }}</b><span>Selesai hari ini</span></article>
        </div>
        <div class="space-y-4">
            @forelse ($habits as $habit)
                <article class="concept-card habit-card">
                    <div class="habit-card-main">
                        <p class="k">{{ $habit->frequency->label() }}</p>
                        <h2 class="text-lg font-semibold">{{ $habit->title }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ $habit->statistics['completion_percent'] }}% selesai · 30 hari terakhir</p>
                        <div class="concept-meter" role="progressbar" aria-label="Penyelesaian 30 hari terakhir" aria-valuenow="{{ $habit->statistics['completion_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                            <span style="width: {{ min(100, max(0, $habit->statistics['completion_percent'])) }}%"></span>
                        </div>
                        <div class="habit-stats mt-3">
                            <div class="habit-stat"><b>{{ $habit->statistics['current'] }}</b><span>Hari beruntun</span></div>
                            <div class="habit-stat"><b>{{ $habit->statistics['longest'] }}</b><span>Rekor beruntun</span></div>
                            @if ($habit->goal_value)
                                <div class="habit-stat"><b>{{ $habit->goal_value }}</b><span>Target {{ $habit->goal_unit }}</span></div>
                            @endif
                        </div>
                        @if ($habit->description)<p class="mt-2 text-sm">{{ $habit->description }}</p>@endif
                    </div>
                    <div class="habit-card-actions">
                        <a class="btn-secondary" href="{{ route('user.habits.edit', $habit) }}">Ubah</a>
                        @if ($habit->today_log?->status->value === 'done')
                            <form method="POST" action="{{ route('user.habits.check-in.cancel', $habit) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="log_date" value="{{ $today }}">
                                <button class="btn-secondary">Batalkan</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('user.habits.check-in', $habit) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <input type="hidden" name="log_date" value="{{ $today }}">
                                <input type="hidden" name="status" value="done">
                                @if ($habit->goal_value)<input type="hidden" name="value" value="{{ $habit->goal_value }}">@endif
                                <button class="btn-primary">Tandai selesai</button>
                                <button name="status" value="skipped" class="btn-secondary">Libur hari ini</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('user.habits.archive', $habit) }}" onsubmit="return confirm('Arsipkan kebiasaan ini?')">
                            @csrf
                            @method('PATCH')
                            <button class="btn-secondary">Arsipkan</button>
                        </form>
                    </div>
                    <details class="mt-4 border-t-2 border-dashed border-ink pt-3">
                        <summary class="min-h-11 cursor-pointer font-medium text-primary-800">Catatan 7 hari terakhir</summary>
                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @for ($offset = 7; $offset >= 0; $offset--)
                                @php
                                    $logDate = \Illuminate\Support\Carbon::parse($today)->subDays($offset)->toDateString();
                                    $log = $habit->logs->first(fn ($entry) => $entry->log_date->toDateString() === $logDate);
                                    $scheduled = $habit->frequency->value !== 'weekdays'
                                        || in_array(\Illuminate\Support\Carbon::parse($logDate)->dayOfWeekIso, array_map('intval', $habit->weekdays ?? []), true);
                                @endphp
                                @if ($logDate >= $habit->starts_on->toDateString() && $scheduled)
                                    <div class="rounded-xl border-2 border-ink bg-white p-2 text-sm shadow-[2px_2px_0_var(--ink)]">
                                        <p class="font-medium">{{ \Illuminate\Support\Carbon::parse($logDate)->translatedFormat('D, j M') }}</p>
                                        @if ($log)
                                            <p class="mt-1 text-muted">{{ $log->status->label() }}</p>
                                            <form method="POST" action="{{ route('user.habits.check-in.cancel', $habit) }}">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="log_date" value="{{ $logDate }}">
                                                <button class="mt-1 min-h-11 font-medium text-primary-800 underline">Batalkan</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('user.habits.check-in', $habit) }}">
                                                @csrf
                                                <input type="hidden" name="log_date" value="{{ $logDate }}">
                                                <input type="hidden" name="status" value="done">
                                                @if ($habit->goal_value)<input type="hidden" name="value" value="{{ $habit->goal_value }}">@endif
                                                <button class="mt-1 min-h-11 font-medium text-primary-800 underline">Tandai selesai</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            @endfor
                        </div>
                    </details>
                </article>
            @empty
                <section class="concept-card concept-card-yellow px-5 py-12 text-center">
                    <h2 class="text-lg font-semibold">Belum ada kebiasaan</h2>
                    <p class="mt-2 text-sm text-muted">Mulai dengan satu kebiasaan kecil yang ingin dijaga.</p>
                    <a href="{{ route('user.habits.create', ['preset' => 0]) }}" class="btn-primary mt-5">Mulai belajar 30 menit</a>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @foreach (config('presets.habits') as $index => $preset)
                            @if ($index > 0)<a href="{{ route('user.habits.create', ['preset' => $index]) }}" class="btn-secondary">{{ $preset['title'] }}</a>@endif
                        @endforeach
                    </div>
                </section>
            @endforelse
        </div>
    </div>
@endsection
