@extends('layouts.user')

@section('content')
    <div class="max-w-2xl mx-auto rounded bg-white p-6 shadow">
        <h1 class="mb-6 text-2xl font-bold">Edit tugas</h1>
        <form method="POST" action="{{ route('user.tasks.update', $task) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('user.tasks.form')
            <div class="flex flex-col gap-3 sm:flex-row">
                <button class="min-h-11 rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Simpan perubahan</button>
                <a href="{{ route('user.tasks.index') }}" class="inline-flex min-h-11 items-center justify-center rounded border px-4 py-2">Batal</a>
            </div>
        </form>
    </div>
@endsection
