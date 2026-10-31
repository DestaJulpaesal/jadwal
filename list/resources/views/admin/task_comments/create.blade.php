<x-app-layout>
    <x-slot name="header">Tambah Komentar Tugas</x-slot>

    <form action="{{ route('admin.task_comments.store') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="task_id">Tugas</label>
            <select name="task_id" class="form-control">
                @foreach ($tasks as $task)
                    <option value="{{ $task->id }}">{{ $task->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="user_id">User</label>
            <select name="user_id" class="form-control">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="comment">Komentar</label>
            <textarea name="comment" class="form-control" required></textarea>
        </div>

        <button class="btn btn-primary">Simpan</button>
    </form>
</x-app-layout>
