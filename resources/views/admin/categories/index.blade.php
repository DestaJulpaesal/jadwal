@extends('layouts.admin')

@section('title', 'Kategori')
@section('heading', 'Kategori')

@section('content')
    @php($creatingCategory = old('form_context') === 'category-create')
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Master data</p>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight">Kategori kebiasaan dan tugas</h1>
        <p class="mt-2 text-muted">Kelola pilihan kategori yang tersedia untuk semua pengguna.</p>
    </div>
    <x-error-summary :errors="$errors->messages()" title="Kategori belum disimpan. Periksa isian berikut:" />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <section class="surface-card overflow-hidden">
            <div class="border-b border-line p-5"><h2 class="font-semibold">Kategori tersedia</h2></div>
            <div class="divide-y divide-line">
                @forelse ($categories as $category)
                    @php($editingCategory = old('form_context') === 'category-update-'.$category->id)
                    <div class="flex flex-wrap items-center gap-3 p-5">
                        <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="min-w-0 flex-1 space-y-3" data-dirty-guard>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="form_context" value="category-update-{{ $category->id }}">
                            <x-field :id="$editingCategory ? 'name' : 'category-name-'.$category->id" label="Nama kategori" help="Nama ini tersedia untuk semua tugas dan kebiasaan." required>
                                <input id="{{ $editingCategory ? 'name' : 'category-name-'.$category->id }}" name="name" value="{{ $editingCategory ? old('name', $category->name) : $category->name }}" maxlength="60" required autocapitalize="words" class="form-control">
                            </x-field>
                            <x-category-color-picker :id="$editingCategory ? 'color' : 'category-color-'.$category->id"
                                :value="$editingCategory ? old('color', $category->displayColor()) : $category->displayColor()" />
                            <div class="flex flex-wrap gap-2">
                                <x-button type="reset" variant="secondary" icon="kembali">Batal</x-button>
                                <x-button type="submit" variant="primary" icon="selesai" loading-text="Menyimpan kategori…">Simpan</x-button>
                            </div>
                        </form>
                        <span class="text-xs text-muted">{{ $category->tasks_count + $category->habits_count }} dipakai</span>
                        <x-confirm-action :action="route('admin.categories.destroy', $category)" title="Hapus kategori?"
                            :message="'Kategori '.$category->name.' akan dihapus dari pilihan. Item yang sudah menggunakannya tidak ikut dihapus.'"
                            label="Hapus kategori" />
                    </div>
                @empty
                    <p class="p-5 text-sm text-muted">Belum ada kategori.</p>
                @endforelse
            </div>
        </section>
        <section class="surface-card h-fit p-5">
            <h2 class="font-semibold">Tambah kategori</h2>
            <p class="mt-1 text-sm text-muted">Kategori ini langsung tersedia untuk semua user.</p>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-5 space-y-4" data-dirty-guard>
                @csrf
                <input type="hidden" name="form_context" value="category-create">
                <x-field :id="$creatingCategory ? 'name' : 'new-category-name'" label="Nama kategori" help="Nama ini tersedia untuk semua tugas dan kebiasaan." required>
                    <input id="{{ $creatingCategory ? 'name' : 'new-category-name' }}" name="name" value="{{ $creatingCategory ? old('name') : '' }}" required maxlength="60" class="form-control" placeholder="Contoh: Kesehatan" autocapitalize="words">
                </x-field>
                <x-category-color-picker :id="$creatingCategory ? 'color' : 'new-category-color'"
                    :value="$creatingCategory ? old('color', '#3B82F6') : '#3B82F6'" />
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <x-button type="reset" variant="secondary" icon="kembali" class="w-full">Batal</x-button>
                    <x-button type="submit" variant="primary" icon="tambah" loading-text="Menyimpan kategori…" class="w-full">Tambah kategori</x-button>
                </div>
            </form>
        </section>
    </div>
@endsection
