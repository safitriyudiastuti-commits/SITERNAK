@extends('layouts.seller')

@section('title', 'Ternak Hidup')

@section('content')
@php
    $livestockTotal = $categories->sum(fn ($category) => $category->livestock->count());
@endphp
<style>
    .livestock-page { --livestock-green: #2d5a27; }
    .livestock-table { min-width: 860px; }
    .livestock-table th, .livestock-table td { border-bottom: 1px solid #eeefea; }
    .livestock-row[hidden] { display: none; }
    .livestock-filter.is-selected { background: #2d5a27; color: #fff; border-color: #2d5a27; }
    .livestock-pill { display: inline-flex; align-items: center; border-radius: 9999px; padding: 0.22rem 0.55rem; font-size: 0.65rem; line-height: 1rem; white-space: nowrap; }
</style>

<div class="livestock-page space-y-5">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="mb-2 text-xs text-gray-500">
                Toko Saya <span class="px-1 text-gray-300">›</span>
                <span class="font-medium text-gray-800">Ternak Hidup</span>
            </p>
            <h1 class="text-2xl font-bold text-gray-900">Ternak hidup</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola data ternak individual maupun batch, dari unggas sampai sapi.</p>
        </div>
        <a href="{{ route('seller.ternak.create') }}"
           class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#2d5a27] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#234a1f]">
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            Tambah Ternak
        </a>
    </header>

    <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
        <label class="relative block w-full xl:max-w-72">
            <span class="sr-only">Cari nama atau jenis ternak</span>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500">
                <circle cx="10.8" cy="10.8" r="6.3" stroke="currentColor" stroke-width="1.7"/>
                <path d="m15.5 15.5 4.2 4.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
            <input id="livestock-search" type="search" placeholder="Cari nama atau jenis ternak"
                   class="w-full rounded-lg border border-gray-200 bg-white py-2 pl-9 pr-3 text-xs outline-none focus:border-[#2d5a27] focus:ring-2 focus:ring-[#2d5a27]/10">
        </label>

        <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filter kategori ternak">
            <button type="button" data-category-filter="all" class="livestock-filter is-selected rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700">
                Semua
            </button>
            @foreach ($categories as $category)
                <button type="button" data-category-filter="{{ $category->id }}"
                        class="livestock-filter rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:border-[#2d5a27]">
                    {{ $category->name }}
                </button>
            @endforeach
        </div>

        <label class="relative block xl:ml-auto xl:w-40">
            <span class="sr-only">Filter status</span>
            <select id="livestock-status" class="w-full appearance-none rounded-lg border border-gray-200 bg-white px-3 py-2 pr-8 text-xs text-gray-700 outline-none focus:border-[#2d5a27]">
                <option value="all">Semua status</option>
                <option value="aktif">Tersedia</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="pointer-events-none absolute right-3 top-1/2 h-3 w-3 -translate-y-1/2 text-gray-500">
                <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </label>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="overflow-x-auto">
            <table class="livestock-table w-full border-collapse text-left text-xs">
                <thead class="bg-[#fafaf8] text-gray-600">
                    <tr>
                        <th class="px-3 py-3 font-medium">Ternak</th>
                        <th class="px-3 py-3 font-medium">Mode</th>
                        <th class="px-3 py-3 font-medium">Peruntukan</th>
                        <th class="px-3 py-3 font-medium">Harga</th>
                        <th class="px-3 py-3 font-medium">Ketersediaan</th>
                        <th class="px-3 py-3 font-medium">Prestasi kontes</th>
                        <th class="px-3 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @foreach ($categories as $category)
                        @foreach ($category->livestock as $item)
                            @php
                                $displayName = $item->nama_kode ?: $item->commodityType->name;
                                $hasEligibleContest = $item->mode === 'individual'
                                    && ! empty($item->commodityType->contest_categories);
                                $statusClass = $item->status === 'aktif' && $item->stok > 0
                                    ? 'bg-[#e0f3e7] text-[#247341]'
                                    : ($item->status === 'aktif' ? 'bg-[#fff1c9] text-[#a16207]' : 'bg-gray-100 text-gray-600');
                                $statusLabel = $item->status !== 'aktif'
                                    ? 'Nonaktif'
                                    : ($item->stok > 0 ? "Stok {$item->stok} {$item->satuan_jual}" : 'Stok habis');
                            @endphp
                            <tr class="livestock-row"
                                data-category="{{ $category->id }}"
                                data-status="{{ $item->status }}"
                                data-search="{{ mb_strtolower($displayName.' '.$item->commodityType->name.' '.$category->name.' '.$item->ras) }}">
                                <td class="px-3 py-3">
                                    <div class="flex min-w-48 items-center gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#f0f4ed] text-[#63845e]">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                                                <path d="M4 13c0-3.3 2.8-5.5 7-5.5h4.5l2.2-2.7 2.1 1.1-1.2 3.3c1.2 1 1.9 2.3 1.9 3.8 0 3.2-2.7 5.5-7.5 5.5H9c-3.1 0-5-2.2-5-5.5Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                                                <path d="M7 8 5 5m10 4h.01M7 14h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        <span class="min-w-0">
                                            <a href="{{ route('seller.ternak.show', $item->id) }}"
                                               class="block max-w-56 truncate font-semibold text-gray-900 hover:text-[#2d5a27]">
                                                {{ $displayName }}
                                            </a>
                                            <span class="mt-0.5 block truncate text-[10px] text-gray-500">
                                                {{ $item->commodityType->name }}
                                                @if ($item->ras && $item->ras !== $item->commodityType->name) · {{ $item->ras }} @endif
                                                @if ($item->jenis_kelamin) · {{ ucfirst($item->jenis_kelamin) }} @endif
                                                @if ($item->umur !== null) · {{ $item->umur }} bulan @endif
                                                @if ($item->berat !== null) · {{ $item->berat }} kg @endif
                                            </span>
                                            @if ($item->media->isNotEmpty())
                                                <span class="sr-only">Media: {{ $item->media->pluck('original_name')->implode(', ') }}</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="livestock-pill {{ $item->mode === 'individual' ? 'bg-[#e5edff] text-[#3156a3]' : 'bg-[#f0f0ec] text-gray-700' }}">
                                        {{ $item->mode === 'individual' ? 'Individual' : 'Batch' }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    {{ $item->purpose ? (\App\Models\CommodityType::PURPOSES[$item->purpose] ?? ucfirst(str_replace('_', ' ', $item->purpose))) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 font-semibold text-[#2d5a27]">
                                    Rp {{ number_format((float) $item->harga, 0, ',', '.') }}
                                    <span class="font-normal text-gray-500">/ {{ $item->satuan_jual }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="livestock-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($hasEligibleContest && $item->contestHistories->contains('status', 'verified'))
                                        <span class="livestock-pill bg-[#e0f3e7] text-[#247341]">Terverifikasi</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex justify-end gap-1.5">
                                        <a href="{{ route('seller.ternak.edit', $item->id) }}"
                                           aria-label="Ubah {{ $displayName }}"
                                           title="Ubah"
                                           class="rounded-lg border border-gray-200 p-2 text-gray-600 hover:border-[#2d5a27] hover:text-[#2d5a27]">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                                                <path d="m14 5 5 5M4 20l4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                        <button type="button"
                                                aria-label="Hapus {{ $displayName }}"
                                                title="Hapus"
                                                onclick="document.getElementById('hapus-{{ $item->id }}').showModal()"
                                                class="rounded-lg border border-gray-200 p-2 text-red-600 hover:border-red-300 hover:bg-red-50">
                                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                                                <path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr id="livestock-empty" hidden>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                            Tidak ada ternak yang cocok dengan pencarian atau filter.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
        <p>Menampilkan <span id="livestock-visible-count">{{ $livestockTotal }}</span> dari {{ $livestockTotal }} data ternak</p>
        <span class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-gray-600">Daftar ternak</span>
    </div>

    @foreach ($categories as $category)
        @foreach ($category->livestock as $item)
            <dialog id="hapus-{{ $item->id }}" class="m-auto w-[min(24rem,calc(100%-2rem))] rounded-2xl p-6 shadow-xl backdrop:bg-black/40">
                <form method="POST" action="{{ route('seller.ternak.destroy', $item->id) }}">
                    @csrf
                    @method('DELETE')
                    <h2 class="text-lg font-semibold text-gray-900">Hapus data ternak?</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Data {{ $item->nama_kode ?: $item->commodityType->name }} dan semua media terkait akan dihapus.
                    </p>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" onclick="this.closest('dialog').close()"
                                class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                            Ya, Hapus
                        </button>
                    </div>
                </form>
            </dialog>
        @endforeach
    @endforeach
</div>

<script>
    (() => {
        const search = document.getElementById('livestock-search');
        const status = document.getElementById('livestock-status');
        const rows = [...document.querySelectorAll('.livestock-row')];
        const categoryButtons = [...document.querySelectorAll('[data-category-filter]')];
        const emptyRow = document.getElementById('livestock-empty');
        const visibleCount = document.getElementById('livestock-visible-count');
        let selectedCategory = 'all';

        function applyFilters() {
            const query = search.value.trim().toLocaleLowerCase('id');
            let visible = 0;
            rows.forEach((row) => {
                const matches = (selectedCategory === 'all' || row.dataset.category === selectedCategory)
                    && (status.value === 'all' || row.dataset.status === status.value)
                    && row.textContent.toLocaleLowerCase('id').includes(query);
                row.hidden = !matches;
                if (matches) visible++;
            });
            emptyRow.hidden = visible !== 0;
            visibleCount.textContent = visible;
        }

        categoryButtons.forEach((button) => {
            button.addEventListener('click', () => {
                selectedCategory = button.dataset.categoryFilter;
                categoryButtons.forEach((item) => item.classList.toggle('is-selected', item === button));
                applyFilters();
            });
        });
        search.addEventListener('input', applyFilters);
        status.addEventListener('change', applyFilters);
        applyFilters();
    })();
</script>
@endsection
