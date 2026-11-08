@extends('layouts.user')

@section('title', 'Kebiasaan')

@section('content')
    <div class="habits-page mx-auto max-w-5xl space-y-5">
        <header class="concept-card concept-card-blue page-heading habit-hero mb-0">
            <div class="habit-page-heading">
                <span class="habit-heading-icon" aria-hidden="true"><x-concept-icon name="fl" class="h-10 w-10" /></span>
                <div class="habit-hero-copy">
                    <p class="k">Ritme harian</p>
                    <h1 class="mt-1 text-3xl">Kebiasaan</h1>
                    <p class="mt-2 text-sm">Bangun ritme kecil, rasakan perubahan besar.</p>
                    <span class="habit-date-pill">{{ \Illuminate\Support\Carbon::parse($today)->translatedFormat('l, j F Y') }}</span>
                </div>
            </div>
            <x-button href="{{ route('user.habits.create') }}" variant="primary" class="habit-add-button"><x-concept-icon name="spark" class="h-5 w-5" />Tambah kebiasaan</x-button>
        </header>
        <div class="habit-stats habit-overview">
            <article class="habit-stat habit-stat-blue">
                <span class="habit-stat-icon" aria-hidden="true"><x-concept-icon name="fl" class="h-5 w-5" /></span>
                <div class="habit-stat-content"><b>{{ $habits->count() }}</b><span>Kebiasaan aktif</span></div>
            </article>
            <article class="habit-stat habit-stat-green">
                <span class="habit-stat-icon" aria-hidden="true"><x-concept-icon name="spark" class="h-5 w-5" /></span>
                <div class="habit-stat-content"><b>{{ $habits->filter(fn ($habit) => $habit->today_log?->status->value === 'done')->count() }}</b><span>Selesai hari ini</span></div>
            </article>
        </div>
        <div class="habit-list-heading">
            <div>
                <p class="k">Jaga konsistensi</p>
                <h2>Rutinitasmu</h2>
            </div>
            <span class="habit-count-pill">{{ $habits->count() }} kebiasaan</span>
        </div>
        <div class="space-y-4">
            @forelse ($habits as $habit)
                <article class="concept-card habit-card {{ $habit->today_log?->status->value === 'done' ? 'habit-card-done' : '' }}">
                    <span class="habit-card-accent" aria-hidden="true"></span>
                    <div class="habit-card-main">
                        <div class="habit-card-heading">
                            <div>
                                <p class="k">{{ $habit->frequency->label() }}</p>
                                <h2 class="text-lg font-semibold">{{ $habit->title }}</h2>
                            </div>
                            @if ($habit->today_log?->status->value === 'done')
                                <span data-habit-status class="habit-status habit-status-done"><x-concept-icon name="spark" class="h-4 w-4" />Selesai hari ini</span>
                            @elseif ($habit->today_log?->status->value === 'skipped')
                                <span data-habit-status class="habit-status habit-status-skipped">Libur hari ini</span>
                            @else
                                <span data-habit-status class="habit-status habit-status-pending">Belum dicatat</span>
                            @endif
                        </div>
                        @if ($habit->today_log?->status->value === 'done' && ($habit->today_log->duration_minutes || $habit->today_log->performed_at || $habit->today_log->value))
                            <div class="habit-activity-summary">
                                @if ($habit->today_log->duration_minutes)<span>{{ $habit->today_log->duration_minutes }} menit</span>@endif
                                @if ($habit->today_log->performed_at)<span>mulai {{ $habit->today_log->performed_at }}</span>@endif
                                @if ($habit->today_log->value)<span>{{ $habit->today_log->value }} {{ $habit->goal_unit }}</span>@endif
                            </div>
                        @endif
                        <div class="habit-progress-label">
                            <p class="text-sm text-muted">{{ $habit->statistics['completion_percent'] }}% selesai</p>
                            <span>30 hari terakhir</span>
                        </div>
                        <div class="concept-meter" role="progressbar" aria-label="Penyelesaian 30 hari terakhir" aria-valuenow="{{ $habit->statistics['completion_percent'] }}" aria-valuemin="0" aria-valuemax="100">
                            <span style="width: {{ min(100, max(0, $habit->statistics['completion_percent'])) }}%"></span>
                        </div>
                        <div class="habit-stats habit-metrics mt-3">
                            <div class="habit-stat"><b>{{ $habit->statistics['current'] }}</b><span>Hari beruntun</span></div>
                            <div class="habit-stat"><b>{{ $habit->statistics['longest'] }}</b><span>Rekor terbaik</span></div>
                            @if ($habit->goal_value)
                                <div class="habit-stat"><b>{{ $habit->goal_value }}</b><span>Target {{ $habit->goal_unit }}</span></div>
                            @endif
                        </div>
                        @if ($habit->description)<p class="mt-2 text-sm">{{ $habit->description }}</p>@endif
                    </div>
                    <div class="habit-card-actions" aria-label="Aksi {{ $habit->title }}">
                        <div class="habit-actions-heading">
                            <strong>Aksi kebiasaan</strong>
                            <span>Catat aktivitas hari ini atau kelola kebiasaan.</span>
                        </div>
                        <x-button href="{{ route('user.habits.edit', $habit) }}" variant="secondary" class="habit-edit-button">Edit kebiasaan</x-button>
                        @if ($habit->today_log?->status->value === 'done')
                            <form method="POST" action="{{ route('user.habits.check-in.cancel', $habit) }}" data-optimistic-action="cancel-habit-checkin">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="log_date" value="{{ $today }}">
                                <x-button variant="secondary" class="habit-main-action">Batalkan catatan hari ini</x-button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('user.habits.check-in', $habit) }}" class="flex flex-wrap items-center gap-2" data-optimistic-action="habit-checkin">
                                @csrf
                                <input type="hidden" name="log_date" value="{{ $today }}">
                                <input type="hidden" name="status" value="done">
                                <details class="habit-checkin-details">
                                    <summary>
                                        <span class="habit-checkin-summary-copy">
                                            <strong>Tambahkan detail aktivitas</strong>
                                            <small>Opsional: durasi, jam mulai, dan hasil</small>
                                        </span>
                                        <span class="habit-checkin-summary-icon">+</span>
                                    </summary>
                                    <div class="habit-checkin-fields">
                                        <label><span>Durasi (menit)</span><input name="duration_minutes" type="number" min="1" max="1440" placeholder="30"></label>
                                        <label><span>Mulai jam</span><input name="performed_at" type="time" value="{{ $habit->schedule_times[\Illuminate\Support\Carbon::parse($today)->dayOfWeekIso] ?? $habit->preferred_time }}"></label>
                                        <label class="habit-checkin-wide"><span>{{ $habit->goal_unit ? 'Hasil ('.$habit->goal_unit.')' : 'Hasil / catatan singkat' }}</span><input name="value" type="number" min="0" max="1000000" value="{{ $habit->goal_value }}" placeholder="Opsional"></label>
                                    </div>
                                </details>
                                <x-button variant="primary" class="habit-main-action">Selesai hari ini</x-button>
                                <x-button name="status" value="skipped" variant="secondary">Lewati hari ini</x-button>
                            </form>
                        @endif
                        <x-confirm-action :action="route('user.habits.archive', $habit)" method="PATCH"
                            title="Arsipkan kebiasaan?"
                            :message="'Kebiasaan '.$habit->title.' akan disembunyikan dari daftar aktif. Riwayat aktivitasnya tetap tersimpan.'"
                            label="Arsipkan kebiasaan" tone="secondary" icon="nonaktifkan" />
                    </div>
                    <details class="habit-history">
                        <summary class="min-h-11 cursor-pointer font-medium text-primary-800"><span>Catatan 7 hari terakhir</span><span class="habit-history-chevron" aria-hidden="true">+</span></summary>
                        <div class="habit-history-grid mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @for ($offset = 7; $offset >= 0; $offset--)
                                @php
                                    $logDate = \Illuminate\Support\Carbon::parse($today)->subDays($offset)->toDateString();
                                    $log = $habit->logs->first(fn ($entry) => $entry->log_date->toDateString() === $logDate);
                                    $scheduled = $habit->frequency->value !== 'weekdays'
                                        || in_array(\Illuminate\Support\Carbon::parse($logDate)->dayOfWeekIso, array_map('intval', $habit->weekdays ?? []), true);
                                @endphp
                                @if ($logDate >= $habit->starts_on->toDateString() && $scheduled)
                                    <div class="habit-day rounded-xl border-2 border-ink bg-white p-2 text-sm shadow-[2px_2px_0_var(--ink)]">
                                        <p class="font-medium">{{ \Illuminate\Support\Carbon::parse($logDate)->translatedFormat('D, j M') }}</p>
                                        @if ($log)
                                            <p class="mt-1 text-muted">{{ $log->status->label() }}</p>
                                            @if ($log->duration_minutes || $log->performed_at)
                                                <p class="mt-1 text-xs text-muted">@if ($log->duration_minutes){{ $log->duration_minutes }} menit @endif @if ($log->performed_at)· {{ $log->performed_at }}@endif</p>
                                            @endif
                                            <form method="POST" action="{{ route('user.habits.check-in.cancel', $habit) }}" data-optimistic-action="cancel-habit-checkin">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="log_date" value="{{ $logDate }}">
                                                <x-button class="mt-1 min-h-11 font-medium text-primary-800 underline">Batalkan</x-button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('user.habits.check-in', $habit) }}" data-optimistic-action="habit-checkin">
                                                @csrf
                                                <input type="hidden" name="log_date" value="{{ $logDate }}">
                                                <input type="hidden" name="status" value="done">
                                                @if ($habit->goal_value)<input type="hidden" name="value" value="{{ $habit->goal_value }}">@endif
                                                <x-button class="mt-1 min-h-11 font-medium text-primary-800 underline">Tandai selesai</x-button>
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
                    <span class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white"><x-concept-icon name="fl" class="h-7 w-7" /></span>
                    <h2 class="text-lg font-semibold">Belum ada kebiasaan</h2>
                    <p class="mt-2 text-sm text-muted">Mulai dengan satu kebiasaan kecil yang ingin dijaga.</p>
                    <x-button href="{{ route('user.habits.create', ['preset' => 0]) }}" variant="primary" icon="tambah" class="mt-5">Mulai kebiasaan 30 menit</x-button>
                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        @foreach (config('presets.habits') as $index => $preset)
                            @if ($index > 0)<x-button href="{{ route('user.habits.create', ['preset' => $index]) }}" variant="secondary" icon="tambah">{{ $preset['title'] }}</x-button>@endif
                        @endforeach
                    </div>
                </section>
            @endforelse
        </div>
    </div>
@endsection
