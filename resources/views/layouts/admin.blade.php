@extends('layouts.base')

@section('title', 'Panel admin')
@section('body-class', 'min-h-screen bg-canvas text-ink antialiased')
@section('body')
    <div data-network-banner role="status" aria-live="polite" hidden class="fixed inset-x-0 top-0 z-[60] bg-warning px-4 py-2 text-center text-sm font-semibold text-ink">
        {{ __('ui.feedback.offline') }}
    </div>
    <div x-data="adminSidebar" class="min-h-screen md:flex">
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 bg-ink/40 md:hidden" x-on:click="closeSidebar"></div>
        <aside x-bind:class="sidebarClass" class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-line bg-white transition-transform duration-200 md:translate-x-0">
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
                        <a href="{{ route('admin.categories.index') }}" @class(['nav-link w-full', 'nav-link-active' => request()->routeIs('admin.categories.*')])>
                            <x-icon name="kelola-akun" class="h-5 w-5" />
                            Kategori
                        </a>
                    </div>
                </section>
            </nav>
            <div class="mt-auto border-t border-line p-4">
                <x-sidebar-profile-card :user="auth()->user()" />
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <x-button type="submit" variant="ghost" class="w-full justify-start"><x-icon name="keluar" class="h-5 w-5" />Keluar</x-button>
                </form>
            </div>
        </aside>
        <div class="min-w-0 flex-1 md:ml-64">
            <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white px-4 sm:px-6">
                <x-button type="button" x-on:click="toggleSidebar" x-bind:aria-expanded="sidebarOpen" aria-label="Buka navigasi admin" title="Buka navigasi admin" variant="ghost" class="md:hidden">
                    <x-icon name="menu" class="h-5 w-5" />
                </x-button>
                <span class="font-semibold">@yield('heading', 'Ringkasan akun')</span>
            </header>
            <main id="main-content" tabindex="-1" class="mx-auto max-w-7xl p-4 pb-10 sm:p-6">
                <x-feedback-host />
                @if ($errors->has('account'))
                    <x-alert type="danger" class="mb-5">{{ $errors->first('account') }}</x-alert>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
@endsection
