<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Jadwal Harian</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-[100dvh] items-center justify-center bg-canvas px-4 py-10 text-ink">
    <main class="surface-card w-full max-w-xl p-6 text-center sm:p-10">
        <p class="text-sm font-semibold tracking-wide text-primary-800">@yield('code')</p>
        <h1 class="mt-3 text-2xl font-semibold sm:text-3xl">@yield('heading')</h1>
        <p class="mx-auto mt-3 max-w-md leading-7 text-muted">@yield('message')</p>
        <div class="mt-7 flex flex-col justify-center gap-3 sm:flex-row">
            @auth
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn-primary">Kembali ke panel admin</a>
                @else
                    <a href="{{ route('user.dashboard') }}" class="btn-primary">Kembali ke beranda</a>
                @endif
            @else
                <a href="{{ route('home') }}" class="btn-primary">Kembali ke beranda</a>
                <a href="{{ route('login') }}" class="btn-secondary">Masuk</a>
            @endauth
        </div>
    </main>
</body>
</html>
