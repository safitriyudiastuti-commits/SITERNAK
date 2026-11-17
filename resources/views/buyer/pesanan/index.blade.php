@extends('layouts.buyer')

@section('title', 'Pesanan Saya')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800">
            Pesanan Saya
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Pantau dan lihat perkembangan pesanan ternak Anda.
        </p>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Filter Status --}}
    @php
        $filters = [
            '' => 'Semua',
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            'diproses' => 'Diproses',
            'dikirim' => 'Dikirim',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];
    @endphp

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach ($filters as $value => $label)
            <a
                href="{{ route('buyer.pesanan.index', $value ? ['status' => $value] : []) }}"
                class="rounded-full border px-4 py-2 text-sm font-medium transition
                    {{ request('status', '') === $value
                        ? 'border-slate-800 bg-slate-800 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-400' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Daftar Pesanan --}}
    @forelse ($pesanan as $item)
        <div class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <p class="text-xs text-slate-400">Nomor Pesanan</p>
                    <p class="mt-1 font-semibold text-slate-800">
                        {{ $item->nomor_order }}
                    </p>
                </div>

                @php
                    $statusStyle = [
                        'menunggu_konfirmasi' => 'bg-amber-50 text-amber-700',
                        'diproses' => 'bg-blue-50 text-blue-700',
                        'dikirim' => 'bg-indigo-50 text-indigo-700',
                        'selesai' => 'bg-green-50 text-green-700',
                        'dibatalkan' => 'bg-red-50 text-red-700',
                    ];

                    $statusLabel = [
                        'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
                        'diproses' => 'Diproses',
                        'dikirim' => 'Dikirim',
                        'selesai' => 'Selesai',
                        'dibatalkan' => 'Dibatalkan',
                    ];
                @endphp

                <span class="rounded-full px-3 py-1.5 text-xs font-semibold
                    {{ $statusStyle[$item->status] ?? 'bg-slate-100 text-slate-600' }}">
                    {{ $statusLabel[$item->status] ?? ucfirst($item->status) }}
                </span>
            </div>

            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-800">
                        {{ $item->nama_item }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ number_format((float) $item->jumlah, 2, ',', '.') }}
                        {{ $item->satuan ?? 'ekor' }}
                        × Rp{{ number_format((float) $item->harga_satuan, 0, ',', '.') }}
                    </p>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ $item->created_at?->format('d M Y, H:i') }}
                    </p>
                </div>

                <div class="flex flex-col gap-3 sm:items-end">
                    <div>
                        <p class="text-xs text-slate-400">Total Pesanan</p>
                        <p class="font-bold text-slate-800">
                            Rp{{ number_format((float) $item->total_harga, 0, ',', '.') }}
                        </p>
                    </div>

                    <a
                        href="{{ route('buyer.pesanan.show', $item->id) }}"
                        class="rounded-xl bg-slate-800 px-5 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-700"
                    >
                        Lacak Pesanan
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
            <div class="mb-3 text-4xl">📦</div>

            <h2 class="font-semibold text-slate-800">
                Belum Ada Pesanan
            </h2>

            <p class="mt-2 text-sm text-slate-500">
                Pesanan Anda akan muncul di halaman ini.
            </p>

            <a
                href="{{ route('buyer.katalog.index') }}"
                class="mt-5 inline-block rounded-xl bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
            >
                Lihat Katalog
            </a>
        </div>
    @endforelse

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $pesanan->links() }}
    </div>

</div>
@endsection
