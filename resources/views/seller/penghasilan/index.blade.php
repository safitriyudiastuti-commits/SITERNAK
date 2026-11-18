@extends('layouts.seller')

@section('title', 'Penghasilan Saya')

@section('content')
    @php
        $rupiah = fn (int|float $amount) => 'Rp '.number_format($amount, 0, ',', '.');
        $highestWeek = $maxWeekly > 0 ? array_search($maxWeekly, $weekly, true) : null;
    @endphp
    <div class="mx-auto max-w-7xl space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <div class="mb-2 text-xs text-gray-500"><a href="{{ route('seller.dasbor') }}" class="hover:text-green-800">Toko Saya</a><span class="mx-2">›</span><span class="font-medium text-gray-700">Penghasilan Saya</span></div>
                <h1 class="text-2xl font-bold text-gray-900">Penghasilan saya</h1>
                <p class="mt-1 text-sm text-gray-600">Rincian pendapatan dari seluruh pesanan yang telah selesai.</p>
            </div>
            <a href="{{ route('seller.penghasilan.export', ['bulan' => $month->format('Y-m')]) }}" class="inline-flex items-center rounded-lg border border-green-800 bg-white px-4 py-2 text-sm font-semibold text-green-800 hover:bg-green-50">↓ <span class="ml-2">Ekspor Excel</span></a>
        </div>

        <form method="GET" action="{{ route('seller.penghasilan.index') }}">
            <label for="bulan" class="sr-only">Pilih bulan</label>
            <select id="bulan" name="bulan" onchange="this.form.submit()" class="min-w-52 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm">
                @foreach ($months as $option)
                    <option value="{{ $option->format('Y-m') }}" @selected($month->format('Y-m') === $option->format('Y-m'))>{{ $option->translatedFormat('F Y') }}</option>
                @endforeach
            </select>
        </form>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan penghasilan">
            <article class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-xs text-gray-600">Pendapatan kotor</p><p class="mt-2 text-xl font-bold text-gray-900">{{ $rupiah($summary['gross']) }}</p><p class="text-xs text-gray-500">{{ $summary['count'] }} pesanan selesai</p></article>
            <article class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-xs text-gray-600">Biaya layanan platform</p><p class="mt-2 text-xl font-bold text-gray-900">{{ $rupiah($summary['fee']) }}</p><p class="text-xs text-red-700">{{ rtrim(rtrim(number_format($feePercent, 2, ',', '.'), '0'), ',') }}% dari pendapatan kotor</p></article>
            <article class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-xs text-gray-600">Pendapatan bersih</p><p class="mt-2 text-xl font-bold text-gray-900">{{ $rupiah($summary['net']) }}</p>@if ($changePercent !== null)<p class="text-xs text-green-700">{{ $changePercent >= 0 ? '+' : '' }}{{ $changePercent }}% dari {{ $month->copy()->subMonthNoOverflow()->translatedFormat('F') }}</p>@endif</article>
            <article class="rounded-xl border border-gray-200 bg-white p-4"><p class="text-xs text-gray-600">Pesanan selesai</p><p class="mt-2 text-xl font-bold text-gray-900">{{ $summary['count'] }} pesanan</p>@if ($summary['count'] > 0)<p class="text-xs text-gray-500">Rata-rata {{ $rupiah($summary['average']) }}</p>@endif</article>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="font-semibold text-gray-900">Pendapatan bersih mingguan</h2>
            @if ($summary['count'] === 0)
                <div class="flex h-36 items-center justify-center text-sm text-gray-500">Belum ada pendapatan bersih pada bulan ini.</div>
            @else
                <div class="mt-5 grid h-40 grid-cols-4 items-end gap-5 border-b border-gray-100 px-4">
                    @foreach ($weekly as $index => $amount)
                        <div class="flex h-full flex-col items-center justify-end gap-2"><div title="{{ $rupiah($amount) }}" class="w-9/12 rounded-t-md {{ $index === $highestWeek ? 'bg-green-900' : 'bg-green-100' }}" style="height: {{ $maxWeekly > 0 ? max(5, ($amount / $maxWeekly) * 85) : 0 }}%"></div><span class="text-xs text-gray-600">M{{ $index + 1 }}</span></div>
                    @endforeach
                </div>
            @endif
        </section>

        <section>
            <h2 class="mb-3 text-lg font-semibold text-gray-900">Rincian per pesanan</h2>
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
                <table class="w-full min-w-[850px] text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600"><tr><th class="px-3 py-3 font-medium">Pesanan</th><th class="px-3 py-3 font-medium">Tanggal</th><th class="px-3 py-3 font-medium">Kotor</th><th class="px-3 py-3 font-medium">Biaya platform</th><th class="px-3 py-3 font-medium">Bersih</th><th class="px-3 py-3 font-medium">Status</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr><td class="px-3 py-3"><a class="font-semibold text-gray-900 hover:text-green-800" href="{{ route('seller.pesanan.show', $order->id) }}">#{{ $order->nomor_order }}</a><span class="mt-1 block text-gray-500">{{ $order->nama_item }}</span></td><td class="px-3 py-3 whitespace-nowrap">{{ $order->completed_at->translatedFormat('d M Y') }}</td><td class="px-3 py-3 whitespace-nowrap font-medium text-green-900">{{ $rupiah($order->gross) }}</td><td class="px-3 py-3 whitespace-nowrap">{{ $rupiah($order->fee) }}</td><td class="px-3 py-3 whitespace-nowrap font-medium text-green-900">{{ $rupiah($order->net) }}</td><td class="px-3 py-3">@php($paymentLabel = ['sah' => 'Lunas', 'menunggu_verifikasi' => 'Menunggu verifikasi', 'belum' => 'Belum lunas'][$order->status_bayar] ?? $order->status_bayar)<span class="rounded-full bg-green-100 px-2 py-1 text-[10px] font-medium text-green-800">{{ $paymentLabel }}</span></td></tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">Belum ada pesanan selesai pada bulan ini.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
