@extends('layouts.buyer')

@section('title', 'Detail Pesanan')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

    <a href="{{ route('buyer.pesanan.index') }}"
       class="mb-6 inline-flex text-sm font-medium text-slate-500 hover:text-slate-800">
        ← Kembali ke Pesanan Saya
    </a>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">
            Detail Pesanan
        </h1>
        <p class="mt-2 text-sm text-slate-500">
            Nomor pesanan: {{ $pesanan->nomor_order }}
        </p>
    </div>

    {{-- Status --}}
    @php
        $statusLabel = [
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            'diproses' => 'Sedang Diproses',
            'dikirim' => 'Sedang Dikirim',
            'selesai' => 'Pesanan Selesai',
            'dibatalkan' => 'Pesanan Dibatalkan',
        ];

        $statusSaatIni = $statusLabel[$pesanan->status] ?? ucfirst($pesanan->status);

        $timeline = [
            'menunggu_konfirmasi' => 'Pesanan Dibuat',
            'diproses' => 'Pesanan Diproses',
            'dikirim' => 'Pesanan Dikirim',
            'selesai' => 'Pesanan Diterima',
        ];
    @endphp

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Status saat ini</p>
        <h2 class="mt-2 text-xl font-bold text-slate-800">
            {{ $statusSaatIni }}
        </h2>
    </div>

    {{-- Timeline --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-6 text-lg font-bold text-slate-800">
            Perjalanan Pesanan
        </h2>

        @forelse ($pesanan->statusHistories as $riwayat)
            <div class="relative flex gap-4 pb-7 last:pb-0">
                <div class="flex flex-col items-center">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-600">
                        ✓
                    </div>

                    @if (!$loop->last)
                        <div class="mt-1 h-full w-0.5 bg-slate-200"></div>
                    @endif
                </div>

                <div class="pb-2">
                    <p class="font-semibold text-slate-800">
                        {{ $statusLabel[$riwayat->status] ?? ucfirst($riwayat->status) }}
                    </p>

                    @if ($riwayat->keterangan)
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $riwayat->keterangan }}
                        </p>
                    @endif

                    <p class="mt-1 text-xs text-slate-400">
                        {{ $riwayat->created_at?->format('d M Y, H:i') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">
                Belum ada riwayat perubahan status pesanan.
            </p>
        @endforelse
    </div>

    {{-- Informasi Barang --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-slate-800">
            Informasi Pesanan
        </h2>

        <p class="font-semibold text-slate-800">
            {{ $pesanan->nama_item }}
        </p>

        <div class="mt-4 space-y-2 text-sm text-slate-600">
            <div class="flex justify-between gap-4">
                <span>Jumlah</span>
                <span>{{ number_format((float) $pesanan->jumlah, 2, ',', '.') }} {{ $pesanan->satuan ?? 'ekor' }}</span>
            </div>

            <div class="flex justify-between gap-4">
                <span>Harga satuan</span>
                <span>Rp{{ number_format((float) $pesanan->harga_satuan, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between gap-4 border-t pt-3 font-bold text-slate-800">
                <span>Total</span>
                <span>Rp{{ number_format((float) $pesanan->total_harga, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Informasi Penerima --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-slate-800">
            Informasi Penerima
        </h2>

        <p class="font-semibold text-slate-800">
            {{ $pesanan->nama_penerima }}
        </p>

        <p class="mt-1 text-sm text-slate-500">
            {{ $pesanan->no_hp_penerima }}
        </p>

        <p class="mt-2 text-sm text-slate-500">
            {{ $pesanan->alamat_penerima }},
            {{ $pesanan->kota_kabupaten_penerima }},
            {{ $pesanan->provinsi_penerima }}
        </p>
    </div>

    {{-- Konfirmasi Diterima --}}
    @if ($pesanan->status === 'dikirim')
        <div class="rounded-2xl border border-green-200 bg-green-50 p-6">
            <h2 class="font-bold text-green-800">
                Apakah pesanan sudah diterima?
            </h2>

            <p class="mt-2 text-sm text-green-700">
                Konfirmasikan jika ternak sudah sampai dan diterima dengan baik.
            </p>

            <form
                action="{{ route('buyer.pesanan.konfirmasi', $pesanan->id) }}"
                method="POST"
                class="mt-4"
                onsubmit="return confirm('Yakin pesanan sudah diterima?')"
            >
                @csrf

                <button
                    type="submit"
                    class="rounded-xl bg-green-700 px-5 py-3 text-sm font-semibold text-white hover:bg-green-800"
                >
                    Konfirmasi Pesanan Diterima
                </button>
            </form>
        </div>
    @endif

    {{-- Ulasan --}}
    @if ($pesanan->status === 'selesai')
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-6">
            <h2 class="font-bold text-blue-800">
                Pesanan telah selesai
            </h2>

            <p class="mt-2 text-sm text-blue-700">
                Terima kasih telah berbelanja. Anda dapat memberikan ulasan setelah pesanan selesai.
            </p>
        </div>
    @endif

</div>
@endsection