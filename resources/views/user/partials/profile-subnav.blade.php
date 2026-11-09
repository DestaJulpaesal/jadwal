@php($profileItems = [
    ['label' => 'Profil', 'concept' => 'pr', 'url' => route('profile.edit'), 'active' => request()->routeIs('profile.*')],
    ['label' => 'Pengaturan', 'icon' => 'zona-waktu', 'url' => route('settings.edit'), 'active' => request()->routeIs('settings.*')],
    ['label' => 'Aplikasi', 'icon' => 'kalender', 'url' => route('user.application'), 'active' => request()->routeIs('user.application')],
])
@if (auth()->user()->isAdmin())
    @php($profileItems[] = ['label' => 'Panel Admin', 'icon' => 'kelola-akun', 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.*')])
@endif

<x-subnav label="Profil" :items="$profileItems" />
