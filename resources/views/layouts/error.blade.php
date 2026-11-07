<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Jadwal Harian</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=3">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-[100dvh] items-center justify-center bg-canvas px-4 py-10 text-ink">
    <main class="surface-card w-full max-w-xl p-6 text-center sm:p-10">
        <p class="text-sm font-semibold tracking-wide text-primary-800">@yield('code')</p>
        <h1 class="mt-3 text-2xl font-semibold sm:text-3xl">@yield('heading')</h1>
        <p class="mx-auto mt-3 max-w-md leading-7 text-muted">@yield('message')</p>
        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
            @if (in_array((string) trim($__env->yieldContent('code')), ['429', '500', '503'], true))
                <x-button href="{{ request()->fullUrl() }}" variant="secondary">Coba lagi</x-button>
            @endif
            @auth
                @if (auth()->user()->isAdmin())
                    <x-button href="{{ route('admin.dashboard') }}" variant="primary">Kembali ke panel admin</x-button>
                @else
                    <x-button href="{{ route('user.dashboard') }}" variant="primary">Kembali ke beranda</x-button>
                @endif
            @else
                <x-button href="{{ route('home') }}" variant="primary">Kembali ke beranda</x-button>
                <x-button href="{{ route('login') }}" variant="secondary">Masuk</x-button>
            @endauth
        </div>
    </main>
</body>
</html>
