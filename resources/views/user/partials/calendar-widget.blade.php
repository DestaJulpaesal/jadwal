@php
        $selectedDate = $month->isCurrentMonth() ? now(auth()->user()->timezone)->toDateString() : $month->toDateString();
        $weekdays = [
            ['short' => 'Sen', 'full' => 'Senin'],
            ['short' => 'Sel', 'full' => 'Selasa'],
            ['short' => 'Rab', 'full' => 'Rabu'],
            ['short' => 'Kam', 'full' => 'Kamis'],
            ['short' => 'Jum', 'full' => 'Jumat'],
            ['short' => 'Sab', 'full' => 'Sabtu'],
            ['short' => 'Min', 'full' => 'Minggu'],
        ];
        if ($weekStartsAt === \Illuminate\Support\Carbon::SUNDAY) {
            $weekdays = array_merge([array_pop($weekdays)], $weekdays);
        }
@endphp
    <section id="user-calendar" class="concept-card concept-card-blue dashboard-wide" data-selected-day="{{ $selectedDate }}" aria-labelledby="user-calendar-heading">
        <header class="concept-card-header">
            <div>
                <div class="concept-heading mb-0"><x-concept-icon name="cl" /><div><h2 id="user-calendar-heading" class="text-lg font-semibold">Kalender</h2><p class="mt-1 text-sm">Pilih tanggal untuk melihat agenda bulanan.</p></div></div>
            </div>
            <x-button href="{{ route('user.tasks.create') }}" variant="secondary"><x-concept-icon name="tk" class="h-5 w-5" />Tambah tugas</x-button>
        </header>

        <section class="surface-card overflow-hidden">
            <div class="flex items-center justify-between gap-2 border-b border-line p-3 sm:p-4">
                <x-button href="{{ route('user.calendar', ['year' => $previousMonth->year, 'month' => $previousMonth->month]) }}" variant="secondary" size="sm" aria-label="Bulan sebelumnya" title="Bulan sebelumnya">
                    <span>Sebelumnya</span>
                </x-button>
                <h2 class="text-center text-base font-semibold sm:text-lg">{{ $month->translatedFormat('F Y') }}</h2>
                <x-button href="{{ route('user.calendar', ['year' => $nextMonth->year, 'month' => $nextMonth->month]) }}" variant="secondary" size="sm" aria-label="Bulan berikutnya" title="Bulan berikutnya">
                    <span>Berikutnya</span>
                </x-button>
            </div>

            <div class="grid grid-cols-7 border-b border-line bg-gray-50 text-center text-xs font-semibold text-muted sm:text-sm">
                @foreach ($weekdays as $weekday)
                    <div class="py-2 sm:py-3"><span class="sm:hidden">{{ $weekday['short'] }}</span><span class="hidden sm:inline">{{ $weekday['full'] }}</span></div>
                @endforeach
            </div>

            @foreach ($weeks as $week)
                <div class="grid grid-cols-7">
                    @foreach ($week as $day)
                        @php
                            $dayKey = $day['date']->toDateString();
                        @endphp
                        <div class="min-h-12 border-b border-r border-line p-0.5 last:border-r-0 sm:min-h-32 sm:p-2 {{ $day['in_month'] ? 'bg-white' : 'bg-gray-50 text-gray-600' }}">
                            <x-button
                                type="button"
                                class="mx-auto flex h-11 w-11 items-center justify-center rounded-full text-sm font-medium hover:bg-primary-50 sm:mx-0 {{ $day['date']->isToday() ? 'bg-primary-700 text-white hover:bg-primary-800' : '' }}"
                                data-calendar-day="{{ $dayKey }}"
                                aria-pressed="{{ $dayKey === $selectedDate ? 'true' : 'false' }}"
                                aria-label="{{ $day['date']->translatedFormat('l, j F Y') }}"
                            >{{ $day['date']->day }}</x-button>

                            <div class="mt-1 hidden space-y-1 md:block">
                                @foreach ($day['tasks']->take(3) as $task)
                                    @php
                                        $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                                        $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                                        $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                                    @endphp
                                    <a href="{{ route('user.tasks.edit', $task) }}" class="block truncate rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-900 hover:bg-primary-100" title="{{ $task->title }}">
                                        @if ($task->category)
                                            <span class="mr-1 inline-block h-2 w-2 rounded-full align-middle" style="background-color: {{ $task->category->displayColor() }}" aria-hidden="true"></span>
                                        @endif
                                        @if ($startsAt)
                                            {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                        @elseif ($dueDate)
                                            {{ $dueDate->format('H:i') }} · Jatuh tempo
                                        @endif
                                        {{ $task->title }}
                                    </a>
                                @endforeach
                                @if ($day['tasks']->count() > 3)
                                    <p class="px-2 text-xs text-muted">+{{ $day['tasks']->count() - 3 }} tugas lainnya</p>
                                @endif
                                @foreach ($calendarAgendaByDay->get($dayKey, collect())->where('type', '!=', 'task')->take(2) as $agendaItem)
                                    <a href="{{ $agendaItem['url'] }}" class="block truncate rounded-md border border-line bg-white px-2 py-1 text-xs text-ink" title="{{ $agendaItem['status'] }}">{{ $agendaItem['type'] === 'habit' ? 'Kebiasaan' : ucfirst($agendaItem['type']) }} · {{ $agendaItem['title'] }}</a>
                                @endforeach
                            </div>

                            <div class="flex justify-center gap-0.5 py-0.5 md:hidden" aria-hidden="true">
                                @foreach ($day['tasks']->take(3) as $task)
                                    <span class="h-1.5 w-1.5 rounded-full {{ $task->category ? '' : 'bg-primary-600' }}" style="background-color: {{ $task->category ? $task->category->displayColor() : '#226B50' }}"></span>
                                @endforeach
                                @if ($day['tasks']->count() > 3)
                                    <span class="text-[9px] leading-none">+</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>

        <section class="surface-card mt-5 p-4 md:hidden" aria-live="polite">
            @foreach ($weeks as $week)
                @foreach ($week as $day)
                    @php
                        $dayKey = $day['date']->toDateString();
                    @endphp
                    <div data-calendar-panel="{{ $dayKey }}" @if ($dayKey !== $selectedDate) hidden @endif>
                        <h2 class="text-lg font-semibold">{{ $day['date']->translatedFormat('l, j F Y') }}</h2>
                        @forelse ($day['tasks'] as $task)
                            @php
                                $startsAt = $task->starts_at?->timezone(auth()->user()->timezone);
                                $endsAt = $task->ends_at?->timezone(auth()->user()->timezone);
                                $dueDate = $task->due_date?->timezone(auth()->user()->timezone);
                            @endphp
                            <a href="{{ route('user.tasks.edit', $task) }}" class="mt-3 flex min-h-14 items-center gap-3 border-t border-line py-3">
                                @if ($task->category)
                                    <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $task->category->displayColor() }}" aria-hidden="true"></span>
                                @else
                                    <span class="h-3 w-3 shrink-0 rounded-full bg-primary-600" aria-hidden="true"></span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words font-medium">{{ $task->title }}</span>
                                    <span class="mt-1 block text-sm text-muted">
                                        @if ($startsAt)
                                            {{ $startsAt->format('H:i') }}@if ($endsAt)–{{ $endsAt->format('H:i') }}@endif
                                        @elseif ($dueDate)
                                            Jatuh tempo {{ $dueDate->format('H:i') }}
                                        @endif
                                    </span>
                                </span>
                            </a>
                        @empty
                            <div class="mt-3 border-t border-line py-4">
                                <p class="font-medium">Belum ada tugas pada tanggal ini.</p>
                                <p class="mt-1 text-sm text-muted">Tambahkan tugas jika ada rencana yang ingin diingat.</p>
                                <a href="{{ route('user.tasks.create') }}" class="mt-2 inline-flex min-h-11 items-center gap-2 font-semibold text-primary-800 underline-offset-4 hover:underline"><x-concept-icon name="tk" class="h-5 w-5" />Tambah tugas</a>
                            </div>
                        @endforelse
                        @foreach ($calendarAgendaByDay->get($dayKey, collect())->where('type', '!=', 'task') as $agendaItem)
                            <a href="{{ $agendaItem['url'] }}" class="mt-2 flex min-h-12 items-center justify-between gap-3 border-t border-line py-2">
                                <span class="min-w-0"><span class="block break-words font-medium">{{ $agendaItem['title'] }}</span><span class="text-sm text-muted">{{ $agendaItem['type'] === 'habit' ? 'Kebiasaan' : ucfirst($agendaItem['type']) }} · {{ $agendaItem['status'] }}</span></span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            @endforeach
        </section>
        <p class="mt-3 text-sm text-muted">Kalender memuat tugas, kebiasaan, tagihan, utang/piutang, dan target. Tugas tanpa waktu atau jatuh tempo tidak ditampilkan.</p>
    </section>
