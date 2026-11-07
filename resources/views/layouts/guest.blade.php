<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Akun') · Jadwal Harian</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=3">
    @if (request()->routeIs('login', 'register'))
        @vite(['resources/css/app.css', 'resources/css/pages/login.css', 'resources/js/app.js'])
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
@if (request()->routeIs('login', 'register'))
<body class="concept-login-body">
    <div data-network-banner role="status" aria-live="polite" hidden class="fixed inset-x-0 top-0 z-[60] bg-warning px-4 py-2 text-center text-sm font-semibold text-ink">
        Kamu sedang offline. Perubahan akan dikirim setelah tersambung.
    </div>
    <x-concept-b-sprite />
    <div class="concept-login-shell">
        <section class="desktop-panel" aria-label="Jadwal Harian">
            <svg class="hero-mascot stk bobf" viewBox="0 0 110 100" aria-hidden="true"><use href="#mascot" /></svg>
            <svg class="hero-spark stk" viewBox="0 0 24 24" aria-hidden="true"><use href="#spark" /></svg>
            <p class="k">Jadwal Harian</p>
            @if (request()->routeIs('register'))
                <h1>Hari baru,<br>rencana baru!</h1>
                <p class="desktop-copy">Buat ruang untuk semua yang ingin kamu capai, satu langkah kecil setiap hari.</p>
            @else
                <h1>Rencanakan hari,<br>jalani dengan tenang.</h1>
                <p class="desktop-copy">Tugas, kebiasaan, agenda, dan catatan uangmu—tertata dalam satu tempat.</p>
            @endif
            <div class="quick" aria-label="Fitur yang tersedia">
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#tk" /></svg></span>Tugas</div>
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#fl" /></svg></span>Kebiasaan</div>
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#wl" /></svg></span>Uang</div>
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#cl" /></svg></span>Jadwal</div>
            </div>
            <p class="desktop-note">{{ request()->routeIs('register') ? 'Mulai dari hari ini. Besok, kamu akan berterima kasih.' : 'Mulai dari satu hal kecil. Sisanya bisa menyusul.' }}</p>
        </section>
        <main class="ph">
            <div class="in">
                <x-feedback-host />
                <a class="auth-home-link" href="{{ route('home') }}">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3.5 10 8.5-7 8.5 7M5.5 9v11h13V9M9.5 20v-6h5v6" /></svg>
                    Kembali ke beranda
                </a>
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
@else
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div data-network-banner role="status" aria-live="polite" hidden class="fixed inset-x-0 top-0 z-[60] bg-warning px-4 py-2 text-center text-sm font-semibold text-ink">
        Kamu sedang offline. Perubahan akan dikirim setelah tersambung.
    </div>
    <main class="mx-auto flex min-h-[100dvh] w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:gap-16 lg:py-12">
        <section class="hidden flex-1 lg:block">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3 text-lg font-semibold text-ink">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-800" aria-hidden="true">
                    <x-icon name="kalender" class="h-6 w-6" />
                </span>
                Jadwal Harian
            </a>
            <div class="mt-14 max-w-lg">
                <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-primary-800">Rencanakan dengan tenang</p>
                <h1 class="text-4xl font-semibold leading-tight tracking-tight">Semua rencana harian, tertata di satu tempat.</h1>
                <p class="mt-5 max-w-md text-lg leading-8 text-muted">Catat tugas, atur waktunya, lalu lihat apa yang perlu dikerjakan hari ini.</p>
            </div>
        </section>
        <section class="mx-auto w-full max-w-md flex-1 lg:mx-0">
            <x-feedback-host />
            <a href="{{ route('home') }}" class="mb-8 inline-flex min-h-11 items-center gap-2 font-semibold text-ink lg:hidden">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 text-primary-800" aria-hidden="true">
                    <x-icon name="kalender" class="h-5 w-5" />
                </span>
                Jadwal Harian
            </a>
            <div class="rounded-2xl border border-line bg-white p-5 shadow-card sm:p-8">
                {{ $slot }}
            </div>
            <p class="mt-6 text-center text-sm text-muted"><a href="{{ route('home') }}" class="underline-offset-4 hover:underline">Kembali ke beranda</a></p>
        </section>
    </main>
</body>
@endif
</html>
