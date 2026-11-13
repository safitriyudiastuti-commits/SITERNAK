@extends('layouts.buyer')

@section('title', 'Cara Memesan')

@section('content')
@php
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';
    $langkah = [
        ['cart',  '1. Pilih ternak', 'Cari di katalog, lihat detail, foto, dan riwayat prestasi peternak.'],
        ['check', '2. Pesan & tunggu konfirmasi', 'Isi data pembeli lalu buat pesanan. Peternak mengonfirmasi stok.'],
        ['card',  '3. Bayar', 'Transfer bank, QRIS, atau COD (khusus satu kota/kabupaten).'],
        ['box',   '4. Terima & beri ulasan', 'Setelah selesai, nilai peternak untuk membantu pembeli lain.'],
    ];
@endphp

<div class="mx-auto max-w-[1200px]">
    <nav class="text-xs text-[#6B7265]">
        <a href="{{ $ke('buyer.beranda') }}" class="hover:underline">Beranda</a>
        <span class="mx-2">›</span><span class="font-semibold text-[#1C2118]">Cara Memesan</span>
    </nav>
    <h1 class="mt-3 text-[34px] font-extrabold text-[#1C2118]">Cara memesan di SITERNAK</h1>
    <p class="mt-1 text-sm text-[#6B7265]">Empat langkah dari memilih ternak sampai barang diterima.</p>

    <div class="mt-6 grid grid-cols-2 gap-5">
        @foreach ($langkah as [$ikon, $judul, $isi])
            <div class="flex min-h-[150px] items-start gap-4 rounded-2xl border border-[#E4E4DC] bg-white p-6">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#E9F1E6] text-[#2E5B2A]"><x-ikon :nama="$ikon" class="h-5 w-5" /></span>
                <div>
                    <p class="text-lg font-extrabold text-[#1C2118]">{{ $judul }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-[#6B7265]">{{ $isi }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-5 flex items-start gap-4 rounded-2xl border border-[#E4E4DC] bg-white p-6">
        <x-ikon nama="info" class="mt-0.5 h-5 w-5 shrink-0 text-[#2E5B2A]" />
        <div class="text-sm">
            <p class="font-bold text-[#1C2118]">COD hanya tersedia jika kota pembeli sama dengan kota peternak.</p>
            <p class="mt-0.5 text-[#6B7265]">Untuk pembelian lintas kota, gunakan transfer bank atau QRIS. Status pembayaran berubah otomatis setelah diverifikasi.</p>
        </div>
    </div>

    <div class="mt-6 flex gap-3">
        <a href="{{ $ke('buyer.katalog.index') }}" class="flex h-12 items-center rounded-lg bg-[#2E5B2A] px-7 text-sm font-bold text-white hover:bg-[#1F421C]">Jelajahi Katalog</a>
        <a href="{{ $ke('buyer.pesanan.index') }}" class="flex h-12 items-center rounded-lg border border-[#2E5B2A] bg-white px-7 text-sm font-bold text-[#2E5B2A] hover:bg-[#E9F1E6]">Lihat Pesanan Saya</a>
    </div>
</div>
@endsection
