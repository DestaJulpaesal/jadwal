@extends('layouts.user')

@section('title', 'Tambah tugas')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('user.tasks.index') }}" class="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-primary-800 underline-offset-4 hover:underline">Kembali ke daftar tugas</a>
        <div class="surface-card mt-3 p-5 sm:p-8">
            <header class="mb-6">
                <p class="text-sm font-semibold text-primary-800">Rencana baru</p>
                <h1 class="mt-1 text-2xl font-semibold">Tambah tugas</h1>
                <p class="mt-2 text-sm text-muted">Tambahkan detail yang membantu Anda mengingat rencana ini.</p>
            </header>
            @if ($errors->any())
                <x-alert type="danger" class="mb-5" tabindex="-1">
                    <p class="font-semibold">Tugas belum tersimpan. Periksa isian berikut:</p>
                    <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </x-alert>
            @endif
            <form method="POST" action="{{ route('user.tasks.store') }}" class="space-y-5">
                @csrf
                @include('user.tasks.form')
                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-button href="{{ route('user.tasks.index') }}" variant="secondary">Batal</x-button>
                    <x-button type="submit" data-loading-text="Menyimpan..." variant="primary"><x-concept-icon name="tk" class="h-5 w-5" />Simpan tugas</x-button>
                </div>
            </form>
        </div>
    </div>
@endsection
