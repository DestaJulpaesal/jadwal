@extends('layouts.user')

@section('title', 'Ubah kebiasaan')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('user.habits.index') }}" class="habit-form-back">← Kembali ke kebiasaan</a>
        <div class="surface-card habit-form-shell mt-4 p-5 sm:p-8">
            <div class="habit-form-heading">
                <span class="habit-form-icon"><x-concept-icon name="fl" class="h-8 w-8" /></span>
                <div><p class="k">Perbarui ritmemu</p><h1>Ubah kebiasaan</h1><p class="habit-form-subtitle">Sesuaikan jadwal dan target agar tetap realistis.</p></div>
            </div>
            @if ($errors->any())<x-alert type="danger" class="mb-5"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
            <form method="POST" action="{{ route('user.habits.update', $habit) }}" class="habit-form">
                @csrf
                @method('PUT')
                @include('user.habits.form')
                <button class="btn-primary habit-form-submit w-full sm:w-auto">Simpan perubahan</button>
            </form>
        </div>
    </div>
@endsection
