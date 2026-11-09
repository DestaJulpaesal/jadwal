@extends('layouts.user')

@section('title', 'Kalender')

@section('content')
    <div class="dashboard-page mx-auto max-w-6xl">
        <x-page-header title="Kalender" description="Lihat agenda tugas, kebiasaan, dan target berdasarkan tanggal." />
        @include('user.partials.home-subnav')
        @include('user.partials.calendar-widget')
    </div>
@endsection
