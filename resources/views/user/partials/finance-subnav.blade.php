@php($month = request('month', now(auth()->user()->timezone)->format('Y-m')))

<x-subnav label="Keuangan" :items="[
    ['label' => 'Ringkasan', 'concept' => 'wl', 'url' => route('user.money.index', ['section' => 'overview', 'month' => $month]), 'active' => request()->routeIs('user.money.index') && request('section', 'overview') === 'overview'],
    ['label' => 'Transaksi', 'icon' => 'keuangan', 'url' => route('user.money.index', ['section' => 'transactions', 'month' => $month]), 'active' => (request()->routeIs('user.money.*') && request('section') === 'transactions') || request()->routeIs('user.money.transactions.*')],
    ['label' => 'Dompet', 'icon' => 'keuangan', 'url' => route('user.money.index', ['section' => 'wallets', 'month' => $month]), 'active' => request()->routeIs('user.money.index') && request('section') === 'wallets'],
    ['label' => 'Tagihan', 'icon' => 'tagihan', 'url' => route('user.obligations.index', ['month' => $month]), 'active' => request()->routeIs('user.obligations.*')],
    ['label' => 'Utang-piutang', 'icon' => 'utang', 'url' => route('user.loans.index', ['month' => $month]), 'active' => request()->routeIs('user.loans.*')],
    ['label' => 'Laporan', 'concept' => 'rc', 'url' => route('user.reports.index', ['month' => $month]), 'active' => request()->routeIs('user.reports.*')],
]" />
