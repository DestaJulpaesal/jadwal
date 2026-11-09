<x-subnav class="dashboard-wide" label="Beranda" :items="[
    [
        'label' => 'Hari ini',
        'concept' => 'home',
        'url' => route('user.dashboard'),
        'active' => request()->routeIs('user.dashboard') && request('tab') !== 'attention',
    ],
    [
        'label' => 'Kalender',
        'concept' => 'cl',
        'url' => route('user.calendar'),
        'active' => request()->routeIs('user.calendar'),
    ],
    [
        'label' => 'Perlu perhatian',
        'icon' => 'notifikasi',
        'url' => route('user.dashboard', ['tab' => 'attention']).'#attention-heading',
        'active' => request()->routeIs('user.dashboard') && request('tab') === 'attention',
    ],
]" />
