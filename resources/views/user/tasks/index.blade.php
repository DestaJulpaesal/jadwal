@extends('layouts.user')

@section('title', 'Tugas')

@section('content')
    <div class="mx-auto max-w-6xl">
        <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-semibold text-primary-800">Ruang kerja pribadi</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">Daftar tugas</h1>
                <p class="mt-2 text-muted">Cari, saring, dan perbarui rencana Anda.</p>
            </div>
            <x-button href="{{ route('user.tasks.create') }}" variant="primary" class="hidden md:inline-flex"><x-concept-icon name="tk" class="h-5 w-5" />Tambah tugas</x-button>
        </header>

        @if (session('schedule_conflicts'))
            <x-alert type="warning" class="mb-5">
                <p class="font-semibold">Perhatian: jadwal berhasil disimpan, tetapi bertabrakan dengan:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach (session('schedule_conflicts') as $conflict)
                        <li>{{ $conflict['title'] }} ({{ $conflict['starts_at'] }}–{{ $conflict['ends_at'] }})</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="GET" action="{{ route('user.tasks.index') }}" class="surface-card mb-5 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-[1.5fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label for="search" class="field-label inline-flex items-center gap-2"><x-concept-icon name="sc" class="h-5 w-5" />Cari tugas</label>
                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" type="search" autocomplete="off" placeholder="Judul atau catatan" class="form-control">
            </div>
            <div>
                <label for="status_id" class="field-label">Status</label>
                <select id="status_id" name="status_id" class="form-control">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected(($filters['status_id'] ?? '') == $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="due" class="field-label inline-flex items-center gap-2"><x-concept-icon name="cl" class="h-5 w-5" />Waktu tugas</label>
                <select id="due" name="due" class="form-control">
                    <option value="">Semua jadwal</option>
                    <option value="today" @selected(($filters['due'] ?? '') === 'today')>Hari ini</option>
                    <option value="upcoming" @selected(($filters['due'] ?? '') === 'upcoming')>Akan datang</option>
                    <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>Terlambat</option>
                    <option value="none" @selected(($filters['due'] ?? '') === 'none')>Tanpa tenggat</option>
                </select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <x-button type="submit" variant="primary" class="flex-1 lg:flex-none">Terapkan</x-button>
                <x-button href="{{ route('user.tasks.index') }}" variant="secondary" class="flex-1 lg:flex-none">Reset</x-button>
            </div>
        </form>

        <section class="surface-card overflow-hidden">
            @if ($tasks->isEmpty())
                <div class="px-5 py-12 text-center sm:px-8">
                    <h2 class="text-lg font-semibold">{{ request()->hasAny(['search', 'status_id', 'due']) ? 'Tidak ada tugas yang cocok.' : 'Belum ada tugas.' }}</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted">{{ request()->hasAny(['search', 'status_id', 'due']) ? 'Coba ubah kata pencarian atau filter, atau tampilkan semua tugas.' : 'Tambahkan tugas pertama Anda agar rencana mudah diingat dan diatur.' }}</p>
                    <div class="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
                        @if (request()->hasAny(['search', 'status_id', 'due']))
                            <x-button href="{{ route('user.tasks.index') }}" variant="secondary">Tampilkan semua tugas</x-button>
                        @endif
                        <x-button href="{{ route('user.tasks.create') }}" variant="primary">Tambah tugas</x-button>
                    </div>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($tasks as $task)
                        @php
                            $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                            $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                            $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                        @endphp
                        <li class="flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0">
                                <h2 class="break-words font-semibold">{{ $task->title }}</h2>
                                @if ($task->description)
                                    <p class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-muted">{{ $task->description }}</p>
                                @endif
                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted">
                                    @if ($task->category)
                                        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $task->category->displayColor() }}" aria-hidden="true"></span>{{ $task->category->name }}</span>
                                    @else
                                        <span>Tanpa kategori</span>
                                    @endif
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-ink">{{ $task->status->name ?? 'Belum berstatus' }}</span>
                                    <span>
                                        @if ($startsAt)
                                            {{ $startsAt->translatedFormat('j M Y') }} · {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                        @elseif ($dueDate)
                                            Jatuh tempo {{ $dueDate->translatedFormat('j M Y, H:i') }}
                                        @else
                                            Tanpa tenggat
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <x-task-actions :task="$task" show-delete />
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="border-t border-line px-4 py-4 sm:px-5">{{ $tasks->links() }}</div>
        </section>
    </div>
@endsection
