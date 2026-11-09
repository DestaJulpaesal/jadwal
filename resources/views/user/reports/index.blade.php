@extends('layouts.user')

@section('title', 'Laporan bulanan')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <x-page-header eyebrow="Tinjauan keuangan" title="Laporan bulanan" description="Bandingkan pemasukan dan pengeluaran berdasarkan bulan.">
            <form method="GET" class="flex items-end gap-2" data-auto-submit>
                <div>
                    <label for="month" class="field-label">Bulan laporan</label>
                    <input id="month" name="month" type="month" value="{{ $month }}" class="form-control">
                </div>
                <x-button type="submit" variant="secondary" data-submit-default>Terapkan</x-button>
            </form>
        </x-page-header>
        @include('user.partials.finance-subnav')
        <section class="grid gap-3 sm:grid-cols-3">
            <article class="surface-card p-4"><p class="text-sm text-muted">Pemasukan</p><strong class="mt-1 block text-xl" data-private>{{ rupiah($current['income']) }}</strong></article>
            <article class="surface-card p-4"><p class="text-sm text-muted">Pengeluaran</p><strong class="mt-1 block text-xl" data-private>{{ rupiah($current['expense']) }}</strong></article>
            <article class="surface-card p-4"><p class="text-sm text-muted">Sisa</p><strong class="mt-1 block text-xl" data-private>{{ rupiah($current['net']) }}</strong></article>
        </section>
        <section class="surface-card p-5">
            <h2 class="text-lg font-semibold">Dibanding bulan lalu</h2>
            <p class="mt-2 text-sm text-muted">Pengeluaran bulan {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $previousMonth)->translatedFormat('F') }}: <strong data-private>{{ rupiah($previous['expense']) }}</strong></p>
            @if ($comparison === null)
                <p class="mt-1 text-sm text-muted">Belum ada data pengeluaran bulan lalu untuk dibandingkan.</p>
            @elseif ($comparison === 0)
                <p class="mt-1 text-sm">Pengeluaran sama seperti bulan lalu.</p>
            @else
                <p class="mt-1 text-sm">Pengeluaran {{ abs($comparison) }}% {{ $comparison > 0 ? 'lebih tinggi' : 'lebih rendah' }} dari bulan lalu.</p>
            @endif
        </section>
        <section class="surface-card p-5">
            <h2 class="text-lg font-semibold">Kategori pengeluaran terbesar</h2>
            @forelse ($categories as $category)
                <div class="mt-4">
                    <div class="mb-1 flex justify-between gap-3 text-sm"><span>{{ $category->category_name }}</span><strong data-private>{{ rupiah((int) $category->total) }}</strong></div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-label="Pengeluaran {{ $category->category_name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $largest ? (int) round($category->total * 100 / $largest) : 0 }}">
                        <div class="h-full rounded-full bg-primary-700" style="width: {{ $largest ? (int) round($category->total * 100 / $largest) : 0 }}%"></div>
                    </div>
                </div>
            @empty
                <p class="mt-3 text-sm text-muted">Belum ada pengeluaran berkategori pada bulan ini.</p>
                <x-button href="{{ route('user.money.index', ['section' => 'transactions', 'month' => $month, 'create' => 'transaction']) }}" variant="secondary" icon="tambah" class="mt-4">Catat pengeluaran pertama</x-button>
            @endforelse
        </section>
    </div>
@endsection
