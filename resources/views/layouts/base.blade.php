<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @auth data-theme="{{ auth()->user()->theme ?? 'light' }}" @endauth>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f7f8f5" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#1d2925" media="(prefers-color-scheme: dark)">
    <title>@hasSection('document-title')@yield('document-title')@else @yield('title', 'Beranda') · Jadwal Harian @endif</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=3">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <script nonce="{{ $cspNonce }}">
        (() => {
            const root = document.documentElement;
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
            const applyTheme = () => {
                root.dataset.colorScheme = root.dataset.theme === 'system'
                    ? (prefersDark.matches ? 'dark' : 'light')
                    : (root.dataset.theme || 'light');
            };

            applyTheme();
            prefersDark.addEventListener('change', applyTheme);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('extra-assets')
</head>
<body class="@yield('body-class')" @yield('body-attributes')>
    <a class="skip-link" href="#main-content">Lompat ke konten</a>
    <div id="pwa-install-banner" class="fixed inset-x-4 bottom-[calc(5rem+env(safe-area-inset-bottom))] z-[100] mx-auto flex max-w-lg items-center justify-between gap-4 rounded-xl bg-white p-4 text-ink shadow-lg" role="region" aria-label="Pasang aplikasi" hidden>
        <span>Pasang Jadwal di perangkatmu.</span>
        <button class="shrink-0 rounded-lg bg-primary-700 px-3 py-2 font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" data-pwa-install type="button">Pasang aplikasi</button>
    </div>
    <div id="pwa-update-banner" class="fixed inset-x-4 bottom-[calc(1rem+env(safe-area-inset-bottom))] z-[100] mx-auto flex max-w-lg items-center justify-between gap-4 rounded-xl bg-primary-900 p-4 text-white shadow-lg" role="status" aria-live="polite" hidden>
        <span>Versi baru tersedia.</span>
        <button id="pwa-update-button" class="shrink-0 rounded-lg bg-white px-3 py-2 font-semibold text-primary-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2" type="button">Muat ulang</button>
    </div>
    @yield('body')
</body>
</html>
