@extends('layouts.user')

@section('title', 'Api aktivitas')

@section('content')
    <div class="mx-auto max-w-4xl space-y-5">
        <x-page-header
            eyebrow="Konsistensi harian"
            title="Api aktivitas"
            description="Selesaikan setidaknya satu tugas atau kebiasaan setiap hari untuk menjaga rentetanmu."
        />

        <section class="surface-card p-5 sm:p-7" aria-labelledby="streak-summary-heading">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-muted">Api saat ini</p>
                    <h2 id="streak-summary-heading" class="mt-1 text-4xl font-bold tracking-tight">{{ $currentStreak }} hari berturut-turut</h2>
                    <p class="mt-2 text-sm text-muted">
                        @if ($activeToday)
                            Aktivitas hari ini sudah tercatat. Api aman untuk hari ini!
                        @elseif ($currentStreak > 0)
                            Api masih menyala. Selesaikan satu tugas atau kebiasaan hari ini untuk menjaganya.
                        @else
                            Api akan menyala lagi setelah kamu menyelesaikan tugas atau kebiasaan.
                        @endif
                    </p>
                </div>
                <div class="rounded-xl border-2 border-ink bg-primary-50 px-5 py-4 text-center shadow-card">
                    <p class="text-sm font-semibold text-muted">Api tertinggi sepanjang waktu</p>
                    <p class="mt-1 text-2xl font-bold">{{ $bestStreak }} hari</p>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <x-button href="{{ route('user.tasks.index') }}" variant="secondary">
                    <x-concept-icon name="tk" class="h-5 w-5" />
                    Lihat tugas
                </x-button>
                <x-button href="{{ route('user.habits.index') }}" variant="secondary">
                    <x-concept-icon name="fl" class="h-5 w-5" />
                    Lihat kebiasaan
                </x-button>
            </div>
        </section>

        <section class="surface-card p-5 sm:p-7" aria-labelledby="streak-records-heading">
            <h2 id="streak-records-heading" class="text-lg font-semibold">Riwayat api terpanjang</h2>
            <p class="mt-1 text-sm text-muted">Diurutkan dari streak paling panjang, bukan berdasarkan kapan api terakhir padam.</p>
            @if ($streakHistory)
                <ol class="mt-4 space-y-3">
                    @foreach ($streakHistory as $streak)
                        <li @class([
                            'flex flex-wrap items-center justify-between gap-3 rounded-xl border p-4',
                            'border-primary-700 bg-primary-50' => $loop->first,
                            'border-line bg-white' => ! $loop->first,
                        ])>
                            <div>
                                <p class="font-semibold">
                                    @if ($loop->first)
                                        Rekor tertinggi
                                    @else
                                        Rekor #{{ $loop->iteration }}
                                    @endif
                                    @if ($streak['current'])
                                        <span class="ml-1 text-sm font-medium text-primary-800">· Api masih menyala</span>
                                    @endif
                                </p>
                                <p class="mt-1 text-sm text-muted">
                                    {{ $streak['start']->locale(app()->getLocale())->translatedFormat('j F Y') }}
                                    – {{ $streak['end']->locale(app()->getLocale())->translatedFormat('j F Y') }}
                                </p>
                            </div>
                            <p class="text-lg font-bold">{{ $streak['days'] }} hari</p>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="mt-4 rounded-xl border border-line bg-white p-4 text-sm text-muted">
                    Belum ada riwayat api. Selesaikan tugas atau kebiasaan untuk mulai membangun rekor pertamamu.
                </p>
            @endif
        </section>

        <section class="surface-card p-5 sm:p-7" aria-labelledby="streak-history-heading">
            <h2 id="streak-history-heading" class="text-lg font-semibold">Aktivitas 14 hari terakhir</h2>
            <p class="mt-1 text-sm text-muted">Hari bertanda sudah memiliki tugas selesai atau kebiasaan yang dituntaskan.</p>
            <ol class="mt-5 grid grid-cols-7 gap-2">
                @foreach ($calendar as $day)
                    <li class="flex min-w-0 flex-col items-center gap-2 text-center">
                        <span class="text-xs font-medium text-muted">{{ $day['date']->locale(app()->getLocale())->translatedFormat('D') }}</span>
                        <span
                            @class([
                                'flex h-9 w-9 items-center justify-center rounded-full border text-sm font-semibold',
                                'border-primary-700 bg-primary-700 text-white' => $day['active'],
                                'border-line bg-white text-muted' => ! $day['active'],
                                'ring-2 ring-primary-200 ring-offset-2' => $day['today'],
                            ])
                            aria-label="{{ $day['date']->locale(app()->getLocale())->translatedFormat('l, j F') }}: {{ $day['active'] ? 'aktivitas tercatat' : 'belum ada aktivitas' }}"
                            @if ($day['today']) aria-current="date" @endif
                        >{{ $day['date']->day }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
@endsection
