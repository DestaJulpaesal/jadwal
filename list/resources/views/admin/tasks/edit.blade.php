<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Tugas
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 bg-white p-6 rounded shadow">
            <form action="{{ route('admin.tasks.update', $task->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label for="title" class="block font-medium text-sm text-gray-700">Judul</label>
                    <input type="text" name="title" class="form-input rounded w-full" value="{{ old('title', $task->title) }}" required>
                    @error('title') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="description" class="block font-medium text-sm text-gray-700">Deskripsi</label>
                    <textarea name="description" class="form-textarea rounded w-full">{{ old('description', $task->description) }}</textarea>
                    @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block font-medium text-sm text-gray-700">Kategori</label>
                    <select name="category_id" class="form-select rounded w-full">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $task->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="task_status_id" class="block font-medium text-sm text-gray-700">Status</label>
                    <select name="task_status_id" class="form-select rounded w-full">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->id }}" {{ $task->task_status_id == $status->id ? 'selected' : '' }}>{{ $status->name }}</option>
                        @endforeach
                    </select>
                    @error('task_status_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="due_date" class="block font-medium text-sm text-gray-700">Tanggal Jatuh Tempo</label>
                    <input type="date" name="due_date" class="form-input rounded w-full" value="{{ old('due_date', $task->due_date) }}">
                    @error('due_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label class="block font-medium text-sm text-gray-700">Tag</label>
                    <div class="space-y-1">
                        @foreach ($tags as $tag)
                            <label class="flex items-center">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}"
                                    {{ $task->tags->contains($tag->id) ? 'checked' : '' }}
                                    class="mr-2">
                                {{ $tag->name }}
                            </label>
                        @endforeach
                    </div>
                    @error('tags') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Perbarui</button>
                <a href="{{ route('admin.tasks.index') }}" class="ml-2 text-gray-600 hover:underline">Batal</a>
            </form>
        </div>
    </div>
</x-app-layout>
