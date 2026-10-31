<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Daftar Tugas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ session('success') }}
                </div>
            @endif

            <a href="{{ route('admin.tasks.create') }}" class="mb-4 inline-block px-4 py-2 bg-blue-600 text-white rounded">Tambah Tugas</a>

            <div class="bg-white shadow-sm rounded-lg p-6">
                <table class="table-auto w-full">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Judul</th>
                            <th class="px-4 py-2">Kategori</th>
                            <th class="px-4 py-2">Status</th>
                            <th class="px-4 py-2">Jatuh Tempo</th>
                            <th class="px-4 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tasks as $task)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $task->title }}</td>
                                <td class="px-4 py-2">{{ $task->category->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $task->status->name ?? '-' }}</td>
                                <td class="px-4 py-2">{{ $task->due_date ?? '-' }}</td>
                                <td class="px-4 py-2 space-x-2">
                                    <a href="{{ route('admin.tasks.show', $task) }}" class="text-blue-500">Lihat</a>
                                    <a href="{{ route('admin.tasks.edit', $task) }}" class="text-yellow-500">Edit</a>
                                    <form action="{{ route('admin.tasks.destroy', $task) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
