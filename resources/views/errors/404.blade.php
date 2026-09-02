@extends('layouts.public')
@section('title', 'Halaman tidak ditemukan — Radar Redaksi')
@section('content')
<section class="site-container grid min-h-[55vh] place-items-center py-16 text-center"><div><span class="font-display text-8xl font-bold text-brand">404</span><h1 class="mt-3 font-display text-4xl font-bold">Halaman tidak ditemukan</h1><p class="mx-auto mt-3 max-w-lg text-muted">Alamat mungkin berubah atau konten sudah tidak tersedia. Kembali ke beranda untuk membaca kabar terbaru.</p><a href="{{ route('home') }}" class="btn-primary mt-6">Kembali ke beranda</a></div></section>
@endsection
