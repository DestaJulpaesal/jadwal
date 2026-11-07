@extends('layouts.error')
@section('title', 'Terlalu banyak permintaan')
@section('code', '429')
@section('heading', 'Tunggu sebentar sebelum mencoba lagi')
@section('message', 'Kami menerima terlalu banyak permintaan dalam waktu singkat. Silakan tunggu sebentar, lalu coba kembali.')
@section('retry')
    <p data-retry-countdown data-retry-seconds="{{ (int) request()->header('Retry-After', 0) }}" class="mt-2 text-sm text-muted"></p>
@endsection
