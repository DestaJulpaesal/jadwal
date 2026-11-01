@extends('layouts.user')

@section('title', 'Ubah target')

@section('content')
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('user.goals.index') }}" class="text-sm font-medium text-primary-800 underline">Kembali ke target</a>
        <section class="surface-card mt-4 p-5 sm:p-8">
            <h1 class="mb-6 text-2xl font-semibold">Ubah target</h1>
            @if ($errors->any())<x-alert type="danger" class="mb-5"><ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert>@endif
            <form method="POST" action="{{ route('user.goals.update', $goal) }}" class="space-y-4">
                @csrf
                @method('PUT')
                @include('user.goals.form')
                <button class="btn-primary">Simpan perubahan</button>
            </form>
        </section>
    </div>
@endsection
