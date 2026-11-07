@extends('layouts.admin')

@section('title', 'Kategori')
@section('heading', 'Kategori')

@section('content')
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Master data</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Kategori kebiasaan dan tugas</h1>
        <p class="mt-2 text-muted">Kelola pilihan kategori yang tersedia untuk semua pengguna.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="surface-card overflow-hidden">
            <div class="border-b border-line p-5"><h2 class="font-semibold">Kategori tersedia</h2></div>
            <div class="divide-y divide-line">
                @forelse ($categories as $category)
                    <div class="flex flex-wrap items-center gap-3 p-5">
                        <span class="h-4 w-4 rounded-full border border-ink" style="background: {{ $category->displayColor() }}"></span>
                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <input name="name" value="{{ $category->name }}" maxlength="60" class="form-control min-h-10 max-w-xs flex-1">
                            <input name="color" type="color" value="{{ $category->displayColor() }}" class="h-10 w-12 rounded-lg border border-line bg-white p-1">
                            <x-button variant="secondary" class="min-h-10 px-3">Simpan</x-button>
                        </form>
                        <span class="text-xs text-muted">{{ $category->tasks_count + $category->habits_count }} dipakai</span>
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">
                            @csrf
                            @method('DELETE')
                            <x-button variant="ghost" class="min-h-10 text-danger">Hapus</x-button>
                        </form>
                    </div>
                @empty
                    <p class="p-5 text-sm text-muted">Belum ada kategori.</p>
                @endforelse
            </div>
        </section>
        <section class="surface-card h-fit p-5">
            <h2 class="font-semibold">Tambah kategori</h2>
            <p class="mt-1 text-sm text-muted">Kategori ini langsung tersedia untuk semua user.</p>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-5 space-y-4">
                @csrf
                <div><label for="name" class="field-label">Nama kategori</label><input id="name" name="name" required maxlength="60" class="form-control" placeholder="Kesehatan"></div>
                <div><label for="color" class="field-label">Warna</label><input id="color" name="color" type="color" value="#3B82F6" class="h-11 w-20 rounded-lg border border-line bg-white p-1"></div>
                <x-button variant="primary" class="w-full">Tambah kategori</x-button>
            </form>
        </section>
    </div>
@endsection
