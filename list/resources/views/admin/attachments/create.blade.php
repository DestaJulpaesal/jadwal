<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Tambah Lampiran Tugas</h2>
    </x-slot>

    <div class="py-4">
        <form action="{{ route('admin.attachments.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="task_id" class="form-label">Tugas</label>
                <select name="task_id" id="task_id" class="form-control" required>
                    <option value="">-- Pilih Tugas --</option>
                    @foreach ($tasks as $task)
                        <option value="{{ $task->id }}">{{ $task->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label for="file" class="form-label">File Lampiran</label>
                <input type="file" name="file" id="file" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</x-app-layout>
