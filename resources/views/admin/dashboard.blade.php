@extends('layouts.admin')

@section('title', 'Ringkasan akun')
@section('heading', 'Ringkasan akun')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Panel admin</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Ringkasan akun</h1>
        <p class="mt-2 text-muted">Pantau akun dan kelola akses pengguna.</p>
    </div>

    <section class="mb-6 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan jumlah akun">
        <article class="surface-card p-5">
            <p class="text-sm text-muted">Semua akun</p>
            <p class="mt-2 text-3xl font-semibold">{{ $accountTotals['total'] }}</p>
        </article>
        <article class="surface-card p-5">
            <p class="text-sm text-muted">Akun aktif</p>
            <p class="mt-2 text-3xl font-semibold text-success">{{ $accountTotals['active'] }}</p>
        </article>
        <article class="surface-card p-5">
            <p class="text-sm text-muted">Akun nonaktif</p>
            <p class="mt-2 text-3xl font-semibold text-muted">{{ $accountTotals['inactive'] }}</p>
        </article>
    </section>

    @include('admin.users.index')
@endsection
