<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @auth data-theme="{{ auth()->user()->theme ?? 'light' }}" @endauth>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('document-title')@yield('document-title')@else @yield('title', 'Beranda') · Jadwal Harian @endif</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=3">
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
    @yield('body')
</body>
</html>
