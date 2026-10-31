@extends('layouts.user')

@section('content')
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Jadwal saya</h1>
                <p class="text-gray-600">Halo, {{ Auth::user()->name }}. Berikut ringkasan jadwal Anda.</p>
            </div>
            <a href="{{ route('user.tasks.create') }}" class="inline-flex min-h-11 items-center justify-center rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Tambah tugas</a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded bg-green-100 p-3 text-green-800" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded bg-red-100 p-3 text-red-800" role="alert">{{ session('error') }}</div>
        @endif

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Total tugas</p>
                <p class="text-2xl font-semibold">{{ $totalTasks }}</p>
            </div>
            <div class="rounded bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Jadwal hari ini</p>
                <p class="text-2xl font-semibold">{{ $todayTasks->count() }}</p>
            </div>
            <div class="rounded bg-white p-4 shadow">
                <p class="text-sm text-gray-500">Terlambat</p>
                <p class="text-2xl font-semibold">{{ $overdueTasks->count() }}</p>
            </div>
        </div>

        <section class="mb-6 rounded bg-white p-4 shadow sm:p-6">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold">Jadwal hari ini</h2>
                <a href="{{ route('user.tasks.index', ['due' => 'today']) }}" class="text-sm text-blue-600 hover:underline">Lihat daftar</a>
            </div>
            @forelse ($todayTasks as $task)
                @php
                    $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                    $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                    $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                @endphp
                <div class="flex flex-col gap-3 border-t py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-medium">{{ $task->title }}</p>
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
                                    {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                @elseif ($dueDate)
                                    Tenggat {{ $dueDate->format('H:i') }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        @if ($task->status?->name !== 'Selesai')
                            <form method="POST" action="{{ route('user.tasks.complete', $task) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex min-h-10 items-center rounded bg-green-100 px-3 py-2 text-sm font-medium text-green-800 hover:bg-green-200">Tandai selesai</button>
                            </form>
                        @endif
                        <a href="{{ route('user.tasks.edit', $task) }}" class="text-blue-600 hover:underline">Edit</a>
                    </div>
                </div>
            @empty
                <p class="border-t py-4 text-gray-600">Belum ada jadwal untuk hari ini.</p>
            @endforelse
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded bg-white p-4 shadow sm:p-6">
                <h2 class="mb-4 text-lg font-semibold">Terlambat</h2>
                @forelse ($overdueTasks as $task)
                    @php
                        $taskTime = ($task->due_date ?? $task->ends_at)?->timezone(auth()->user()->timezone);
                    @endphp
                    <div class="flex flex-col gap-3 border-t py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $task->title }}</p>
                            <p class="mt-1 text-sm text-red-700">{{ $taskTime?->format('d M Y H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('user.tasks.complete', $task) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex min-h-10 items-center rounded bg-green-100 px-3 py-2 text-sm font-medium text-green-800 hover:bg-green-200">Tandai selesai</button>
                            </form>
                            <a href="{{ route('user.tasks.edit', $task) }}" class="text-blue-600 hover:underline">Edit</a>
                        </div>
                    </div>
                @empty
                    <p class="border-t py-4 text-gray-600">Tidak ada tugas yang terlambat.</p>
                @endforelse
            </section>

            <section class="rounded bg-white p-4 shadow sm:p-6">
                <h2 class="mb-4 text-lg font-semibold">7 hari mendatang</h2>
                @forelse ($upcomingTasks as $task)
                    @php
                        $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                        $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                        $taskTime = $startsAt ?? $dueDate;
                    @endphp
                    <div class="flex flex-col gap-3 border-t py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $task->title }}</p>
                            <p class="mt-1 text-sm text-gray-600">{{ $taskTime?->format('d M Y H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('user.tasks.complete', $task) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex min-h-10 items-center rounded bg-green-100 px-3 py-2 text-sm font-medium text-green-800 hover:bg-green-200">Tandai selesai</button>
                            </form>
                            <a href="{{ route('user.tasks.edit', $task) }}" class="text-blue-600 hover:underline">Edit</a>
                        </div>
                    </div>
                @empty
                    <p class="border-t py-4 text-gray-600">Tidak ada tugas dalam 7 hari mendatang.</p>
                @endforelse
            </section>
        </div>
    </div>
@endsection
