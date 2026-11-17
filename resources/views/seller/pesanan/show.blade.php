@extends('layouts.seller')

@section('title', 'Detail Pesanan')

@section('content')
@php
    $statusClasses = [
        'menunggu_konfirmasi' => 'bg-amber-100 text-amber-800',
        'dikonfirmasi' => 'bg-sky-100 text-sky-800',
        'diproses' => 'bg-blue-100 text-blue-800',
        'dikirim' => 'bg-indigo-100 text-indigo-800',
        'selesai' => 'bg-emerald-100 text-emerald-800',
        'ditolak' => 'bg-red-100 text-red-800',
        'dibatalkan' => 'bg-gray-100 text-gray-700',
    ];
    $paymentLabels = [
        'belum' => 'Belum',
        'menunggu_verifikasi' => 'Menunggu Verifikasi',
        'sah' => 'Sah',
    ];
    $paymentClasses = [
        'belum' => 'bg-gray-100 text-gray-700',
        'menunggu_verifikasi' => 'bg-amber-100 text-amber-800',
        'sah' => 'bg-emerald-100 text-emerald-800',
    ];
@endphp

<div class="mx-auto max-w-5xl space-y-5">
    <a href="{{ route('seller.pesanan.index', request()->query()) }}" class="inline-flex text-sm font-medium text-gray-500 hover:text-gray-900">← Kembali ke Pesanan Masuk</a>

    <header>
        <p class="mb-2 text-xs text-gray-500">Detail pesanan</p>
        <h1 class="text-2xl font-bold text-gray-900">{{ $order->nomor_order }}</h1>
        <p class="mt-1 text-sm text-gray-500">Dibuat {{ $order->created_at?->format('d M Y, H:i') }}</p>
    </header>

    <section class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Status Pesanan</p>
            <span class="mt-3 inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
            </span>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Status Bayar</p>
            <span class="mt-3 inline-flex rounded-full px-3 py-1.5 text-xs font-semibold {{ $paymentClasses[$order->status_bayar] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $paymentLabels[$order->status_bayar] ?? ucfirst($order->status_bayar ?? '-') }}
            </span>
            <p class="mt-2 text-xs text-gray-500">Metode: {{ str_replace('_', ' ', $order->metode_bayar) }}</p>
        </div>
    </section>

    @if ($order->status === 'ditolak' && $order->alasan_penolakan)
        <section class="rounded-xl border border-red-200 bg-red-50 p-5">
            <h2 class="font-semibold text-red-900">Alasan penolakan</h2>
            <p class="mt-2 whitespace-pre-line text-sm text-red-800">{{ $order->alasan_penolakan }}</p>
        </section>
    @endif

    <section class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="text-base font-bold text-gray-900">Informasi barang</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-xs text-gray-500">Item</dt><dd class="mt-1 font-medium text-gray-900">{{ $order->nama_item }}</dd></div>
                @if ($order->livestockProduct?->commodityType)
                    <div><dt class="text-xs text-gray-500">Jenis produk</dt><dd class="mt-1 text-gray-800">{{ $order->livestockProduct->commodityType->name }}</dd></div>
                @elseif ($order->livestock)
                    <div><dt class="text-xs text-gray-500">Data ternak</dt><dd class="mt-1 text-gray-800">{{ $order->livestock->nama_kode ?: $order->livestock->commodityType?->name }}</dd></div>
                @endif
                <div><dt class="text-xs text-gray-500">Jumlah</dt><dd class="mt-1 text-gray-800">{{ number_format((float) $order->jumlah, 2, ',', '.') }} {{ $order->satuan }}</dd></div>
                <div><dt class="text-xs text-gray-500">Harga satuan</dt><dd class="mt-1 text-gray-800">Rp{{ number_format((float) $order->harga_satuan, 0, ',', '.') }}</dd></div>
                <div><dt class="text-xs text-gray-500">Total</dt><dd class="mt-1 text-lg font-bold text-gray-900">Rp{{ number_format((float) $order->total_harga, 0, ',', '.') }}</dd></div>
            </dl>
        </div>

        <div class="space-y-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="text-base font-bold text-gray-900">Data pembeli</h2>
                <p class="mt-4 text-sm font-medium text-gray-900">{{ $order->buyer?->name ?? 'Pembeli' }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $order->buyer?->email }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $order->no_hp_penerima }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5">
                <h2 class="text-base font-bold text-gray-900">Alamat penerima</h2>
                <p class="mt-4 text-sm font-medium text-gray-900">{{ $order->nama_penerima }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $order->alamat_penerima }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $order->kota_kabupaten_penerima }}, {{ $order->provinsi_penerima }}</p>
                <p class="mt-1 text-sm text-gray-600">{{ $order->no_hp_penerima }}</p>
            </div>
        </div>
    </section>

    @if ($order->status === 'menunggu_konfirmasi')
        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="text-base font-bold text-gray-900">Tindakan pesanan</h2>
            <p class="mt-1 text-sm text-gray-500">Konfirmasi atau tolak pesanan ini. Keputusan hanya dapat dilakukan sekali.</p>
            <div class="mt-4 flex flex-wrap items-start gap-3">
                <form method="POST" action="{{ route('seller.pesanan.confirm', $order->id) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-[#2d5a27] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#234a1f]">
                        Konfirmasi Pesanan
                    </button>
                </form>

                <form method="POST" action="{{ route('seller.pesanan.reject', $order->id) }}" class="w-full max-w-xl space-y-2">
                    @csrf
                    <label for="alasan_penolakan" class="block text-sm font-medium text-gray-700">Alasan penolakan <span class="text-red-600">*</span></label>
                    <textarea id="alasan_penolakan" name="alasan_penolakan" rows="3" maxlength="1000" required
                              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">{{ old('alasan_penolakan') }}</textarea>
                    @error('alasan_penolakan')
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Tolak Pesanan
                    </button>
                </form>
            </div>
        </section>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="text-base font-bold text-gray-900">Riwayat status</h2>
        @forelse ($order->statusHistories as $history)
            <article class="mt-4 flex gap-3 border-l-2 border-[#dce8d7] pl-4">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="font-semibold text-gray-900">{{ $statusLabels[$history->status] ?? ucfirst($history->status) }}</span>
                        <span class="text-xs text-gray-500">{{ $history->created_at?->format('d M Y, H:i') }}</span>
                    </div>
                    @if ($history->keterangan)
                        <p class="mt-1 whitespace-pre-line text-sm text-gray-600">{{ $history->keterangan }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">{{ $history->actor?->name ?? 'Sistem' }}</p>
                </div>
            </article>
        @empty
            <p class="mt-3 text-sm text-gray-500">Belum ada riwayat status.</p>
        @endforelse
    </section>
</div>
@endsection
