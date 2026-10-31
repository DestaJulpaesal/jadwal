@extends('layouts.user')

@section('content')
    <div class="max-w-7xl mx-auto">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Kalender tugas</h1>
                <p class="text-gray-600">Tenggat tugas pribadi Anda per bulan.</p>
            </div>
            <a href="{{ route('user.tasks.create') }}" class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Tambah tugas</a>
        </div>

        <div class="mb-4 flex items-center justify-between rounded bg-white p-4 shadow">
            <a href="{{ route('user.calendar', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}" class="rounded border px-3 py-2 hover:bg-gray-50" aria-label="Bulan sebelumnya">&larr; Sebelumnya</a>
            <h2 class="text-lg font-semibold">{{ $month->translatedFormat('F Y') }}</h2>
            <a href="{{ route('user.calendar', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" class="rounded border px-3 py-2 hover:bg-gray-50" aria-label="Bulan berikutnya">Berikutnya &rarr;</a>
        </div>

        <div class="overflow-x-auto rounded bg-white shadow">
            <div class="min-w-[700px]">
                <div class="grid grid-cols-7 border-b bg-gray-50 text-center text-sm font-semibold">
                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $weekday)
                        <div class="p-3">{{ $weekday }}</div>
                    @endforeach
                </div>

                @foreach ($weeks as $week)
                    <div class="grid grid-cols-7">
                        @foreach ($week as $day)
                            <div class="min-h-32 border-b border-r p-2 {{ $day['in_month'] ? '' : 'bg-gray-50 text-gray-400' }}">
                                <div class="mb-2 flex justify-end">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $day['date']->isToday() ? 'bg-blue-600 text-white' : '' }}">{{ $day['date']->day }}</span>
                                </div>
                                @foreach ($day['tasks']->take(3) as $task)
                                    @php
                                        $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                                        $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                                        $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                                    @endphp
                                    <a href="{{ route('user.tasks.edit', $task) }}" class="mb-1 block truncate rounded bg-blue-100 px-2 py-1 text-xs text-blue-900 hover:bg-blue-200" title="{{ $task->title }}">
                                        @if ($task->category)
                                            <span class="mr-1 inline-block h-2 w-2 rounded-full align-middle" style="background-color: {{ $task->category->displayColor() }}"></span>
                                        @endif
                                        @if ($startsAt)
                                            {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                        @elseif ($dueDate)
                                            {{ $dueDate->format('H:i') }} · Tenggat
                                        @endif
                                        {{ $task->title }}
                                    </a>
                                @endforeach
                                @if ($day['tasks']->count() > 3)
                                    <p class="text-xs text-gray-500">+{{ $day['tasks']->count() - 3 }} tugas lainnya</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        <p class="mt-3 text-sm text-gray-500">Tugas tanpa tenggat tidak ditampilkan di kalender.</p>
    </div>
@endsection
