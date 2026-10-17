@extends('layouts.buyer')

@section('title', 'Pesanan Saya')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');
    $status = [
        'menunggu_konfirmasi'       => ['Menunggu konfirmasi', 'bg-amber-100 text-amber-800'],
        'dikonfirmasi'              => ['Dikonfirmasi', 'bg-blue-100 text-blue-800'],
        'menunggu_pembayaran'       => ['Menunggu pembayaran', 'bg-blue-100 text-blue-800'],
        'menunggu_verifikasi_bayar' => ['Menunggu verifikasi bayar', 'bg-indigo-100 text-indigo-800'],
        'lunas'                     => ['Lunas', 'bg-green-100 text-green-800'],
        'disiapkan'                 => ['Disiapkan', 'bg-green-100 text-green-800'],
        'dikirim'                   => ['Dikirim', 'bg-green-100 text-green-800'],
        'diambil'                   => ['Diambil', 'bg-green-100 text-green-800'],
        'selesai'                   => ['Selesai', 'bg-emerald-100 text-emerald-800'],
        'dibatalkan'                => ['Dibatalkan', 'bg-red-100 text-red-800'],
        'ditolak'                   => ['Ditolak', 'bg-red-100 text-red-800'],
    ];
    $metode = ['transfer_bank' => 'Transfer Bank', 'qris' => 'QRIS', 'cod' => 'COD'];
@endphp

<div class="mx-auto max-w-5xl">
    <h1 class="text-3xl font-extrabold text-[#1C2118]">Pesanan Saya</h1>
    <p class="mt-1 text-sm text-[#6B7265]">Pantau status pesanan dan lanjutkan pembayaran.</p>

    @if (session('success') || session('error') || session('info'))
        <div class="mt-4 rounded-lg border px-4 py-3 text-sm {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' }}">
            {{ session('success') ?? session('error') ?? session('info') }}
        </div>
    @endif

    {{-- Tab status --}}
    <div class="mt-5 flex flex-wrap gap-2">
        <a href="{{ route('buyer.pesanan.index') }}"
           class="rounded-full border px-4 py-1.5 text-sm font-semibold {{ $tabAktif === null ? 'border-[#2E5B2A] bg-[#2E5B2A] text-white' : 'border-[#E4E4DC] bg-white text-[#4B5245] hover:border-[#2E5B2A]' }}">
            Semua <span class="ml-1 opacity-70">{{ $totalSemua }}</span>
        </a>
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('buyer.pesanan.index', ['status' => $key]) }}"
               class="rounded-full border px-4 py-1.5 text-sm font-semibold {{ $tabAktif === $key ? 'border-[#2E5B2A] bg-[#2E5B2A] text-white' : 'border-[#E4E4DC] bg-white text-[#4B5245] hover:border-[#2E5B2A]' }}">
                {{ $tab['label'] }} <span class="ml-1 opacity-70">{{ $tab['jumlah'] }}</span>
            </a>
        @endforeach
    </div>

    @forelse ($orders as $order)
        @php
            [$label, $warna] = $status[$order->status] ?? [\Illuminate\Support\Str::headline($order->status), 'bg-gray-100 text-gray-700'];
            $bisaBayar = in_array($order->status, ['dikonfirmasi', 'menunggu_pembayaran'], true) && $order->metode_bayar !== 'cod';
        @endphp
        <div class="mt-4 rounded-2xl border border-[#E4E4DC] bg-white p-5">
            <div class="flex items-center justify-between">
                <div class="text-sm">
                    <a href="{{ route('buyer.pesanan.show', $order) }}" class="font-bold text-[#1C2118] hover:underline">{{ $order->nomor_order }}</a>
                    <span class="ml-2 text-[#6B7265]">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-[#F6F6F2] px-2.5 py-0.5 text-[11px] font-semibold text-[#4B5245]">{{ $metode[$order->metode_bayar] ?? $order->metode_bayar }}</span>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $warna }}">{{ $label }}</span>
                </div>
            </div>

            <div class="mt-3 flex items-end justify-between">
                <div>
                    <p class="font-semibold text-[#1C2118]">{{ $order->nama_item }}</p>
                    <p class="text-sm text-[#6B7265]">
                        {{ $order->peternak->nama_usaha ?? '-' }} · {{ $angka($order->jumlah) }} {{ $order->satuan }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-[#6B7265]">Total</p>
                    <p class="text-lg font-extrabold text-[#2E5B2A]">{{ $rp($order->total_harga) }}</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2 border-t border-[#EDEDE6] pt-3 text-sm">
                @if ($bisaBayar)
                    <a href="{{ route('buyer.pembayaran.show', $order) }}" class="rounded-lg bg-[#2E5B2A] px-4 py-2 font-semibold text-white hover:bg-[#1F421C]">
                        {{ $order->metode_bayar === 'qris' ? 'Bayar dengan QRIS' : 'Unggah Bukti Transfer' }}
                    </a>
                @endif
                @if ($order->status === 'selesai' && ! $order->review)
                    <a href="{{ route('buyer.ulasan.create', $order) }}" class="rounded-lg border border-[#2E5B2A] px-4 py-2 font-semibold text-[#2E5B2A] hover:bg-[#E9F1E6]">Beri Ulasan</a>
                @endif
                <a href="{{ route('buyer.pesanan.show', $order) }}" class="rounded-lg border border-[#E4E4DC] px-4 py-2 font-semibold text-[#4B5245] hover:border-[#2E5B2A]">Lihat Detail</a>
            </div>
        </div>
    @empty
        <div class="mt-6 rounded-2xl border border-dashed border-[#E4E4DC] bg-white p-12 text-center">
            <p class="font-semibold text-[#1C2118]">Belum ada pesanan{{ $tabAktif ? ' pada status ini' : '' }}.</p>
            <a href="{{ route('buyer.katalog.index') }}" class="mt-4 inline-block rounded-lg bg-[#2E5B2A] px-4 py-2 text-sm font-semibold text-white">Jelajahi Katalog</a>
        </div>
    @endforelse

    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection