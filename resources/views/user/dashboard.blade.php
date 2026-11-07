@extends('layouts.user')

@section('title', 'Beranda')

@section('content')
    <div class="dashboard-page mx-auto max-w-6xl">
        @php
            $todayCount = $todayTasks->count() + $todayHabits->count();
        @endphp
        <section class="concept-card concept-card-blue concept-hero dashboard-wide" aria-label="Ringkasan hari ini">
            <svg class="concept-hero-mascot stk bobf" viewBox="0 0 110 100" aria-hidden="true"><use href="#mascot" /></svg>
            <svg class="concept-hero-spark stk" viewBox="0 0 24 24" aria-hidden="true"><use href="#spark" /></svg>
            <p class="k">{{ now(auth()->user()->timezone)->translatedFormat('l, j F') }}</p>
            <h1 class="mt-1 break-words text-3xl sm:text-4xl">Halo,<br>{{ auth()->user()->name }}!</h1>
            <span class="concept-chip mt-2">{{ $todayCount }} hal hari ini</span>
            <p class="dashboard-hero-note">Pilih satu hal penting, lalu mulai dengan langkah kecil.</p>
            <div class="concept-stats {{ in_array('finance', $dashboardSections, true) ? '' : 'concept-stats-inline' }}">
                <div class="concept-stat"><b>{{ $totalTasks }}</b><span>Total tugas</span></div>
                <div class="concept-stat"><b>{{ $doneHabits }}/{{ $todayHabits->count() }}</b><span>kebiasaan</span></div>
                @if (in_array('finance', $dashboardSections, true))
                    <div class="concept-stat concept-stat-finance">
                        <b data-private>{{ rupiah($totalBalance) }}</b>
                        <div class="concept-stat-label">
                            <span>saldo</span>
                            <x-button type="button" class="privacy-toggle privacy-toggle-stat" data-privacy-toggle aria-label="Sembunyikan nominal" aria-pressed="false">
                                <x-concept-icon name="lk" />
                            </x-button>
                        </div>
                    </div>
                @endif
            </div>
        </section>

        @if (session('schedule_conflicts'))
            <x-alert type="warning" class="dashboard-wide mb-5">
                <p class="font-semibold">Tugas tersimpan, tetapi waktunya bertabrakan dengan jadwal berikut:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach (session('schedule_conflicts') as $conflict)
                        <li>{{ $conflict['title'] }} ({{ $conflict['starts_at'] }}–{{ $conflict['ends_at'] }})</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <nav class="concept-quick dashboard-wide" aria-label="Akses cepat">
            <a href="{{ route('user.tasks.index') }}" class="concept-quick-link"><x-concept-icon name="tk" /><strong>Tugas</strong><small>Atur pekerjaan hari ini</small></a>
            <a href="{{ route('user.money.index') }}" class="concept-quick-link"><x-concept-icon name="wl" /><strong>Catat uang</strong><small>Pantau pemasukan dan pengeluaran</small></a>
            <a href="{{ route('user.calendar') }}" class="concept-quick-link"><x-concept-icon name="cl" /><strong>Kalender</strong><small>Lihat jadwal mendatang</small></a>
            <a href="{{ route('user.habits.index') }}" class="concept-quick-link"><x-concept-icon name="fl" /><strong>Kebiasaan</strong><small>Jaga ritme setiap hari</small></a>
            <a href="{{ route('user.search') }}" class="concept-quick-link"><x-concept-icon name="sc" /><strong>Cari</strong><small>Temukan data dengan cepat</small></a>
        </nav>

        @if (in_array('attention', $dashboardSections, true))
        <section class="concept-card concept-card-pink dashboard-panel" aria-labelledby="attention-heading">
            <div class="concept-card-header">
                <div class="concept-heading mb-0"><x-concept-icon name="bl" /><h2 id="attention-heading">Perlu perhatian</h2></div>
                <p class="text-sm">Yang perlu dibereskan lebih dulu.</p>
            </div>
            @if ($overdueTasks->isEmpty() && $attentionItems->isEmpty())
                <p class="text-sm">Tidak ada yang perlu dikejar sekarang. Hari ini bisa dimulai dengan tenang.</p>
            @else
                <ul>
                    @foreach ($overdueTasks as $task)
                        <li class="concept-item flex-wrap">
                            <span class="min-w-0 flex-1 break-words"><b>{{ $task->title }}</b><span class="block text-sm">Tugas terlambat</span></span>
                            <a href="{{ route('user.tasks.edit', $task) }}" class="dashboard-action dashboard-action-secondary">Atur tugas</a>
                        </li>
                    @endforeach
                    @foreach ($attentionItems as $item)
                        <li class="concept-item flex-wrap">
                            <span class="min-w-0 flex-1 break-words"><b>{{ $item['title'] }}</b><span class="block text-sm">{{ $item['status'] }}</span></span>
                            <a href="{{ $item['url'] }}" class="dashboard-action dashboard-action-secondary">Lihat detail</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
        @endif

        @if (in_array('habits', $dashboardSections, true))
        <section class="concept-card concept-card-green dashboard-panel" aria-labelledby="habits-heading">
            <div class="concept-card-header">
                <div class="concept-heading mb-0"><x-concept-icon name="fl" /><div><h2 id="habits-heading" class="text-lg font-semibold">Kebiasaan hari ini</h2><p class="mt-1 text-sm">{{ $doneHabits }} dari {{ $todayHabits->count() }} selesai</p></div></div>
                <a href="{{ route('user.habits.index') }}" class="dashboard-action dashboard-action-secondary"><x-concept-icon name="fl" class="h-4 w-4" />Lihat semua</a>
            </div>
            @forelse ($todayHabits as $habit)
                <div class="concept-item flex-wrap">
                    <div class="min-w-0 flex-1"><p class="break-words font-medium">{{ $habit->title }}</p>@if($habit->goal_value)<p class="text-sm">Target {{ $habit->goal_value }} {{ $habit->goal_unit }}</p>@endif</div>
                    @if ($habit->today_log?->status === \App\Enums\HabitLogStatus::Done)
                        <form method="POST" action="{{ route('user.habits.check-in.cancel', $habit) }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="log_date" value="{{ $today }}">
                            <x-button class="dashboard-action dashboard-action-secondary">Batalkan catatan</x-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('user.habits.check-in', $habit) }}" class="flex shrink-0 gap-2">
                            @csrf
                            <input type="hidden" name="log_date" value="{{ $today }}">
                            <input type="hidden" name="status" value="done">
                            @if($habit->goal_value)<input type="hidden" name="value" value="{{ $habit->goal_value }}">@endif
                            <x-button class="dashboard-action dashboard-action-primary"><x-concept-icon name="spark" class="h-4 w-4" />Selesaikan hari ini</x-button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="border-t border-line py-4">
                    <p class="font-medium">Tidak apa-apa. Mulai lagi hari ini.</p>
                    <a href="{{ route('user.habits.create', ['preset' => 0]) }}" class="dashboard-action-link mt-2"><x-concept-icon name="fl" class="h-5 w-5" />Mulai kebiasaan pertama</a>
                </div>
            @endforelse
        </section>
        @endif

        @if (in_array('today_tasks', $dashboardSections, true))
        <section class="concept-card concept-card-yellow dashboard-panel">
            <div class="concept-card-header">
                <div class="concept-heading mb-0"><x-concept-icon name="cl" /><div><h2 class="text-lg font-semibold">Jadwal hari ini</h2><p class="mt-1 text-sm">Agenda yang sudah tiba atau akan dimulai hari ini.</p></div></div>
                <a href="{{ route('user.tasks.index', ['due' => 'today']) }}" class="dashboard-action dashboard-action-secondary"><x-concept-icon name="tk" class="h-4 w-4" />Lihat semua tugas</a>
            </div>
            @if ($todayTasks->isNotEmpty())
                @foreach ($todayTasks as $task)
                @php
                    $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                    $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                    $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                @endphp
                <article class="concept-item flex-col items-start sm:flex-row sm:items-center">
                    <div class="min-w-0">
                        <h3 class="break-words font-semibold">{{ $task->title }}</h3>
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
                            @if ($task->category)
                                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $task->category->displayColor() }}" aria-hidden="true"></span>{{ $task->category->name }}</span>
                            @else
                                <span>Tanpa kategori</span>
                            @endif
                            <span>{{ $task->status->name ?? 'Belum berstatus' }}</span>
                            <span>
                                @if ($startsAt)
                                    {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                @elseif ($dueDate)
                                    Jatuh tempo {{ $dueDate->format('H:i') }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <x-task-actions :task="$task" />
                </article>
                @endforeach
            @else
                <div class="concept-item flex-col items-start py-4">
                    <p class="font-medium">Belum ada jadwal untuk hari ini.</p>
                    <p class="mt-1 text-sm text-muted">Tambahkan tugas agar rencana Anda mudah diingat.</p>
                    <a href="{{ route('user.tasks.create') }}" class="dashboard-action-link mt-3"><x-concept-icon name="spark" class="h-5 w-5" />Buat tugas pertama</a>
                </div>
            @endif
        </section>
        @endif

        @if (in_array('attention', $dashboardSections, true))
            <section class="concept-card concept-card-pink dashboard-panel">
                <h2 class="text-lg font-semibold">Tugas yang terlewat</h2>
                <p class="mt-1 text-sm text-muted">Anda masih bisa mengatur ulang waktunya atau menandainya selesai.</p>
                @forelse ($overdueTasks as $task)
                    <article class="concept-item flex-col items-start sm:flex-row sm:items-center">
                        <div class="min-w-0"><h3 class="break-words font-medium">{{ $task->title }}</h3><p class="mt-1 inline-flex items-center gap-1.5 text-sm text-warning">Terlambat · {{ ($task->due_date ?? $task->ends_at)?->timezone(auth()->user()->timezone)->translatedFormat('j M Y, H:i') }}</p></div>
                        <x-task-actions :task="$task" />
                    </article>
                @empty
                    <div class="mt-4 border-t border-line py-4"><p class="font-medium">Tidak ada tugas yang terlewat.</p><p class="mt-1 text-sm text-muted">Bagus, semua tenggat masih terjaga.</p></div>
                @endforelse
            </section>
        @endif

        @if (in_array('upcoming', $dashboardSections, true))
            <section class="concept-card concept-card-blue dashboard-panel">
                <h2 class="text-lg font-semibold">7 hari mendatang</h2>
                <p class="mt-1 text-sm text-muted">Lihat tugas yang akan datang agar bisa menyiapkan waktu.</p>
                @forelse ($upcomingTasks as $task)
                    @php
                        $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                        $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                        $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                    @endphp
                    <article class="concept-item flex-col items-start sm:flex-row sm:items-center">
                        <div class="min-w-0">
                            <h3 class="break-words font-medium">{{ $task->title }}</h3>
                            <p class="mt-1 text-sm text-muted">
                                {{ ($startsAt ?? $dueDate)?->translatedFormat('j M Y') }}
                                @if ($startsAt)
                                    · {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                @elseif ($dueDate)
                                    · Jatuh tempo {{ $dueDate->format('H:i') }}
                                @endif
                            </p>
                        </div>
                        <x-task-actions :task="$task" />
                    </article>
                @empty
                    <div class="mt-4 border-t border-line py-4"><p class="font-medium">Belum ada tugas dalam 7 hari mendatang.</p><p class="mt-1 text-sm text-muted">Tugas baru akan muncul di sini setelah Anda menambahkannya.</p><a href="{{ route('user.tasks.create') }}" class="dashboard-action-link mt-3">Buat tugas baru</a></div>
                @endforelse
                @foreach ($agendaByDay as $date => $items)
                    @if ($date > $today)
                        @foreach ($items->where('type', '!=', 'task') as $item)
                            <article class="concept-item">
                                <span class="min-w-0 break-words"><span class="font-medium">{{ $item['title'] }}</span><span class="text-sm text-muted"> · {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('j M') }} · {{ $item['status'] }}</span></span>
                                <a href="{{ $item['url'] }}" class="dashboard-action-link shrink-0">Lihat detail</a>
                            </article>
                        @endforeach
                    @endif
                @endforeach
            </section>
        @endif

        <section class="concept-card concept-card-purple dashboard-panel" aria-labelledby="goals-summary-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 id="goals-summary-heading" class="text-lg font-semibold">Target aktif</h2><p class="mt-1 text-sm text-muted">Langkah yang sedang kamu kejar.</p></div>
                <a href="{{ route('user.goals.index') }}" class="dashboard-action-link">Kelola target</a>
            </div>
            <div class="mt-4">
                @forelse ($activeGoals as $goal)
                    <div class="concept-item flex-wrap">
                        <div class="min-w-0 flex-1">
                            <p class="break-words font-medium">{{ $goal->title }}</p>
                            <p class="text-sm text-muted">{{ $goal->current_value }} / {{ $goal->target_value }} {{ $goal->unit }}</p>
                            <x-progress-bar class="mt-2 max-w-xl" :value="$goal->progress_percent" :label="'Progres '.$goal->title" />
                        </div>
                        <strong class="shrink-0">{{ $goal->progress_percent }}%</strong>
                    </div>
                @empty
                    <p class="border-t border-line pt-4 text-sm text-muted">Belum ada target aktif. <a href="{{ route('user.goals.index') }}" class="font-semibold underline">Buat target</a></p>
                @endforelse
            </div>
        </section>

        @if (in_array('finance', $dashboardSections, true))
        <section class="concept-card concept-card-orange dashboard-panel" aria-labelledby="money-summary-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 id="money-summary-heading" class="text-lg font-semibold">Ringkasan uang bulan ini</h2><p class="mt-1 text-sm text-muted">Saldo dihitung dari dompet dan catatan uang Anda.</p></div>
                <a href="{{ route('user.money.index') }}" class="dashboard-action-link">Buka ringkasan</a>
            </div>
            <div class="money-summary-grid dashboard-money-summary mt-4">
                <article class="concept-stat"><b data-private>{{ rupiah($totalBalance) }}</b><span>saldo semua dompet</span></article>
                <article class="concept-stat"><b data-private>{{ rupiah($monthlySummary['income']) }}</b><span>pemasukan</span></article>
                <article class="concept-stat"><b data-private>{{ rupiah($monthlySummary['expense']) }}</b><span>pengeluaran</span></article>
                <article class="concept-stat"><b data-private>{{ rupiah($monthlySummary['net']) }}</b><span>sisa</span></article>
            </div>
        </section>
        @endif

        @if ($showOnboarding)
            <section class="surface-card mt-6 border-primary-200 p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><h2 class="text-lg font-semibold">Mulai dari mana?</h2><p class="mt-1 text-sm text-muted">Pilih satu langkah kecil. Anda bisa melanjutkan kapan saja.</p></div>
                    <form method="POST" action="{{ route('user.onboarding.dismiss') }}">@csrf<x-button class="dashboard-action dashboard-action-quiet">Tutup panduan</x-button></form>
                </div>
                <ol class="mt-4 grid gap-3 sm:grid-cols-3">
                    <li><a href="{{ route('user.tasks.create') }}" class="block min-h-14 rounded-lg border border-line p-3 font-medium hover:bg-gray-50">1. Tambah tugas pertama</a></li>
                    <li><a href="{{ route('user.habits.index') }}" class="block min-h-14 rounded-lg border border-line p-3 font-medium hover:bg-gray-50">2. Pilih kebiasaan contoh</a></li>
                    <li><a href="{{ route('user.money.index') }}" class="block min-h-14 rounded-lg border border-line p-3 font-medium hover:bg-gray-50">3. Catat uang pertama</a></li>
                </ol>
            </section>
        @endif
    </div>
@endsection