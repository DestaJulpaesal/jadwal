<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ auth()->user()->theme ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') · Jadwal Harian</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=3">
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
<body class="user-app min-h-screen bg-canvas pt-16 text-ink antialiased" data-private-default="{{ auth()->user()->hide_financial_amounts ? 'true' : 'false' }}">
    <x-concept-b-sprite />
    <div data-network-banner role="status" aria-live="polite" hidden class="fixed inset-x-0 top-0 z-[60] bg-warning px-4 py-2 text-center text-sm font-semibold text-ink">
        Kamu sedang offline. Perubahan akan dikirim setelah tersambung.
    </div>
    <header class="user-header fixed inset-x-0 top-0 z-30">
        <div class="user-header-inner flex h-16 items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('user.dashboard') }}" class="user-brand flex min-h-11 min-w-0 items-center gap-3" aria-label="Jadwal Harian, Beranda">
                <span class="brand-mark flex h-10 w-10 shrink-0 items-center justify-center" aria-hidden="true">
                    <x-concept-icon name="home" class="h-8 w-8" />
                </span>
                <span class="block min-w-0 truncate">Jadwal Harian</span>
            </a>
            <div class="user-header-actions flex shrink-0 items-center gap-2 sm:gap-3">
                @if (request()->routeIs('user.search'))
                    <x-button href="{{ route('user.dashboard') }}" aria-label="Kembali ke beranda" title="Kembali ke beranda" variant="ghost" class="global-search-close">
                        <x-concept-icon name="home" class="h-6 w-6" />
                    </x-button>
                @else
                    <a href="{{ route('user.dashboard') }}#attention-heading" class="notification-button" aria-label="Buka notifikasi" title="Notifikasi">
                        <span class="notification-icon-wrap">
                            <x-icon name="notifikasi" class="h-8 w-8" />
                        </span>
                    </a>
                @endif
                <a href="{{ route('profile.edit') }}" class="profile-header-link" aria-label="Buka profil" title="Profil">
                    <span class="profile-header-frame">
                        <x-profile-avatar :user="auth()->user()" class="h-10 w-10" />
                        <span class="profile-header-dot" aria-hidden="true"></span>
                    </span>
                </a>
            </div>
        </div>
    </header>

    <aside class="user-sidebar fixed bottom-0 left-0 top-16 hidden w-64 lg:block">
        <div class="sidebar-inner flex h-full flex-col overflow-y-auto">
            <div class="sidebar-content px-5 py-6">
                <nav aria-label="Navigasi utama" class="space-y-6">
                    <section>
                        <p class="sidebar-section-label mb-2 px-3">Ringkasan</p>
                        <div class="sidebar-nav space-y-1.5">
                            <a href="{{ route('user.dashboard') }}" class="nav-link w-full {{ request()->routeIs('user.dashboard') ? 'nav-link-active' : '' }}"><x-concept-icon name="home" class="h-5 w-5" />Beranda</a>
                        </div>
                    </section>
                    <section>
                        <p class="sidebar-section-label mb-2 px-3">Aktivitas</p>
                        <div class="sidebar-nav space-y-1.5">
                            <a href="{{ route('user.tasks.index') }}" class="nav-link w-full {{ request()->routeIs('user.tasks.*') ? 'nav-link-active' : '' }}"><x-concept-icon name="tk" class="h-5 w-5" />Tugas</a>
                            <a href="{{ route('user.habits.index') }}" class="nav-link w-full {{ request()->routeIs('user.habits.*') ? 'nav-link-active' : '' }}"><x-concept-icon name="fl" class="h-5 w-5" />Kebiasaan</a>
                        </div>
                    </section>
                    <section>
                        <p class="sidebar-section-label mb-2 px-3">Keuangan</p>
                        <div class="sidebar-nav space-y-1.5">
                            <a href="{{ route('user.money.index') }}" class="nav-link w-full {{ request()->routeIs('user.money.*') ? 'nav-link-active' : '' }}"><x-concept-icon name="wl" class="h-5 w-5" />Keuangan</a>
                        </div>
                    </section>
                </nav>
            </div>
            <div class="sidebar-profile-footer">
                <x-sidebar-profile-card :user="auth()->user()" />
            </div>
        </div>
    </aside>

    <main class="user-main min-h-[calc(100dvh-4rem)] px-4 py-6 pb-32 sm:px-6 sm:py-8 lg:ml-64 lg:pb-10">
        @if (request()->routeIs('user.search'))
            <form method="GET" action="{{ route('user.search') }}" role="search" class="global-search-wrap mx-auto mb-6 max-w-6xl">
                <label for="global-search" class="sr-only">Cari di semua data</label>
                <div class="global-search-control">
                    <x-concept-icon name="sc" class="global-search-icon h-5 w-5" />
                    <input id="global-search" name="q" type="search" value="{{ request('q') }}" maxlength="100" required autocomplete="off" placeholder="Cari tugas, kebiasaan, transaksi..." class="global-search-input">
                    <x-button type="submit" variant="primary" icon-only data-loading-text="Mencari..." aria-label="Cari" title="Cari" icon="cari">
                        <x-concept-icon name="sc" class="h-5 w-5" />
                    </x-button>
                </div>
            </form>
        @endif
        <x-feedback-host />
        @yield('content')
    </main>

    <nav id="mobileNavigationMenu" class="mobile-nav fixed bottom-0 z-40 lg:hidden" aria-label="Navigasi utama">
        <span class="mobile-nav-shape" aria-hidden="true"></span>
        <div class="mobile-nav-grid grid grid-cols-5 items-stretch">
            <a href="{{ route('user.dashboard') }}" class="mobile-nav-link {{ request()->routeIs('user.dashboard') ? 'mobile-nav-link-active' : '' }}" @if (request()->routeIs('user.dashboard')) aria-current="page" @endif>
                <span class="mobile-nav-icon"><x-concept-icon name="home" /></span><span>Beranda</span>
            </a>
            <a href="{{ route('user.tasks.index') }}" class="mobile-nav-link {{ request()->routeIs('user.tasks.*') ? 'mobile-nav-link-active' : '' }}" @if (request()->routeIs('user.tasks.*')) aria-current="page" @endif>
                <span class="mobile-nav-icon"><x-concept-icon name="tk" /></span><span>Tugas</span>
            </a>
            <a href="{{ route('user.habits.index') }}" class="mobile-nav-link {{ request()->routeIs('user.habits.*') ? 'mobile-nav-link-active' : '' }}" @if (request()->routeIs('user.habits.*')) aria-current="page" @endif>
                <span class="mobile-nav-icon"><x-concept-icon name="fl" /></span><span>Kebiasaan</span>
            </a>
            <a href="{{ route('user.money.index') }}" class="mobile-nav-link {{ request()->routeIs('user.money.*') ? 'mobile-nav-link-active' : '' }}" @if (request()->routeIs('user.money.*')) aria-current="page" @endif>
                <span class="mobile-nav-icon"><x-concept-icon name="wl" /></span><span>Keuangan</span>
            </a>
            <a href="{{ route('profile.edit') }}" class="mobile-nav-link {{ request()->routeIs('profile.*') ? 'mobile-nav-link-active' : '' }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                <span class="mobile-nav-icon"><x-concept-icon name="pr" /></span><span>Profil</span>
            </a>
        </div>
    </nav>

    <x-button type="button" class="mobile-create-button fixed bottom-[calc(5rem+env(safe-area-inset-bottom))] right-4 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary-700 text-white shadow-card hover:bg-primary-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-700 lg:bottom-8 lg:right-8 lg:w-auto lg:rounded-lg" x-data x-on:click="$dispatch('open-modal', 'global-create')" aria-label="Tambah baru" title="Tambah baru">
        <x-icon name="tambah" class="h-6 w-6" />
        <span class="hidden lg:inline">Tambah baru</span>
    </x-button>

    <x-bottom-sheet name="global-create" title="Mau tambah apa?">
        <div class="grid gap-2 sm:grid-cols-2">
            <x-button href="{{ route('user.tasks.create') }}" variant="secondary" class="min-h-16 justify-start gap-3">
                <x-concept-icon name="tk" class="h-5 w-5 shrink-0" /><span class="text-left"><strong class="block">Tugas</strong><span class="text-xs text-muted">Catat hal yang perlu dikerjakan.</span></span>
            </x-button>
            <x-button href="{{ route('user.habits.create') }}" variant="secondary" class="min-h-16 justify-start gap-3">
                <x-concept-icon name="fl" class="h-5 w-5 shrink-0" /><span class="text-left"><strong class="block">Kebiasaan</strong><span class="text-xs text-muted">Mulai rutinitas yang ingin dijaga.</span></span>
            </x-button>
            <x-button href="{{ route('user.money.index', ['section' => 'transactions', 'create' => 'transaction']) }}" variant="secondary" class="min-h-16 justify-start gap-3">
                <x-concept-icon name="wl" class="h-5 w-5 shrink-0" /><span class="text-left"><strong class="block">Catat uang</strong><span class="text-xs text-muted">Simpan pemasukan, pengeluaran, atau transfer.</span></span>
            </x-button>
            <x-button href="{{ route('user.goals.create') }}" variant="secondary" class="min-h-16 justify-start gap-3">
                <x-icon name="target" class="h-5 w-5 shrink-0" /><span class="text-left"><strong class="block">Target</strong><span class="text-xs text-muted">Tentukan hasil yang ingin dicapai.</span></span>
            </x-button>
            <x-button href="{{ route('user.obligations.index', ['create' => 'obligation']) }}" variant="secondary" class="min-h-16 justify-start gap-3">
                <x-icon name="kalender" class="h-5 w-5 shrink-0" /><span class="text-left"><strong class="block">Tagihan / cicilan</strong><span class="text-xs text-muted">Atur jadwal pembayaran rutin.</span></span>
            </x-button>
            <x-button href="{{ route('user.loans.index', ['create' => 'loan']) }}" variant="secondary" class="min-h-16 justify-start gap-3">
                <x-icon name="utang" class="h-5 w-5 shrink-0" /><span class="text-left"><strong class="block">Utang / piutang</strong><span class="text-xs text-muted">Catat kesepakatan dan pelunasannya.</span></span>
            </x-button>
        </div>
    </x-bottom-sheet>
</body>
</html>