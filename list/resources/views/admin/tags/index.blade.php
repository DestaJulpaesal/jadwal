<x-app-layout>
    <x-slot name="header">
        <h1>Daftar Tag</h1>
    </x-slot>

    <div class="container mt-4">
        <a href="{{ route('admin.tags.create') }}" class="btn btn-primary mb-3">+ Tambah Tag</a>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tags as $tag)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $tag->name }}</td>
                        <td>
                            <a href="{{ route('admin.tags.edit', $tag) }}" class="btn btn-sm btn-warning">Edit</a>
                            <form action="{{ route('admin.tags.destroy', $tag) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin hapus tag ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
