<x-app-layout>
    <x-slot name="header">
        <h1>Edit Tag</h1>
    </x-slot>

    <div class="container mt-4">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.tags.update', $tag) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Nama Tag</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $tag->name) }}" required>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('admin.tags.index') }}" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</x-app-layout>
