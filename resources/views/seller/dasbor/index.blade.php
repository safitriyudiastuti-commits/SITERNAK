@extends('layouts.seller')

@section('title', 'Dasbor Toko')

@section('content')
<style>
    .seller-dashboard { --dashboard-green: #2d5a27; }
    .dashboard-card { border: 1px solid #e5e7eb; border-radius: 0.75rem; background: #fff; }
    .dashboard-tone-amber { background: #fff4d6; color: #a16207; }
    .dashboard-tone-green { background: #e8f2e5; color: #2d5a27; }
    .dashboard-tone-blue { background: #e8efff; color: #3156a3; }
    .dashboard-tone-red { background: #fee9e7; color: #b42318; }
    .dashboard-chart { height: 190px; }
    .dashboard-bar { min-height: 0.5rem; background: #d7e5d2; }
    .dashboard-bar.is-current { background: var(--dashboard-green); }
    .dashboard-table { min-width: 700px; }
    @media (max-width: 640px) {
        .dashboard-chart { height: 150px; }
    }
</style>

<div class="seller-dashboard space-y-6">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Toko Saya</p>
            <h1 class="text-2xl font-bold text-gray-900">Selamat datang, {{ $ownerName }}</h1>
            <p class="mt-1 text-sm text-gray-500">Ringkasan usaha {{ $store->name }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="#" class="inline-flex items-center gap-2 rounded-lg border border-[#2d5a27] px-4 py-2 text-sm font-medium text-[#2d5a27] hover:bg-[#eef3ea]">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Tambah Hasil Ternak
            </a>
            <a href="#" class="inline-flex items-center gap-2 rounded-lg bg-[#2d5a27] px-4 py-2 text-sm font-medium text-white hover:bg-[#234a1f]">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Tambah Ternak
            </a>
        </div>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan toko">
        @foreach ($summary as $item)
            <article class="dashboard-card flex items-start gap-3 p-4">
                <span class="dashboard-tone-{{ $item['tone'] }} flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-sm font-bold">
                    @if ($item['icon'] === '!')
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                            <path d="M12 3 2.8 19h18.4L12 3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                            <path d="M12 9v4m0 3h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                    @else
                        {{ $item['icon'] }}
                    @endif
                </span>
                <div class="min-w-0">
                    <p class="text-xs text-gray-500">{{ $item['label'] }}</p>
                    <p class="mt-1 text-lg font-bold text-gray-900">{{ $item['value'] }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $item['note'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="grid gap-4 xl:grid-cols-5">
        <article class="dashboard-card p-5 xl:col-span-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-gray-900">Pendapatan 6 bulan terakhir</h2>
                    <p class="mt-1 text-xs text-gray-500">Ringkasan pendapatan per bulan</p>
                </div>
                <span class="text-xs text-gray-500">Dalam juta rupiah</span>
            </div>
            <div class="dashboard-chart mt-5 flex items-end justify-around gap-3 border-b border-l border-gray-200 px-3">
                @foreach ($chart as $index => $item)
                    <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                        <span class="text-[10px] text-gray-500">{{ $item['amount'] }}</span>
                        <div class="dashboard-bar w-full max-w-10 rounded-t-md {{ $index === count($chart) - 1 ? 'is-current' : '' }}"
                             style="height: {{ $item['height'] }}%"
                             role="img"
                             aria-label="Pendapatan {{ $item['month'] }}: {{ $item['amount'] }}"></div>
                        <span class="text-xs text-gray-500">{{ $item['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="dashboard-card p-5 xl:col-span-2">
            <h2 class="font-semibold text-gray-900">Perlu tindakan</h2>
            <ul class="mt-3 divide-y divide-gray-100">
                @foreach ($actions as $item)
                    <li class="flex items-start gap-3 py-3 first:pt-1 last:pb-0">
                        <span class="dashboard-tone-{{ $item['tone'] }} flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-sm font-bold">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                                <path d="M12 3 2.8 19h18.4L12 3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                <path d="M12 9v4m0 3h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-gray-900">{{ $item['title'] }}</p>
                            <p class="mt-1 text-[11px] text-gray-500">{{ $item['description'] }}</p>
                        </div>
                        <span class="text-[11px] font-semibold text-[#2d5a27]">{{ $item['action'] }}</span>
                    </li>
                @endforeach
            </ul>
        </article>
    </section>

    <section class="dashboard-card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-4">
            <div>
                <h2 class="font-semibold text-gray-900">Pesanan terbaru</h2>
                <p class="mt-1 text-xs text-gray-500">Aktivitas pesanan terbaru di {{ $store->name }}</p>
            </div>
            <a href="{{ route('seller.toko.profil') }}" class="text-xs font-semibold text-[#2d5a27] hover:underline">
                Kelola toko
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="dashboard-table w-full border-collapse text-left text-xs">
                <thead class="bg-gray-50 text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Pesanan</th>
                        <th class="px-4 py-3 font-medium">Pembeli</th>
                        <th class="px-4 py-3 font-medium">Metode</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="px-5 py-3">
                                <p class="font-semibold text-gray-900">{{ $order['product'] }}</p>
                                <p class="mt-1 text-[10px] text-gray-500">{{ $order['order_code'] }} · {{ $order['quantity'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-900">{{ $order['buyer'] }}</p>
                                <p class="mt-1 text-[10px] text-gray-500">{{ $order['location'] }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px]">{{ $order['method'] }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="dashboard-tone-{{ $order['tone'] }} whitespace-nowrap rounded-full px-2 py-1 text-[10px] font-medium">
                                    {{ $order['status'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right font-semibold text-gray-900">{{ $order['total'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
