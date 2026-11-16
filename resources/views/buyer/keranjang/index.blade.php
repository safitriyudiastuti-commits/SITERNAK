@extends('layouts.buyer')

@section('title', 'Keranjang Saya')

@section('content')
@php
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');
@endphp

{{-- Breadcrumb + judul --}}
<nav class="flex items-center text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">
    <a href="{{ $ke('buyer.beranda') }}" class="hover:underline">Beranda</a>
    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />
    <span class="font-semibold text-[#1C2118]">Keranjang Saya</span>
</nav>
<h1 class="mt-3 text-[1.75rem] font-extrabold leading-[2.125rem] text-[#1C2118]">Keranjang saya</h1>
<p class="mt-[0.4375rem] text-sm leading-5 text-[#6B7265]">Simpan batch dan hasil ternak sebelum memesan. Setiap barang dipesan terpisah.</p>

@if (session('success') || session('error'))
    <p class="mt-3 rounded-lg border px-3 py-2 text-[0.8125rem] {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-[#CFE3C9] bg-[#EEF6EB] text-[#1F421C]' }}">{{ session('success') ?? session('error') }}</p>
@endif

<div class="mt-6 grid grid-cols-[minmax(0,1fr)_17.75rem] items-start gap-6">
    <div class="space-y-5">
        @forelse ($grup as $penjualId => $baris)
            @php $pertama = $baris->first()->r; @endphp
            <section class="overflow-hidden rounded-[0.875rem] border border-[#E4E4DC] bg-white">
                {{-- Kepala peternak --}}
                <header class="flex h-[3.5rem] items-center gap-3 border-b border-[#EDEDE6] px-5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-[#D5E4CF] text-[0.76rem] font-bold text-[#2E5B2A]">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($pertama->penjual, 0, 2)) }}</span>
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-[#1C2118]">{{ $pertama->penjual }}</p>
                        <p class="mt-0.5 flex items-center gap-1 text-[0.71875rem] text-[#6B7265]"><x-ikon nama="pin" class="h-3 w-3" /> {{ $pertama->kota }}, {{ $pertama->provinsi }}</p>
                    </div>
                </header>

                {{-- Barang --}}
                <div class="divide-y divide-[#EDEDE6]">
                    @foreach ($baris as $x)
                        @php $r = $x->r; @endphp
                        <article class="flex items-center gap-4 px-5 py-4">
                            <x-ilustrasi :r="$r" class="h-[4.5rem] w-[4.5rem] shrink-0 rounded-xl" ikon="h-9 w-9" />

                            <div class="min-w-0 flex-1">
                                <a href="{{ $ke('buyer.katalog.show', [$r->tipe, $r->id]) }}" class="line-clamp-2 text-sm font-semibold leading-[1.1875rem] text-[#1C2118] hover:underline">{{ $r->nama }}</a>
                                <p class="mt-1 text-xs text-[#6B7265]">{{ $rp($r->harga) }} / {{ $r->satuan }} · Stok {{ $angka($r->stok) }}</p>
                                <form method="POST" action="{{ route('buyer.keranjang.hapus', $x->id) }}" class="mt-1.5">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-[#B42318] hover:underline">Hapus</button>
                                </form>
                            </div>

                            {{-- Jumlah (− n +) --}}
                            <div class="flex h-10 items-center rounded-[0.625rem] border border-[#E4E4DC] bg-white">
                                <form method="POST" action="{{ route('buyer.keranjang.ubah', $x->id) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="jumlah" value="{{ max(1, $x->jumlah - 1) }}">
                                    <button class="flex h-10 w-9 items-center justify-center text-lg text-[#4B5245] disabled:opacity-40" @disabled($x->jumlah <= 1) aria-label="Kurangi">−</button>
                                </form>
                                <span class="w-9 text-center text-sm font-semibold text-[#1C2118]">{{ $angka($x->jumlah) }}</span>
                                <form method="POST" action="{{ route('buyer.keranjang.ubah', $x->id) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="jumlah" value="{{ $x->jumlah + 1 }}">
                                    <button class="flex h-10 w-9 items-center justify-center text-lg text-[#4B5245] disabled:opacity-40" @disabled($x->jumlah >= $r->stok) aria-label="Tambah">+</button>
                                </form>
                            </div>

                            <div class="w-[8.5rem] text-right">
                                <p class="text-[1.0625rem] font-bold text-[#2E5B2A]">{{ $rp($x->subtotal) }}</p>
                                <a href="{{ $ke('buyer.pemesanan.create', ['tipe' => $r->tipe, 'item' => $r->id, 'jumlah' => $x->jumlah]) }}"
                                   class="mt-1.5 inline-flex h-8 items-center rounded-lg bg-[#2E5B2A] px-4 text-xs font-semibold text-white hover:bg-[#1F421C]">Pesan</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <footer class="flex h-12 items-center justify-between border-t border-[#EDEDE6] bg-[#FAFAF7] px-5 text-[0.8125rem]">
                    <span class="text-[#6B7265]">Subtotal {{ $baris->count() }} barang</span>
                    <span class="font-bold text-[#1C2118]">{{ $rp($baris->sum('subtotal')) }}</span>
                </footer>
            </section>
        @empty
            <div class="rounded-[0.875rem] border border-[#E4E4DC] bg-white px-8 py-14 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#E9F1E6] text-[#2E5B2A]"><x-ikon nama="cart" class="h-6 w-6" sw="1.8" /></span>
                <h2 class="mt-4 text-base font-bold text-[#1C2118]">Keranjang masih kosong</h2>
                <p class="mt-1 text-sm text-[#6B7265]">Telusuri katalog dan tekan ikon keranjang pada batch atau hasil ternak yang Anda minati.</p>
                <a href="{{ $ke('buyer.katalog.index') }}" class="mt-6 inline-flex h-11 items-center rounded-[0.625rem] bg-[#2E5B2A] px-6 text-sm font-semibold text-white hover:bg-[#1F421C]">Jelajahi Katalog</a>
            </div>
        @endforelse
    </div>

    {{-- Catatan --}}
    <aside class="space-y-4">
        <div class="rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <h2 class="text-base font-bold text-[#1C2118]">Ringkasan</h2>
            <p class="mt-3 flex justify-between text-[0.8125rem]"><span class="text-[#6B7265]">Jumlah barang</span><span class="font-semibold text-[#1C2118]">{{ $total }}</span></p>
            <p class="mt-1.5 flex justify-between text-[0.8125rem]"><span class="text-[#6B7265]">Jumlah peternak</span><span class="font-semibold text-[#1C2118]">{{ $grup->count() }}</span></p>
            <a href="{{ $ke('buyer.katalog.index') }}" class="mt-4 flex h-11 items-center justify-center rounded-[0.625rem] border border-[#2E5B2A] text-sm font-semibold text-[#2E5B2A] hover:bg-[#E9F1E6]">Lanjut belanja</a>
        </div>

        <div class="flex gap-3 rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5 text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">
            <x-ikon nama="info" class="mt-0.5 h-5 w-5 shrink-0 text-[#2E5B2A]" />
            <p>Ternak individual (satu ekor) tidak masuk keranjang karena langsung dikunci saat dipesan. Gunakan tombol <b class="text-[#1C2118]">Pesan Sekarang</b> di halaman detail.</p>
        </div>
    </aside>
</div>
@endsection
