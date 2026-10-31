<x-app-layout>
    <x-slot name="header">Daftar Komentar Tugas</x-slot>

    <a href="{{ route('admin.task_comments.create') }}" class="btn btn-primary mb-3">+ Tambah Komentar</a>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Tugas</th>
                <th>User</th>
                <th>Komentar</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($comments as $comment)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $comment->task->title ?? '-' }}</td>
                    <td>{{ $comment->user->name ?? '-' }}</td>
                    <td>{{ $comment->comment }}</td>
                    <td>
                        <a href="{{ route('admin.task_comments.edit', $comment) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('admin.task_comments.destroy', $comment) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus komentar ini?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-app-layout>
