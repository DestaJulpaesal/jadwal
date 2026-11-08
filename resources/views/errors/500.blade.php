@extends('layouts.error')
@section('title', 'Terjadi kendala')
@section('code', '500')
@section('heading', 'Ada kendala saat memuat halaman')
@section('message', 'Perubahanmu mungkin belum tersimpan. Coba kembali beberapa saat lagi.')
@section('retry')
    @if (request()->attributes->get('request_id'))
        <p class="mt-4 text-sm text-muted">Kode referensi: <code>{{ request()->attributes->get('request_id') }}</code></p>
    @endif
@endsection
