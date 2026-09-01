<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Tugas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow">
            <form action="{{ route('admin.tasks.store') }}" method="POST">
                @csrf

                <div class="mb-4">
                    <label for="title" class="block font-medium text-sm text-gray-700">Judul</label>
                    <input type="text" name="title" class="form-input rounded w-full" required>
                </div>

                <div class="mb-4">
                    <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi</label>
                    <textarea name="description" class="form-textarea rounded w-full"></textarea>
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block font-medium text-sm text-gray-700">Kategori</label>
                    <select name="category_id" class="form-select rounded w-full">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="task_status_id" class="block font-medium text-sm text-gray-700">Status</label>
                    <select name="task_status_id" class="form-select rounded w-full">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label for="due_date" class="block font-medium text-sm text-gray-700">Tanggal Jatuh Tempo</label>
                    <input type="date" name="due_date" class="form-input rounded w-full">
                </div>

                <div class="mb-4">
                    <label class="block font-medium text-sm text-gray-700">Tag</label>
                    <div class="space-y-1">
                        @foreach ($tags as $tag)
                            <label class="flex items-center">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" class="mr-2">
                                {{ $tag->name }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Simpan</button>
            </form>
        </div>
    </div>
</x-app-layout>
