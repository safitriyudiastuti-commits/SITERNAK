@extends('layouts.seller')

@section('title', 'Pesanan masuk')

@section('content')
@php
    $tabs = [
        'semua' => 'Semua',
        'menunggu_konfirmasi' => 'Menunggu konfirmasi',
        'dikonfirmasi' => 'Dikonfirmasi',
        'menunggu_verifikasi_bayar' => 'Menunggu verifikasi bayar',
        'lunas' => 'Lunas',
        'selesai' => 'Selesai',
    ];
    $orderStatusLabels = [
        'menunggu_konfirmasi' => 'Menunggu konfirmasi',
        'dikonfirmasi' => 'Dikonfirmasi',
        'diproses' => 'Diproses',
        'dikirim' => 'Dikirim',
        'selesai' => 'Selesai',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
    ];
    $orderStatusClasses = [
        'menunggu_konfirmasi' => 'bg-amber-50 text-amber-800',
        'dikonfirmasi' => 'bg-sky-50 text-sky-800',
        'diproses' => 'bg-blue-50 text-blue-800',
        'dikirim' => 'bg-indigo-50 text-indigo-800',
        'selesai' => 'bg-emerald-50 text-emerald-800',
        'ditolak' => 'bg-red-50 text-red-800',
        'dibatalkan' => 'bg-gray-100 text-gray-700',
    ];
    $paymentLabels = [
        'belum' => 'Belum',
        'menunggu_verifikasi' => 'Menunggu verifikasi',
        'sah' => 'Lunas',
    ];
    $paymentClasses = [
        'belum' => 'bg-gray-100 text-gray-700',
        'menunggu_verifikasi' => 'bg-amber-50 text-amber-800',
        'sah' => 'bg-emerald-50 text-emerald-800',
    ];
@endphp

