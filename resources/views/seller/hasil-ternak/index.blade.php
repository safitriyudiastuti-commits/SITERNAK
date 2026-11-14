@extends('layouts.seller')

@section('title', 'Hasil Ternak')

@section('content')
@php
    $statusLabels = [
        'tampil' => 'Tampil di katalog',
        'hampir_kedaluwarsa' => 'Hampir kedaluwarsa',
        'habis' => 'Stok habis',
        'kedaluwarsa' => 'Kedaluwarsa',
    ];
    $statusClasses = [
        'tampil' => 'bg-[#e0f3e7] text-[#247341]',
        'hampir_kedaluwarsa' => 'bg-[#fff1c9] text-[#a16207]',
        'habis' => 'bg-gray-100 text-gray-600',
        'kedaluwarsa' => 'bg-[#fee2e2] text-[#b91c1c]',
    ];
    $statusCounts = $products->countBy(fn ($product) => $product->status);
@endphp
<style>
    .product-table { min-width: 930px; }
    .product-table th, .product-table td { border-bottom: 1px solid #eeefea; }
    .product-row[hidden] { display: none; }
    .product-filter.is-selected { border-color: #2d5a27; background: #e0f3e7; color: #24531f; }
</style>

<div class="space-y-5">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="mb-2 text-xs text-gray-500">Toko Saya <span class="px-1 text-gray-300">›</span> <span class="font-medium text-gray-800">Hasil Ternak</span></p>
            <h1 class="text-2xl font-bold text-gray-900">Hasil ternak</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola stok telur, susu, daging, dan produk turunan lainnya.</p>
        </div>
        <a href="{{ route('seller.hasil-ternak.create') }}"
           class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#2d5a27] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#234a1f]">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            Tambah Hasil Ternak
        </a>
    </header>

    <div class="flex flex-wrap gap-2" role="group" aria-label="Filter status hasil ternak">
        @foreach (['all' => 'Semua', 'tampil' => 'Tampil di katalog', 'hampir_kedaluwarsa' => 'Hampir kedaluwarsa', 'habis' => 'Stok habis', 'kedaluwarsa' => 'Kedaluwarsa'] as $status => $label)
            <button type="button" data-product-filter="{{ $status }}"
                    class="product-filter {{ $status === 'all' ? 'is-selected' : '' }} rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700">
                {{ $label }} <span class="ml-1 text-gray-500">{{ $status === 'all' ? $products->count() : ($statusCounts[$status] ?? 0) }}</span>
            </button>
        @endforeach
    </div>

    <div class="flex items-start gap-2 rounded-lg bg-[#eaf2e6] px-3 py-2.5 text-[11px] leading-5 text-[#456541]">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="mt-0.5 h-4 w-4 shrink-0">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/><path d="M12 11v5m0-8h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        Produk otomatis disembunyikan dari katalog saat kedaluwarsa atau stok habis. Riwayat produk tetap tersimpan.
    </div>

    @if (session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <label class="relative block w-full sm:max-w-72">
        <span class="sr-only">Cari nama atau jenis produk</span>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500">
            <circle cx="10.8" cy="10.8" r="6.3" stroke="currentColor" stroke-width="1.7"/><path d="m15.5 15.5 4.2 4.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        <input id="product-search" type="search" placeholder="Cari nama atau jenis produk"
               class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-xs outline-none focus:border-[#2d5a27] focus:ring-2 focus:ring-[#2d5a27]/10">
    </label>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="product-table w-full border-collapse text-left text-xs">
                <thead class="bg-[#fafaf8] text-gray-600">
                    <tr>
                        <th class="px-3 py-3 font-medium">Produk</th>
                        <th class="px-3 py-3 font-medium">Jenis</th>
                        <th class="px-3 py-3 font-medium">Harga</th>
                        <th class="px-3 py-3 font-medium">Stok</th>
                        <th class="px-3 py-3 font-medium">Kedaluwarsa</th>
                        <th class="px-3 py-3 font-medium">Status</th>
                        <th class="px-3 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse ($products as $product)
                        @php($status = $product->status)
                        <tr class="product-row" data-status="{{ $status }}">
                            <td class="px-3 py-3">
                                <div class="flex min-w-44 items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#f0f4ed] text-[#63845e]">
                                        @if ($product->media->isNotEmpty())
                                            <img src="{{ Storage::disk('public')->url($product->media->first()->path) }}" alt="" class="h-full w-full object-cover">
                                        @else
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M7 4h10v16H7zM7 9h10" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                                        @endif
                                    </span>
                                    <span class="font-semibold text-gray-900">{{ $product->commodityType->name }}</span>
                                </div>
                            </td>
                            <td class="px-3 py-3">{{ $product->commodityType->category->name }}</td>
                            <td class="whitespace-nowrap px-3 py-3 font-semibold text-[#2d5a27]">
                                Rp {{ number_format((float) $product->harga, 0, ',', '.') }} <span class="font-normal text-gray-500">/ {{ $product->satuan }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3">{{ rtrim(rtrim(number_format((float) $product->stok, 3, ',', '.'), '0'), ',') }} {{ $product->satuan }}</td>
                            <td class="whitespace-nowrap px-3 py-3">
                                <span class="font-medium">{{ $product->tanggal_kedaluwarsa->translatedFormat('d M Y') }}</span>
                                <span class="mt-0.5 block text-[10px] text-gray-500">
                                    @if ($product->tanggal_kedaluwarsa->lt(today()))
                                        Lewat {{ $product->tanggal_kedaluwarsa->diffForHumans(today(), true) }}
                                    @else
                                        Sisa {{ today()->diffInDays($product->tanggal_kedaluwarsa) }} hari
                                    @endif
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-medium {{ $statusClasses[$status] }}">
                                    {{ $statusLabels[$status] }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex justify-end gap-1.5">
                                    <a href="{{ route('seller.hasil-ternak.edit', $product) }}" class="rounded-lg border border-gray-200 px-2.5 py-1.5 text-[10px] font-medium text-gray-700 hover:border-[#2d5a27] hover:text-[#2d5a27]">Ubah</a>
                                    @if ($status === 'kedaluwarsa')
                                        <span class="rounded-lg bg-gray-100 px-2.5 py-1.5 text-[10px] text-gray-500">Restock ditutup</span>
                                    @else
                                        <a href="{{ route('seller.hasil-ternak.restock.form', $product) }}" class="rounded-lg bg-[#eaf2e6] px-2.5 py-1.5 text-[10px] font-semibold text-[#2d5a27] hover:bg-[#dcebd6]">Restock</a>
                                    @endif
                                    <button type="button" data-delete-product="{{ $product->id }}"
                                            data-delete-url="{{ route('seller.hasil-ternak.destroy', $product) }}"
                                            data-delete-name="{{ $product->commodityType->name }}"
                                            class="rounded-lg px-2.5 py-1.5 text-[10px] font-medium text-red-600 hover:bg-red-50">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">Belum ada hasil ternak. Tambahkan produk untuk mulai mengelola stok.</td></tr>
                    @endforelse
                    <tr id="product-empty-filter" hidden><td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">Tidak ada produk yang sesuai filter.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-xs text-gray-500">Menampilkan <span id="product-visible-count">{{ $products->count() }}</span> dari {{ $products->count() }} produk</p>

    <dialog id="delete-product-dialog" class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl p-6 shadow-xl backdrop:bg-black/40">
        <form id="delete-product-form" method="POST">
            @csrf
            @method('DELETE')
            <h2 class="text-lg font-semibold text-gray-900">Hapus produk?</h2>
            <p class="mt-2 text-sm text-gray-600">Produk <span id="delete-product-name" class="font-semibold"></span> dan semua fotonya akan dihapus.</p>
            <div class="mt-5 flex justify-end gap-2">
                <button id="cancel-delete-product" type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">Ya, Hapus</button>
            </div>
        </form>
    </dialog>
</div>

<script>
    (() => {
        const search = document.getElementById('product-search');
        const rows = [...document.querySelectorAll('.product-row')];
        const filterButtons = [...document.querySelectorAll('[data-product-filter]')];
        const emptyRow = document.getElementById('product-empty-filter');
        const visibleCount = document.getElementById('product-visible-count');
        const dialog = document.getElementById('delete-product-dialog');
        const deleteForm = document.getElementById('delete-product-form');
        const deleteName = document.getElementById('delete-product-name');
        const cancelDelete = document.getElementById('cancel-delete-product');
        let selectedStatus = 'all';

        function applyFilters() {
            const query = search?.value.trim().toLocaleLowerCase('id') ?? '';
            let visible = 0;
            rows.forEach((row) => {
                const matches = (selectedStatus === 'all' || row.dataset.status === selectedStatus)
                    && row.textContent.toLocaleLowerCase('id').includes(query);
                row.hidden = !matches;
                if (matches) visible++;
            });
            if (emptyRow) emptyRow.hidden = visible !== 0 || rows.length === 0;
            if (visibleCount) visibleCount.textContent = String(visible);
        }

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                selectedStatus = button.dataset.productFilter ?? 'all';
                filterButtons.forEach((item) => item.classList.toggle('is-selected', item === button));
                applyFilters();
            });
        });
        search?.addEventListener('input', applyFilters);

        document.querySelectorAll('[data-delete-product]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!dialog || !deleteForm || !deleteName) return;
                const url = button.dataset.deleteUrl;
                if (!url) return;
                deleteForm.action = url;
                deleteName.textContent = button.dataset.deleteName ?? 'produk ini';
                dialog.showModal();
            });
        });
        cancelDelete?.addEventListener('click', () => dialog?.close());
        dialog?.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.close();
        });
        applyFilters();
    })();
</script>
@endsection
