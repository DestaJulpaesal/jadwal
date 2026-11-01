@extends('layouts.user')

@section('title', 'Ubah kebiasaan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('user.habits.index') }}" class="text-sm font-medium text-primary-800 underline">Kembali ke kebiasaan</a>
        <div class="surface-card mt-4 p-5 sm:p-8">
            <h1 class="mb-6 text-2xl font-semibold">Ubah kebiasaan</h1>
            @if ($errors->any())<x-alert type="danger" class="mb-5"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
            <form method="POST" action="{{ route('user.habits.update', $habit) }}" class="space-y-5">
                @csrf
                @method('PUT')
                @include('user.habits.form')
                <button class="btn-primary w-full sm:w-auto">Simpan perubahan</button>
            </form>
        </div>
    </div>
@endsection
