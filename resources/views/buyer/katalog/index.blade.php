@extends('layouts.buyer')

@section('title', 'Katalog Ternak')

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');
    $labelPeruntukan = ['bibit' => 'Bibit', 'pembesaran' => 'Pembesaran', 'siap_potong' => 'Siap potong', 'indukan_kontes' => 'Indukan/kontes'];
    $kelompokAktif = $filter['kelompok'] ?? '';
@endphp

<div class="mx-auto max-w-7xl">
    <nav class="text-xs text-[#6B7265]">Beranda <span class="mx-1">›</span> <span class="font-semibold text-[#1C2118]">Katalog Ternak</span></nav>
    <h1 class="mt-3 text-3xl font-extrabold text-[#1C2118]">Katalog ternak dan hasil ternak</h1>
    <p class="mt-1 text-sm text-[#6B7265]">Telusuri ternak hidup dan hasil ternak dari peternak di seluruh Indonesia.</p>

    <form id="filter-form" method="GET" action="{{ route('buyer.katalog.index') }}" class="mt-6 grid grid-cols-[17rem_minmax(0,1fr)] gap-6">
        <input type="hidden" name="kelompok" value="{{ $kelompokAktif }}">

        {{-- ====== FILTER ====== --}}
        <aside class="h-fit space-y-5 rounded-2xl border border-[#E4E4DC] bg-white p-5 text-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-[#1C2118]">Filter</h2>
                <a href="{{ route('buyer.katalog.index') }}" class="text-xs font-semibold text-[#2E5B2A] hover:underline">Atur ulang</a>
            </div>

            <div>
                <label class="mb-1 block font-semibold text-[#1C2118]">Cari</label>
                <input type="text" name="q" value="{{ $filter['q'] ?? '' }}" placeholder="Nama ternak atau produk"
                       class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2 focus:border-[#2E5B2A] focus:outline-none">
            </div>

            <div>
                <label class="mb-1 block font-semibold text-[#1C2118]">Kategori</label>
                <select name="kategori" class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2"
                        onchange="document.querySelectorAll('[name=&quot;spesies[]&quot;]').forEach(c => c.checked = false); this.form.submit()">
                    <option value="">Semua kategori</option>
                    @foreach ($kategoriOptions as $kat)
                        <option value="{{ $kat }}" @selected(($filter['kategori'] ?? '') === $kat)>{{ \Illuminate\Support\Str::headline($kat) }}</option>
                    @endforeach
                </select>
            </div>

            @if ($spesiesOptions->isNotEmpty() && $kelompokAktif !== 'hasil_ternak')
                <div>
                    <p class="mb-1 font-semibold text-[#1C2118]">Jenis <span class="font-normal text-[#9AA093]">ternak hidup</span></p>
                    <div class="space-y-1">
                        @foreach ($spesiesOptions as $sp)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="spesies[]" value="{{ $sp }}" class="rounded border-[#E4E4DC] text-[#2E5B2A]"
                                       @checked(in_array($sp, $filter['spesies'] ?? [], true))>
                                {{ $sp }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($kelompokAktif !== 'hasil_ternak')
                <div>
                    <p class="mb-1 font-semibold text-[#1C2118]">Peruntukan <span class="font-normal text-[#9AA093]">ternak hidup</span></p>
                    <div class="space-y-1">
                        @foreach ($labelPeruntukan as $val => $lbl)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="peruntukan[]" value="{{ $val }}" class="rounded border-[#E4E4DC] text-[#2E5B2A]"
                                       @checked(in_array($val, $filter['peruntukan'] ?? [], true))>
                                {{ $lbl }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <p class="mb-1 font-semibold text-[#1C2118]">Rentang harga</p>
                <div class="flex items-center gap-2">
                    <input type="number" min="0" name="harga_min" value="{{ $filter['harga_min'] ?? '' }}" placeholder="Rp Min"
                           class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2">
                    <span class="text-[#9AA093]">–</span>
                    <input type="number" min="0" name="harga_maks" value="{{ $filter['harga_maks'] ?? '' }}" placeholder="Rp Maks"
                           class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2">
                </div>
            </div>

            @if ($kelompokAktif !== 'hasil_ternak')
                <label class="flex items-start gap-2">
                    <input type="checkbox" name="berprestasi" value="1" class="mt-0.5 rounded border-[#E4E4DC] text-[#2E5B2A]"
                           @checked(! empty($filter['berprestasi']))>
                    <span>Hanya yang berprestasi terverifikasi <span class="block text-xs text-[#9AA093]">prestasi kontes, ternak hidup</span></span>
                </label>
            @endif

            @if ($kelompokAktif !== 'ternak_hidup')
                <div>
                    <label class="mb-1 block font-semibold text-[#1C2118]">Kesegaran <span class="font-normal text-[#9AA093]">hasil ternak</span></label>
                    <select name="kesegaran" class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2">
                        <option value="">Semua</option>
                        <option value="3" @selected(($filter['kesegaran'] ?? '') === '3')>Produksi ≤ 3 hari lalu</option>
                        <option value="7" @selected(($filter['kesegaran'] ?? '') === '7')>Produksi ≤ 7 hari lalu</option>
                    </select>
                </div>
            @endif

            <div class="space-y-2">
                <p class="font-semibold text-[#1C2118]">Wilayah peternak</p>
                <select name="provinsi" class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2"
                        onchange="this.form.kota.value=''; this.form.kecamatan.value=''; this.form.submit()">
                    <option value="">Semua provinsi</option>
                    @foreach ($provinsiOptions as $opt)
                        <option value="{{ $opt }}" @selected(($filter['provinsi'] ?? '') === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                <select name="kota" class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2 disabled:bg-[#F6F6F2]"
                        @disabled($kotaOptions->isEmpty()) onchange="this.form.kecamatan.value=''; this.form.submit()">
                    <option value="">Semua kota/kabupaten</option>
                    @foreach ($kotaOptions as $opt)
                        <option value="{{ $opt }}" @selected(($filter['kota'] ?? '') === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
                <select name="kecamatan" class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2 disabled:bg-[#F6F6F2]"
                        @disabled($kecamatanOptions->isEmpty())>
                    <option value="">Semua kecamatan</option>
                    @foreach ($kecamatanOptions as $opt)
                        <option value="{{ $opt }}" @selected(($filter['kecamatan'] ?? '') === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block font-semibold text-[#1C2118]">Satuan jual</label>
                <select name="satuan" class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2">
                    <option value="">Semua satuan</option>
                    @foreach (['ekor' => 'Per ekor', 'kg' => 'Per kg', 'batch' => 'Per batch', 'butir' => 'Per butir', 'liter' => 'Per liter'] as $val => $lbl)
                        <option value="{{ $val }}" @selected(($filter['satuan'] ?? '') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="w-full rounded-lg bg-[#2E5B2A] py-2.5 font-semibold text-white hover:bg-[#1F421C]">Terapkan Filter</button>
        </aside>

        {{-- ====== HASIL ====== --}}
        <section class="min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex gap-2">
                    @foreach (['' => 'Semua', 'ternak_hidup' => 'Ternak Hidup', 'hasil_ternak' => 'Hasil Ternak'] as $val => $lbl)
                        <button type="submit" name="kelompok" value="{{ $val }}"
                                class="rounded-full border px-4 py-1.5 text-sm font-semibold
                                       {{ $kelompokAktif === $val ? 'border-[#2E5B2A] bg-[#2E5B2A] text-white' : 'border-[#E4E4DC] bg-white text-[#4B5245] hover:border-[#2E5B2A]' }}">
                            {{ $lbl }}
                        </button>
                    @endforeach
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <label for="urut" class="text-[#6B7265]">Urutkan:</label>
                    <select id="urut" name="urut" class="rounded-lg border border-[#E4E4DC] bg-white px-3 py-1.5" onchange="this.form.submit()">
                        <option value="terbaru" @selected(($filter['urut'] ?? 'terbaru') === 'terbaru')>Terbaru</option>
                        <option value="harga_asc" @selected(($filter['urut'] ?? '') === 'harga_asc')>Harga terendah</option>
                        <option value="harga_desc" @selected(($filter['urut'] ?? '') === 'harga_desc')>Harga tertinggi</option>
                    </select>
                </div>
            </div>

            <p class="mt-4 text-sm text-[#6B7265]">
                @if ($items->total() > 0)
                    Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ number_format($items->total(), 0, ',', '.') }} produk
                @else
                    Tidak ada produk yang cocok
                @endif
            </p>

            @if ($items->isEmpty())
                <div class="mt-4 rounded-2xl border border-dashed border-[#E4E4DC] bg-white p-12 text-center">
                    <p class="font-semibold text-[#1C2118]">Belum ada hasil yang sesuai.</p>
                    <p class="mt-1 text-sm text-[#6B7265]">Coba ubah atau kurangi filter, atau atur ulang pencarian.</p>
                    <a href="{{ route('buyer.katalog.index') }}" class="mt-4 inline-block rounded-lg border border-[#2E5B2A] px-4 py-2 text-sm font-semibold text-[#2E5B2A]">Atur ulang filter</a>
                </div>
            @else
                <div class="mt-4 grid grid-cols-3 gap-5">
                    @foreach ($items as $row)
                        @php
                            $segar = $row->tipe === 'produk' && $row->tanggal_produksi
                                && \Illuminate\Support\Carbon::parse($row->tanggal_produksi)->gte(today()->subDays(3));
                            $tipeLabel = match ($row->tipe) { 'individual' => 'Individual', 'batch' => 'Batch', default => ($segar ? 'Segar' : 'Hasil ternak') };
                        @endphp
                        <a href="{{ route('buyer.katalog.show', [$row->tipe, $row->id]) }}"
                           class="group flex flex-col overflow-hidden rounded-2xl border border-[#E4E4DC] bg-white transition hover:border-[#2E5B2A] hover:shadow-md">
                            <div class="relative h-44 bg-[#EEF1EA]">
                                @if ($row->foto)
                                    <img src="{{ asset('storage/' . $row->foto) }}" alt="{{ $row->nama }}" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-xs text-[#9AA093]">Belum ada foto</div>
                                @endif
                                <div class="absolute left-3 top-3 flex gap-1.5">
                                    <span class="rounded-full bg-[#E9F1E6] px-2.5 py-0.5 text-[11px] font-semibold text-[#2E5B2A]">{{ $tipeLabel }}</span>
                                    @if ($row->berprestasi)
                                        <span class="rounded-full bg-[#FEF3C7] px-2.5 py-0.5 text-[11px] font-semibold text-[#B45309]">Berprestasi</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col p-4">
                                <h3 class="line-clamp-2 min-h-[2.5rem] text-[15px] font-bold leading-snug text-[#1C2118]">{{ $row->nama }}</h3>

                                <div class="mt-2 flex flex-wrap gap-1.5 text-[11px] font-semibold text-[#4B5245]">
                                    @if ($row->peruntukan)
                                        <span class="rounded-full bg-[#F6F6F2] px-2 py-0.5">{{ $labelPeruntukan[$row->peruntukan] ?? $row->peruntukan }}</span>
                                    @endif
                                    @if ($row->grade)
                                        <span class="rounded-full bg-[#F6F6F2] px-2 py-0.5">Grade {{ $row->grade }}</span>
                                    @endif
                                    @if ($row->tanggal_produksi)
                                        <span class="rounded-full bg-[#F6F6F2] px-2 py-0.5">Produksi {{ \Illuminate\Support\Carbon::parse($row->tanggal_produksi)->translatedFormat('d M') }}</span>
                                    @endif
                                    @if ($row->butuh_pendingin)
                                        <span class="rounded-full bg-[#E0EAFF] px-2 py-0.5 text-[#1D4ED8]">Simpan dingin</span>
                                    @endif
                                </div>

                                <p class="mt-auto pt-3 text-lg font-extrabold text-[#2E5B2A]">
                                    {{ $rp($row->harga) }} <span class="text-xs font-medium text-[#6B7265]">/ {{ $row->satuan }}</span>
                                </p>
                                <div class="mt-1 flex items-center justify-between text-xs text-[#6B7265]">
                                    <span class="truncate">{{ $row->kota_kabupaten }}, {{ $row->provinsi }}</span>
                                    <span class="ml-2 shrink-0 font-semibold text-[#4B5245]">
                                        {{ $row->tipe === 'individual' ? 'Tersedia' : 'Stok ' . $angka($row->stok) }}
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">{{ $items->links() }}</div>
            @endif
        </section>
    </form>
</div>
@endsection