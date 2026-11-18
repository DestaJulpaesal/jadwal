<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @auth data-theme="{{ auth()->user()->theme ?? 'light' }}" data-private-default="{{ auth()->user()->hide_financial_amounts ? 'true' : 'false' }}" data-privacy-hidden="{{ auth()->user()->hide_financial_amounts ? 'true' : 'false' }}" @endauth>
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
    <script @isset($cspNonce) nonce="{{ $cspNonce }}" @endisset>
        (() => {
            const root = document.documentElement;
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
            const privacyStorageKey = 'jadwal:hide-financial-amounts';
            const applyTheme = () => {
                root.dataset.colorScheme = root.dataset.theme === 'system'
                    ? (prefersDark.matches ? 'dark' : 'light')
                    : (root.dataset.theme || 'light');
            };

            applyTheme();
            prefersDark.addEventListener('change', applyTheme);

            try {
                const savedPrivacyPreference = sessionStorage.getItem(privacyStorageKey);
                if (savedPrivacyPreference === 'true' || savedPrivacyPreference === 'false') {
                    root.dataset.privacyHidden = savedPrivacyPreference;
                }
            } catch (error) {
                console.error('Could not read the financial privacy preference.', error);
            }
        })();
    </script>
    @if (request()->routeIs('home', 'login', 'register'))
        @vite(['resources/css/public.css', 'resources/js/app.js'])
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @yield('extra-assets')
</head>
<body class="@yield('body-class')" @yield('body-attributes')>
    <a class="skip-link" href="#main-content">Lompat ke konten</a>
    @yield('body')
</body>
</html>
