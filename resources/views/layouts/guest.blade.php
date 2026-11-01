<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Akun') · Jadwal Harian</title>
    @if (request()->routeIs('login'))
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bagel+Fat+One&family=Baloo+2:wght@600;700;800&family=Caveat:wght@600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">
        @vite(['resources/css/concept-b-login.css', 'resources/js/app.js'])
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
@if (request()->routeIs('login'))
<body class="concept-login-body">
    <x-concept-b-sprite />
    <div class="concept-login-shell">
        <section class="desktop-panel" aria-label="Jadwal Harian">
            <svg class="hero-mascot stk bobf" viewBox="0 0 110 100" aria-hidden="true"><use href="#mascot" /></svg>
            <svg class="hero-spark stk" viewBox="0 0 24 24" aria-hidden="true"><use href="#spark" /></svg>
            <p class="k">Jadwal Harian</p>
            <h1>Rencanakan hari,<br>jalani dengan tenang.</h1>
            <p class="desktop-copy">Tugas, kebiasaan, agenda, dan catatan uangmu—tertata dalam satu tempat.</p>
            <div class="quick" aria-label="Fitur yang tersedia">
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#tk" /></svg></span>Tugas</div>
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#fl" /></svg></span>Kebiasaan</div>
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#wl" /></svg></span>Uang</div>
                <div class="q"><span class="ic" aria-hidden="true"><svg viewBox="0 0 48 48"><use href="#cl" /></svg></span>Jadwal</div>
            </div>
            <p class="desktop-note">Mulai dari satu hal kecil. Sisanya bisa menyusul.</p>
        </section>
        <main class="ph">
            <div class="in">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
@else
<body class="min-h-screen bg-canvas text-ink antialiased">
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
