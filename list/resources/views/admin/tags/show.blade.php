<x-app-layout>
    <x-slot name="header">
        <h1>Detail Tag</h1>
    </x-slot>

    <div class="container mt-4">
        <div class="mb-3">
            <strong>Nama Tag:</strong>
            <p>{{ $tag->name }}</p>
        </div>

        <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">Kembali</a>
    </div>
</x-app-layout>
