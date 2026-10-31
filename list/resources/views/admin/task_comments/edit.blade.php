<x-app-layout>
    <x-slot name="header">Edit Komentar</x-slot>

    <form action="{{ route('admin.task_comments.update', $task_comment) }}" method="POST">
        @csrf @method('PUT')

        <div class="mb-3">
            <label for="task_id">Tugas</label>
            <select name="task_id" class="form-control">
                @foreach ($tasks as $task)
                    <option value="{{ $task->id }}" {{ $task->id == $task_comment->task_id ? 'selected' : '' }}>{{ $task->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="user_id">User</label>
            <select name="user_id" class="form-control">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ $user->id == $task_comment->user_id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="comment">Komentar</label>
            <textarea name="comment" class="form-control" required>{{ $task_comment->comment }}</textarea>
        </div>

        <button class="btn btn-success">Update</button>
    </form>
</x-app-layout>