<div class="space-y-6">
    <header>
        <p class="mb-2 text-xs text-gray-500">
            Toko Saya <span class="px-1 text-gray-300">›</span>
            <span class="font-medium text-gray-800">Pesanan Masuk</span>
        </p>
        <h1 class="text-2xl font-bold text-gray-900">Pesanan masuk</h1>
        <p class="mt-1 text-sm text-gray-500">Terima atau tolak pesanan, lalu pantau pembayarannya.</p>
    </header>

    <nav class="flex flex-wrap gap-2" aria-label="Filter tahap pesanan">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('seller.pesanan.index', ['tab' => $value]) }}"
               @if ($tab === $value) aria-current="page" @endif
               class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold transition
                    {{ $tab === $value
                        ? 'border-[#2d5a27] bg-[#2d5a27] text-white'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-[#2d5a27] hover:text-[#2d5a27]' }}">
                {{ $label }}
                <span class="rounded-full px-1.5 py-0.5 text-[10px]
                    {{ $tab === $value ? 'bg-white/15 text-white' : 'bg-gray-100 text-gray-600' }}">
                    {{ $counts[$value] }}
                </span>
            </a>
        @endforeach
    </nav>

    <div class="space-y-3">
        @forelse ($orders as $order)
            @php
                $isPending = $order->status === 'menunggu_konfirmasi';
                $itemType = $order->tipe_item;
                $inventory = $order->livestockProduct ?? $order->livestock;
                $hasInventory = $inventory !== null;
                $stock = $hasInventory ? (float) $inventory->stok : null;
                $stockUnit = $order->livestockProduct?->satuan
                    ?? $order->livestock?->satuan_jual
                    ?? $order->satuan
                    ?? 'satuan';
                $stockLabel = $hasInventory
                    ? rtrim(rtrim(number_format($stock, 3, ',', '.'), '0'), ',')
                    : null;
                $methodLabel = match ($order->metode_bayar) {
                    'cod' => 'COD',
                    'qris' => 'QRIS',
                    'transfer_bank' => 'Transfer Bank',
                    default => mb_strtoupper(str_replace('_', ' ', $order->metode_bayar)),
                };
                $dialogId = 'reject-order-dialog-'.$order->id;
                $hasRejectError = $errors->has('alasan_penolakan')
                    && (string) old('reject_order_id') === (string) $order->id;
            @endphp

            <article class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                    <a href="{{ route('seller.pesanan.show', $order->id) }}"
                       class="group flex min-w-0 items-start gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2d5a27]/30">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                            {{ $itemType === 'produk' ? 'bg-amber-50 text-amber-700' : ($itemType === 'batch' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700') }}">
                            @if ($itemType === 'produk')
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                    <path d="M7 4h10l1 4v12H6V8l1-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="M6 8h12M9 12h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            @elseif ($itemType === 'batch')
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                    <path d="M4 13c0-3 2-5 5-5h6c3 0 5 2 5 5v4h-3v-2H7v2H4v-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="M8 8 6 5M16 8l2-3M8 12h.01M16 12h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            @else
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                                    <path d="M4 14c0-3.3 2.7-6 6-6h5a5 5 0 0 1 5 5v4h-3v-2H7v2H4v-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                                    <path d="M9 8 7 5M17 8l2-3M8 13h.01M15 13h.01" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                            @endif
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-gray-900 group-hover:text-[#2d5a27]">
                                {{ $order->nama_item }}
                            </span>
                            <span class="mt-1 block text-xs text-gray-500">
                                #{{ $order->nomor_order }} · {{ number_format((float) $order->jumlah, 2, ',', '.') }} {{ $order->satuan }}
                            </span>
                            <span class="mt-2 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-gray-600">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-3.5 w-3.5 shrink-0 text-gray-400">
                                    <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M5 20a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                                {{ $order->nama_penerima }}
                                <span class="text-gray-300">·</span>
                                {{ $order->kota_kabupaten_penerima }}, {{ $order->provinsi_penerima }}
                            </span>
                            <span class="mt-2 block text-[11px] leading-5 text-gray-500">
                                @if ($order->metode_bayar === 'cod' && $order->cod_same_city)
                                    <span class="block">Pembeli satu kota dengan Anda, sehingga COD tersedia</span>
                                @endif
                                @if ($hasInventory)
                                    <span class="block">
                                        Stok tersedia {{ $stockLabel }} {{ $stockUnit }},
                                        {{ $stock >= (float) $order->jumlah ? 'cukup untuk pesanan ini' : 'stok tidak cukup' }}
                                    </span>
                                @endif
                                <span class="block">Pesanan diterima {{ $order->created_at?->format('d M Y, H:i') }}</span>
                            </span>
                        </span>
                    </a>

                    <div class="flex flex-col gap-3 border-t border-gray-100 pt-4 lg:min-w-[17rem] lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-700">{{ $methodLabel }}</span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $orderStatusClasses[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $orderStatusLabels[$order->status] ?? ucfirst($order->status) }}
                            </span>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $paymentClasses[$order->status_bayar] ?? 'bg-gray-100 text-gray-700' }}">
                                Bayar: {{ $paymentLabels[$order->status_bayar] ?? ucfirst($order->status_bayar ?? '-') }}
                            </span>
                        </div>

                        <p class="text-lg font-bold text-[#2d5a27] lg:text-right">
                            Rp {{ number_format((float) $order->total_harga, 0, ',', '.') }}
                        </p>

                        <div class="flex flex-wrap gap-2 lg:justify-end">
                            @if ($isPending)
                                <form method="POST" action="{{ route('seller.pesanan.confirm', $order->id) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-[#2d5a27] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#234a1f]">
                                        Terima Pesanan
                                    </button>
                                </form>
                                <button type="button" data-open-reject="{{ $dialogId }}"
                                        class="rounded-lg border border-red-300 bg-white px-3.5 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                    Tolak
                                </button>
                            @else
                                <a href="{{ route('seller.pesanan.show', $order->id) }}"
                                   class="rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                    Lihat detail
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </article>

            @if ($isPending)
                <dialog id="{{ $dialogId }}" class="m-auto w-[min(28rem,calc(100%-2rem))] rounded-2xl p-0 shadow-xl backdrop:bg-black/40">
                    <form method="POST" action="{{ route('seller.pesanan.reject', $order->id) }}" class="p-5">
                        @csrf
                        <input type="hidden" name="reject_order_id" value="{{ $order->id }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-base font-bold text-gray-900">Tolak pesanan?</h2>
                                <p class="mt-1 text-xs text-gray-500">Pesanan #{{ $order->nomor_order }}</p>
                            </div>
                            <button type="button" data-close-reject="{{ $dialogId }}" aria-label="Tutup dialog"
                                    class="rounded-md px-2 py-1 text-lg leading-none text-gray-400 hover:bg-gray-100 hover:text-gray-700">×</button>
                        </div>
                        <label for="alasan-penolakan-{{ $order->id }}" class="mt-4 block text-sm font-medium text-gray-700">
                            Alasan penolakan <span class="text-red-600">*</span>
                        </label>
                        <textarea id="alasan-penolakan-{{ $order->id }}" name="alasan_penolakan" rows="4" maxlength="1000" required
                                  class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100">{{ $hasRejectError ? old('alasan_penolakan') : '' }}</textarea>
                        @if ($hasRejectError)
                            <p class="mt-1 text-sm text-red-700">{{ $errors->first('alasan_penolakan') }}</p>
                        @endif
                        <div class="mt-5 flex justify-end gap-2">
                            <button type="button" data-close-reject="{{ $dialogId }}"
                                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</button>
                            <button type="submit" class="rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white hover:bg-red-800">Kirim penolakan</button>
                        </div>
                    </form>
                </dialog>
            @endif
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                        <path d="M4 5h16v14H4zM8 9h8M8 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h2 class="mt-3 font-semibold text-gray-900">Belum ada pesanan</h2>
                <p class="mt-1 text-sm text-gray-500">Pesanan pada tahap ini akan muncul di sini.</p>
            </div>
        @endforelse
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
        <p>Menampilkan {{ $orders->firstItem() ?? 0 }}-{{ $orders->lastItem() ?? 0 }} dari {{ $orders->total() }} pesanan</p>
        {{ $orders->links() }}
    </div>
</div>

<script>
    (() => {
        document.querySelectorAll('[data-open-reject]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById(button.dataset.openReject)?.showModal();
            });
        });

        document.querySelectorAll('[data-close-reject]').forEach((button) => {
            button.addEventListener('click', () => {
                document.getElementById(button.dataset.closeReject)?.close();
            });
        });

        document.querySelectorAll('dialog[id^="reject-order-dialog-"]').forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
        });

        const rejectedOrderId = @json(old('reject_order_id'));
        if (rejectedOrderId) {
            document.getElementById(`reject-order-dialog-${rejectedOrderId}`)?.showModal();
        }
    })();
</script>
@endsection
