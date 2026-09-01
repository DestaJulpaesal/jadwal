<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Daftar Lampiran Tugas</h2>
    </x-slot>

    <div class="py-4">
        <a href="{{ route('admin.attachments.create') }}" class="btn btn-primary mb-3">+ Tambah Lampiran</a>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tugas</th>
                    <th>Nama File</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attachments as $attachment)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $attachment->task->title ?? '-' }}</td>
                        <td><a href="{{ Storage::url($attachment->file_path) }}" target="_blank">{{ $attachment->original_name }}</a></td>
                        <td>
                            <form action="{{ route('admin.attachments.destroy', $attachment) }}" method="POST" onsubmit="return confirm('Hapus lampiran ini?')" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
