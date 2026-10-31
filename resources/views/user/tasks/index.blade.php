@extends('layouts.user')

@section('content')
    <div class="max-w-5xl mx-auto">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Daftar tugas</h1>
                <p class="text-gray-600">Kelola tugas pribadi Anda.</p>
            </div>
            <a href="{{ route('user.tasks.create') }}" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Tambah tugas</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded bg-red-100 p-3 text-red-800" role="alert">{{ session('error') }}</div>
        @endif

        @if (session('schedule_conflicts'))
            <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-4 text-amber-900" role="status">
                <p class="font-semibold">Perhatian: jadwal berhasil disimpan, tetapi bertabrakan dengan:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach (session('schedule_conflicts') as $conflict)
                        <li>{{ $conflict['title'] }} ({{ $conflict['starts_at'] }}–{{ $conflict['ends_at'] }})</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="GET" action="{{ route('user.tasks.index') }}" class="mb-4 grid gap-3 rounded bg-white p-4 shadow sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="search" class="sr-only">Cari tugas</label>
                <input id="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari judul atau deskripsi" class="w-full rounded border-gray-300">
            </div>
            <div>
                <label for="status_id" class="sr-only">Status</label>
                <select id="status_id" name="status_id" class="w-full rounded border-gray-300">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" @selected(($filters['status_id'] ?? '') == $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="due" class="sr-only">Jadwal</label>
                <select id="due" name="due" class="w-full rounded border-gray-300">
                    <option value="">Semua jadwal</option>
                    <option value="today" @selected(($filters['due'] ?? '') === 'today')>Hari ini</option>
                    <option value="upcoming" @selected(($filters['due'] ?? '') === 'upcoming')>Akan datang</option>
                    <option value="overdue" @selected(($filters['due'] ?? '') === 'overdue')>Terlambat</option>
                    <option value="none" @selected(($filters['due'] ?? '') === 'none')>Tanpa tenggat</option>
                </select>
            </div>
            <div class="flex flex-col gap-2 sm:col-span-4 sm:flex-row">
                <button class="min-h-11 rounded bg-gray-800 px-4 py-2 text-white hover:bg-gray-700">Filter</button>
                <a href="{{ route('user.tasks.index') }}" class="inline-flex min-h-11 items-center justify-center rounded border px-4 py-2">Reset</a>
            </div>
        </form>

        <div class="rounded bg-white p-6 shadow">
            @forelse ($tasks as $task)
                <div class="flex flex-wrap items-center justify-between gap-4 border-b py-4 last:border-b-0">
                    <div class="min-w-0">
                        <h2 class="font-semibold">{{ $task->title }}</h2>
                        @if ($task->description)
                            <p class="mt-1 text-sm text-gray-600">{{ $task->description }}</p>
                        @endif
                        @php
                            $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                            $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                            $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                        @endphp
                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-600">
                            @if ($task->category)
                                <span class="inline-flex items-center gap-1">
                                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $task->category->displayColor() }}"></span>
                                    {{ $task->category->name }}
                                </span>
                            @else
                                <span>Tanpa kategori</span>
                            @endif
                            <span>{{ $task->status->name ?? 'Belum berstatus' }}</span>
                            <span>
                                @if ($startsAt)
                                    {{ $startsAt->format('d M Y H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                @else
                                    {{ $dueDate?->format('d M Y H:i') ?? 'Tanpa tenggat' }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex w-full items-center justify-between gap-3 sm:w-auto sm:justify-end">
                        @if ($task->status?->name !== 'Selesai')
                            <form method="POST" action="{{ route('user.tasks.complete', $task) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex min-h-10 items-center rounded bg-green-100 px-3 py-2 text-sm font-medium text-green-800 hover:bg-green-200">Tandai selesai</button>
                            </form>
                        @endif
                        <a href="{{ route('user.tasks.edit', $task) }}" class="text-blue-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('user.tasks.destroy', $task) }}" onsubmit="return confirm('Hapus tugas ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="min-h-10 text-red-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="py-4 text-gray-600">Belum ada tugas. Tambahkan tugas pertama Anda.</p>
            @endforelse
        </div>

        <div class="mt-4">{{ $tasks->links() }}</div>
    </div>
@endsection
