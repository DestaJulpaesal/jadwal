<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel admin') · Jadwal Harian</title>
    <script>
        (() => {
            const root = document.documentElement;
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');
            const applyTheme = () => {
                root.dataset.colorScheme = root.dataset.theme === 'system'
                    ? (prefersDark.matches ? 'dark' : 'light')
                    : root.dataset.theme;
            };

            applyTheme();
            prefersDark.addEventListener('change', applyTheme);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen md:flex">
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-ink/40 md:hidden" @click="sidebarOpen = false"></div>
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-line bg-white transition-transform duration-200 md:translate-x-0">
            <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center gap-3 border-b border-line px-5 font-semibold">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 text-primary-800" aria-hidden="true">
                    <x-icon name="kalender" class="h-5 w-5" />
                </span>
                Jadwal Harian
            </a>
            <nav class="space-y-5 px-3 pt-6" aria-label="Navigasi admin">
                <section>
                    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-muted">Ringkasan</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.dashboard') }}" @class(['nav-link w-full', 'nav-link-active' => request()->routeIs('admin.dashboard')]) @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                            <x-icon name="beranda" class="h-5 w-5" />
                            Ringkasan akun
                        </a>
                    </div>
                </section>
                <section>
                    <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wide text-muted">Manajemen</p>
                    <div class="space-y-1">
                        <a href="{{ route('admin.users.index') }}" @class(['nav-link w-full', 'nav-link-active' => request()->routeIs('admin.users.*')]) @if (request()->routeIs('admin.users.*')) aria-current="page" @endif>
                            <x-icon name="kelola-akun" class="h-5 w-5" />
                            Manajemen Akun
                        </a>
                    </div>
                </section>
            </nav>
            <div class="mt-auto border-t border-line p-4">
                <x-sidebar-profile-card :user="auth()->user()" />
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="btn-quiet w-full justify-start" type="submit"><x-icon name="keluar" class="h-5 w-5" />Keluar</button>
                </form>
            </div>
        </aside>
        <div class="min-w-0 flex-1 md:ml-64">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white px-4 sm:px-6">
                <button type="button" class="btn-icon md:hidden" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Buka navigasi admin">
                    <x-icon name="menu" class="h-5 w-5" />
                </button>
                <span class="font-semibold">@yield('heading', 'Ringkasan akun')</span>
            </header>
            <main class="mx-auto max-w-7xl p-4 pb-10 sm:p-6">
                @if (session('success'))
                    <x-toast>{{ session('success') }}</x-toast>
                @endif
                @if ($errors->has('account'))
                    <x-alert type="danger" class="mb-5">{{ $errors->first('account') }}</x-alert>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
