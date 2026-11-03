@extends('layouts.user')

@section('title', 'Kalender')

@section('content')
    <div class="dashboard-page mx-auto max-w-6xl">
        @include('user.partials.calendar-widget')
    </div>
@endsection
