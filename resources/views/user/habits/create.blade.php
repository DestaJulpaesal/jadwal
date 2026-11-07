@extends('layouts.user')

@section('title', 'Tambah kebiasaan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('user.habits.index') }}" class="habit-form-back">← Kembali ke kebiasaan</a>
        <div class="surface-card habit-form-shell mt-4 p-5 sm:p-8">
            <div class="habit-form-heading">
                <span class="habit-form-icon"><x-concept-icon name="fl" class="h-8 w-8" /></span>
                <div><p class="k">Mulai dari hal kecil</p><h1>Tambah kebiasaan</h1><p class="habit-form-subtitle">Atur ritmemu sekali, lalu cukup catat saat selesai.</p></div>
            </div>
            @if ($errors->any())<x-alert type="danger" class="mb-5"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
            <form method="POST" action="{{ route('user.habits.store') }}" class="habit-form">
                @csrf
                @include('user.habits.form')
                <x-button variant="primary" class="habit-form-submit w-full sm:w-auto">Simpan kebiasaan</x-button>
            </form>
        </div>
    </div>
@endsection
