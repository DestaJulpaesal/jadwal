<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail Tugas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow">
            <p><strong>Judul:</strong> {{ $task->title }}</p>
            <p><strong>Deskripsi:</strong> {{ $task->description }}</p>
            <p><strong>Kategori:</strong> {{ $task->category->name ?? '-' }}</p>
            <p><strong>Status:</strong> {{ $task->status->name ?? '-' }}</p>
            <p><strong>Tanggal Jatuh Tempo:</strong> {{ $task->due_date }}</p>
            <p><strong>Tag:</strong>
                @foreach ($task->tags as $tag)
                    <span class="inline-block px-2 py-1 bg-gray-200 rounded text-sm">{{ $tag->name }}</span>
                @endforeach
            </p>

            <a href="{{ route('admin.tasks.index') }}" class="mt-4 inline-block text-blue-600">← Kembali ke daftar</a>
        </div>
    </div>
</x-app-layout>
