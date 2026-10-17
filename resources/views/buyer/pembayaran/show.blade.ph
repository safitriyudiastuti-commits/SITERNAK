@extends('layouts.buyer')

@section('title', 'Pembayaran ' . $order->nomor_order)

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $qris = $order->metode_bayar === 'qris';
    $menungguVerifikasi = $order->status === 'menunggu_verifikasi_bayar';
    $payload = $qris && $pembayaran ? ($pembayaran->gateway_payload ?? []) : [];
    $ditolak = ! $qris && $pembayaran && $pembayaran->status === 'ditolak';
@endphp

<div class="mx-auto max-w-3xl">
    <nav class="text-xs text-[#6B7265]">
        <a href="{{ route('buyer.pesanan.index') }}" class="hover:underline">Pesanan Saya</a> <span class="mx-1">›</span>
        <a href="{{ route('buyer.pesanan.show', $order) }}" class="hover:underline">{{ $order->nomor_order }}</a> <span class="mx-1">›</span>
        <span class="font-semibold text-[#1C2118]">Pembayaran</span>
    </nav>
    <h1 class="mt-3 text-3xl font-extrabold text-[#1C2118]">{{ $qris ? 'Bayar dengan QRIS' : 'Bayar via Transfer Bank' }}</h1>

    @if (session('success') || session('error') || session('info'))
        <div class="mt-4 rounded-lg border px-4 py-3 text-sm {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' }}">
            {{ session('success') ?? session('error') ?? session('info') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mt-5 rounded-2xl border border-[#E4E4DC] bg-white p-6">
        <div class="flex items-center justify-between text-sm">
            <span class="text-[#6B7265]">Total yang harus dibayar</span>
            <span class="text-2xl font-extrabold text-[#2E5B2A]" id="nominal">{{ $rp($order->total_harga) }}</span>
        </div>
        <p class="mt-1 text-xs text-[#6B7265]">{{ $order->nama_item }} · {{ $order->peternak->nama_usaha ?? '' }}</p>
    </section>

    @if ($qris)
        {{-- ===== QRIS ===== --}}
        <section class="mt-5 rounded-2xl border border-[#E4E4DC] bg-white p-6 text-center">
            @if ($qrisAktif)
                @if (! empty($payload['qr_url']))
                    <img src="{{ $payload['qr_url'] }}" alt="Kode QRIS" class="mx-auto h-60 w-60">
                @else
                    <div class="mx-auto flex h-60 w-60 flex-col items-center justify-center rounded-xl border-2 border-dashed border-[#C9D3C3] p-4 text-xs text-[#6B7265]">
                        <b class="mb-1 text-sm text-[#1C2118]">QRIS (mode simulasi)</b>
                        <span class="break-all">{{ $payload['qr_string'] ?? '' }}</span>
                    </div>
                @endif
                <p class="mt-4 text-sm text-[#4B5245]">Scan dengan aplikasi bank atau e-wallet. Halaman ini otomatis berubah saat pembayaran berhasil.</p>
                <p class="mt-2 text-sm">Berlaku <b id="hitung" class="text-[#B45309]">--:--</b></p>
            @else
                <p class="font-bold text-[#1C2118]">Kode QRIS sudah kedaluwarsa.</p>
                <p class="mt-1 text-sm text-[#6B7265]">Buat kode baru untuk melanjutkan pembayaran.</p>
                <form method="POST" action="{{ route('buyer.pembayaran.qris-baru', $order) }}" class="mt-4">
                    @csrf
                    <button class="rounded-lg bg-[#2E5B2A] px-5 py-2.5 font-semibold text-white hover:bg-[#1F421C]">Buat Kode QRIS Baru</button>
                </form>
            @endif
        </section>

        @if ($qrisAktif)
            <script>
                (function () {
                    let sisa = {{ (int) $sisaDetik }};
                    const el = document.getElementById('hitung');
                    const tampil = () => {
                        el.textContent = String(Math.floor(sisa / 60)).padStart(2, '0') + ':' + String(sisa % 60).padStart(2, '0');
                    };
                    tampil();
                    setInterval(() => { sisa = Math.max(0, sisa - 1); tampil(); if (sisa === 0) location.reload(); }, 1000);

                    // Lunas masuk lewat webhook gateway, jadi halaman cukup memantau status.
                    setInterval(async () => {
                        try {
                            const r = await fetch(@json(route('buyer.pembayaran.status', $order)), { headers: { 'Accept': 'application/json' } });
                            const d = await r.json();
                            if (d.lunas) location.href = @json(route('buyer.pesanan.show', $order));
                        } catch (e) {}
                    }, 5000);
                })();
            </script>
        @endif
    @elseif ($menungguVerifikasi)
        {{-- ===== Transfer: bukti terkirim ===== --}}
        <section class="mt-5 rounded-2xl border border-indigo-200 bg-indigo-50 p-6 text-center">
            <p class="text-lg font-bold text-indigo-900">Bukti transfer terkirim</p>
            <p class="mt-1 text-sm text-indigo-800">Admin memverifikasi maksimal 1×24 jam. Anda akan menerima notifikasi setelah diverifikasi.</p>
            <a href="{{ route('buyer.pesanan.show', $order) }}" class="mt-4 inline-block rounded-lg bg-[#2E5B2A] px-5 py-2.5 text-sm font-semibold text-white">Lihat Pesanan</a>
        </section>
    @else
        {{-- ===== Transfer: form unggah ===== --}}
        @if ($ditolak)
            <div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                Bukti sebelumnya ditolak{{ $pembayaran->catatan_penolakan ? ': ' . $pembayaran->catatan_penolakan : '.' }} Silakan unggah bukti yang benar.
            </div>
        @endif

        <section class="mt-5 rounded-2xl border border-[#E4E4DC] bg-white p-6 text-sm">
            <h2 class="font-bold text-[#1C2118]">Rekening tujuan</h2>
            <dl class="mt-3 space-y-2">
                <div class="flex justify-between"><dt class="text-[#6B7265]">Bank</dt><dd class="font-semibold">{{ $rekening['bank'] }}</dd></div>
                <div class="flex justify-between"><dt class="text-[#6B7265]">Nomor rekening</dt>
                    <dd class="font-semibold">{{ $rekening['nomor'] }}
                        <button type="button" class="ml-2 text-xs text-[#2E5B2A] hover:underline" onclick="navigator.clipboard.writeText('{{ preg_replace('/\s+/', '', $rekening['nomor']) }}'); this.textContent='Tersalin'">Salin</button>
                    </dd></div>
                <div class="flex justify-between"><dt class="text-[#6B7265]">Atas nama</dt><dd class="font-semibold">{{ $rekening['atas_nama'] }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-[#6B7265]">Transfer sesuai nominal total. Dana ditampung platform dan diteruskan ke peternak setelah Anda menerima barang.</p>
        </section>

        <form method="POST" action="{{ route('buyer.pembayaran.upload', $order) }}" enctype="multipart/form-data"
              class="mt-5 rounded-2xl border border-[#E4E4DC] bg-white p-6">
            @csrf
            <h2 class="font-bold text-[#1C2118]">Unggah bukti transfer</h2>
            <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" required
                   class="mt-3 block w-full rounded-lg border border-dashed border-[#C9D3C3] p-4 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#E9F1E6] file:px-3 file:py-1.5 file:font-semibold file:text-[#2E5B2A]">
            <p class="mt-2 text-xs text-[#6B7265]">Format JPG, PNG, atau PDF, maksimal 5 MB.</p>
            <button class="mt-4 w-full rounded-lg bg-[#2E5B2A] py-3 font-semibold text-white hover:bg-[#1F421C]">Kirim Bukti Transfer</button>
        </form>
    @endif
</div>
@endsection