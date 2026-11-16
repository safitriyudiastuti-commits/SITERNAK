@extends('layouts.buyer')

@section('title', 'Katalog Ternak')

@section('content')
@php
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';
    $f = $filter;
    $dipilih = fn (string $k, $v) => in_array($v, (array) ($f[$k] ?? []), true);
    $kelompokAktif = $f['kelompok'] ?? '';
    $urutAktif = $f['urut'] ?? 'terbaru';

    $filterAktif = collect(['q', 'kelompok', 'jenis', 'peruntukan', 'harga_min', 'harga_maks', 'berprestasi', 'kesegaran', 'provinsi', 'kota', 'kecamatan', 'satuan'])
        ->contains(fn ($k) => ! empty($f[$k]));

    $labelPeruntukan = ['bibit' => 'Bibit', 'pembesaran' => 'Pembesaran', 'siap_potong' => 'Siap potong', 'indukan_kontes' => 'Indukan/kontes'];
    $labelSatuan = ['ekor' => 'Per ekor', 'kg' => 'Per kg', 'batch' => 'Per batch', 'butir' => 'Per butir', 'liter' => 'Per liter'];

    $kotakSelect = 'h-[2.375rem] w-full appearance-none rounded-[0.625rem] border border-[#E4E4DC] bg-white pl-3.5 pr-9 text-[0.84375rem] font-normal outline-none focus:border-[#2E5B2A] disabled:text-[#9AA093]';
    $judulFilter = 'text-[0.8125rem] font-bold leading-4 text-[#1C2118]';
    $petunjuk = 'text-[0.6875rem] font-normal text-[#9AA093]';

    // Nomor halaman: 1 2 3 … terakhir
    $terakhir = $items->lastPage();
    $sekarang = $items->currentPage();
    if ($terakhir <= 6) {
        $nomor = range(1, $terakhir);
    } else {
        $mulai = max(1, min($sekarang - 1, $terakhir - 2));
        $nomor = array_values(array_unique(array_merge([1], range($mulai, min($terakhir, $mulai + 2)), [$terakhir])));
        sort($nomor);
    }
    $daftarHalaman = [];
    $sebelum = 0;
    foreach ($nomor as $n) {
        if ($sebelum && $n - $sebelum > 1) { $daftarHalaman[] = '…'; }
        $daftarHalaman[] = $n;
        $sebelum = $n;
    }
    $kotakHalaman = 'flex h-10 w-10 items-center justify-center rounded-[0.625rem] border text-[0.84375rem] font-semibold';
@endphp

{{-- Breadcrumb + judul --}}
<nav class="flex items-center text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">
    <a href="{{ $ke('buyer.beranda') }}" class="hover:underline">Beranda</a>
    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />
    <span class="font-semibold text-[#1C2118]">Katalog Ternak</span>
</nav>
<h1 class="mt-3 text-[1.75rem] font-extrabold leading-[2.125rem] text-[#1C2118]">Katalog ternak dan hasil ternak</h1>
<p class="mt-[0.4375rem] text-sm leading-5 text-[#6B7265]">Telusuri ternak hidup dan hasil ternak dari peternak di seluruh Indonesia.</p>

@if (session('success') || session('error'))
    <p class="mt-3 rounded-lg border px-3 py-2 text-[0.8125rem] {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-[#CFE3C9] bg-[#EEF6EB] text-[#1F421C]' }}">{{ session('success') ?? session('error') }}</p>
@endif

@if ($dataContoh)
    <p class="mt-3 rounded-lg border border-[#F2D58A] bg-[#FEF8E1] px-3 py-2 text-xs text-[#8A6A00]">
        Menampilkan <b>data contoh sementara</b> (tabel ternak/produk dari tim peternak belum tersedia). Bagian ini hanya muncul di lingkungan local.
    </p>
@endif

<form method="GET" action="{{ route('buyer.katalog.index') }}" class="mt-6 grid grid-cols-[17.75rem_minmax(0,1fr)] items-start gap-6">

    {{-- ===== Panel filter (lebar 284) ===== --}}
    <aside class="rounded-[0.875rem] border border-[#E4E4DC] bg-white">
        <div class="flex h-14 items-center justify-between border-b border-[#E4E4DC] px-5">
            <h2 class="text-base font-bold text-[#1C2118]">Filter</h2>
            <a href="{{ route('buyer.katalog.index') }}" class="text-[0.78125rem] font-semibold text-[#2E5B2A] hover:underline">Atur ulang</a>
        </div>

        <div class="px-5 pb-[1.375rem] pt-4">
            {{-- Kelompok komoditas --}}
            <section>
                <p class="{{ $judulFilter }}">Kelompok komoditas</p>
                <div class="mt-[0.4rem]">
                    <x-pilih tipe="radio" nama="kelompok" nilai="" besar :aktif="$kelompokAktif === ''">Semua</x-pilih>
                    <x-pilih tipe="radio" nama="kelompok" nilai="ternak_hidup" besar :aktif="$kelompokAktif === 'ternak_hidup'">Ternak hidup</x-pilih>
                    <x-pilih tipe="radio" nama="kelompok" nilai="hasil_ternak" besar :aktif="$kelompokAktif === 'hasil_ternak'">Hasil ternak</x-pilih>
                </div>
            </section>

            {{-- Kategori dan jenis --}}
            <section class="mt-[0.85rem]">
                <p class="{{ $judulFilter }}">Kategori dan jenis</p>
                @foreach ($jenisGrup as $grup => $daftar)
                    <p class="{{ $loop->first ? 'mt-[0.7rem]' : 'mt-[0.29rem]' }} text-[0.71875rem] font-semibold leading-4 text-[#6B7265]">{{ $grup }}</p>
                    <div class="mt-[0.1rem]">
                        @foreach ($daftar as $jenis)
                            <x-pilih nama="jenis[]" :nilai="$jenis" besar :aktif="$dipilih('jenis', $jenis)">{{ $jenis }}</x-pilih>
                        @endforeach
                    </div>
                @endforeach
            </section>

            {{-- Peruntukan --}}
            <section class="mt-[0.7rem]">
                <p class="{{ $judulFilter }} flex items-baseline justify-between">Peruntukan <span class="{{ $petunjuk }}">ternak hidup</span></p>
                <div class="mt-[0.7rem] flex flex-wrap gap-2">
                    @foreach ($labelPeruntukan as $nilai => $teks)
                        <x-pilih varian="chip" nama="peruntukan[]" :nilai="$nilai" :aktif="$dipilih('peruntukan', $nilai)">{{ $teks }}</x-pilih>
                    @endforeach
                </div>
            </section>

            {{-- Rentang harga --}}
            <section class="mt-[0.92rem]">
                <p class="{{ $judulFilter }}">Rentang harga</p>
                <div class="mt-[0.7rem] grid grid-cols-2 gap-3">
                    <input type="number" min="0" name="harga_min" value="{{ $f['harga_min'] ?? '' }}" placeholder="Rp Min"
                           class="h-[2.375rem] w-full rounded-[0.625rem] border border-[#E4E4DC] bg-white px-3.5 text-[0.84375rem] outline-none placeholder:text-[#9AA093] focus:border-[#2E5B2A]">
                    <input type="number" min="0" name="harga_maks" value="{{ $f['harga_maks'] ?? '' }}" placeholder="Rp Maks"
                           class="h-[2.375rem] w-full rounded-[0.625rem] border border-[#E4E4DC] bg-white px-3.5 text-[0.84375rem] outline-none placeholder:text-[#9AA093] focus:border-[#2E5B2A]">
                </div>
            </section>

            {{-- Prestasi kontes --}}
            <section class="mt-[1.17rem]">
                <p class="{{ $judulFilter }} flex items-baseline justify-between">Prestasi kontes <span class="{{ $petunjuk }}">ternak hidup</span></p>
                <div class="mt-[0.425rem]">
                    <x-pilih nama="berprestasi" nilai="1" :aktif="! empty($f['berprestasi'])">Hanya yang berprestasi terverifikasi</x-pilih>
                </div>
            </section>

            {{-- Kesegaran --}}
            <section class="mt-[0.76rem]">
                <p class="{{ $judulFilter }} flex items-baseline justify-between">Kesegaran <span class="{{ $petunjuk }}">hasil ternak</span></p>
                <div class="mt-[0.4rem]">
                    <x-pilih tipe="radio" nama="kesegaran" nilai="" :aktif="empty($f['kesegaran'])">Semua</x-pilih>
                    <x-pilih tipe="radio" nama="kesegaran" nilai="3" :aktif="($f['kesegaran'] ?? '') === '3'">Produksi ≤ 3 hari lalu</x-pilih>
                    <x-pilih tipe="radio" nama="kesegaran" nilai="7" :aktif="($f['kesegaran'] ?? '') === '7'">Produksi ≤ 7 hari lalu</x-pilih>
                </div>
            </section>

            {{-- Wilayah peternak --}}
            <section class="mt-[0.86rem]">
                <p class="{{ $judulFilter }}">Wilayah peternak</p>
                <div class="mt-[0.7rem] space-y-2.5">
                    <div class="relative">
                        <select name="provinsi" class="{{ $kotakSelect }} {{ empty($f['provinsi']) ? 'text-[#9AA093]' : 'font-medium' }}"
                                onchange="this.form.kota.value=''; this.form.kecamatan.value=''; this.form.submit()">
                            <option value="">Semua provinsi</option>
                            @foreach ($provinsiOptions as $opt)
                                <option value="{{ $opt }}" @selected(($f['provinsi'] ?? '') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <x-ikon nama="chevron" class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6B7265]" />
                    </div>
                    <div class="relative">
                        <select name="kota" @disabled($kotaOptions->isEmpty()) class="{{ $kotakSelect }} {{ empty($f['kota']) ? 'text-[#9AA093]' : 'font-medium' }}"
                                onchange="this.form.kecamatan.value=''; this.form.submit()">
                            <option value="">Semua kota/kabupaten</option>
                            @foreach ($kotaOptions as $opt)
                                <option value="{{ $opt }}" @selected(($f['kota'] ?? '') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <x-ikon nama="chevron" class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6B7265]" />
                    </div>
                    <div class="relative">
                        <select name="kecamatan" @disabled($kecamatanOptions->isEmpty()) class="{{ $kotakSelect }} {{ empty($f['kecamatan']) ? 'text-[#9AA093]' : 'font-medium' }}">
                            <option value="">Semua kecamatan</option>
                            @foreach ($kecamatanOptions as $opt)
                                <option value="{{ $opt }}" @selected(($f['kecamatan'] ?? '') === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                        <x-ikon nama="chevron" class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6B7265]" />
                    </div>
                </div>
            </section>

            {{-- Satuan jual --}}
            <section class="mt-[1.04rem]">
                <p class="{{ $judulFilter }}">Satuan jual</p>
                <div class="mt-[0.7rem] flex flex-wrap gap-2">
                    @foreach ($labelSatuan as $nilai => $teks)
                        <x-pilih varian="chip" tipe="radio" nama="satuan" :nilai="$nilai" :aktif="($f['satuan'] ?? '') === $nilai">{{ $teks }}</x-pilih>
                    @endforeach
                </div>
            </section>

            <button type="submit" class="mt-5 h-11 w-full rounded-[0.625rem] bg-[#2E5B2A] text-sm font-semibold text-white hover:bg-[#1F421C]">Terapkan Filter</button>
        </div>
    </aside>

    {{-- ===== Hasil ===== --}}
    <section class="min-w-0">
        <div class="flex items-start justify-between">
            <div class="flex gap-2 text-[0.78125rem]">
                @foreach (['' => 'Semua', 'ternak_hidup' => 'Ternak Hidup', 'hasil_ternak' => 'Hasil Ternak'] as $nilai => $teks)
                    <button type="submit" name="kelompok" value="{{ $nilai }}"
                            class="h-9 rounded-full border px-4 {{ $kelompokAktif === $nilai ? 'border-[#2E5B2A] bg-[#2E5B2A] font-semibold text-white' : 'border-[#E4E4DC] bg-white font-medium text-[#4B5245] hover:border-[#2E5B2A]' }}">{{ $teks }}</button>
                @endforeach
            </div>

            <div class="relative w-[12.5rem]">
                <select name="urut" onchange="this.form.submit()"
                        class="h-9 w-full appearance-none rounded-[0.625rem] border border-[#E4E4DC] bg-white pl-3 pr-9 text-[0.78125rem] font-medium text-[#1C2118] outline-none focus:border-[#2E5B2A]">
                    <option value="terbaru" @selected($urutAktif === 'terbaru')>Urutkan: Terbaru</option>
                    <option value="harga_asc" @selected($urutAktif === 'harga_asc')>Urutkan: Harga terendah</option>
                    <option value="harga_desc" @selected($urutAktif === 'harga_desc')>Urutkan: Harga tertinggi</option>
                </select>
                <x-ikon nama="chevron" class="pointer-events-none absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#6B7265]" />
            </div>
        </div>

        <p class="mt-[0.9375rem] text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">
            @if ($items->total() > 0)
                Menampilkan {{ $items->firstItem() }}–{{ $items->lastItem() }} dari {{ number_format($items->total(), 0, ',', '.') }} produk
            @else
                Tidak ada produk yang ditampilkan
            @endif
        </p>

        @if ($items->isEmpty())
            {{-- ===== Tampilan kosong + saran ===== --}}
            <div class="mt-[0.9375rem] rounded-[0.875rem] border border-[#E4E4DC] bg-white px-8 py-14 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#E9F1E6] text-[#2E5B2A]"><x-ikon nama="search" class="h-6 w-6" sw="1.8" /></span>

                @if ($filterAktif)
                    <h3 class="mt-4 text-base font-bold text-[#1C2118]">Tidak ada produk yang cocok dengan filter Anda</h3>
                    <p class="mt-1 text-sm text-[#6B7265]">Coba salah satu saran berikut:</p>
                    <ul class="mx-auto mt-4 max-w-md space-y-1.5 text-left text-[0.8125rem] text-[#4B5245]">
                        <li class="flex gap-2"><x-ikon nama="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#2E5B2A]" sw="2.4" /> Kurangi jumlah filter yang dipilih.</li>
                        @if (isset($f['harga_min']) || isset($f['harga_maks']))
                            <li class="flex gap-2"><x-ikon nama="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#2E5B2A]" sw="2.4" /> Perluas rentang harga.</li>
                        @endif
                        @if (! empty($f['provinsi']) || ! empty($f['kota']) || ! empty($f['kecamatan']))
                            <li class="flex gap-2"><x-ikon nama="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#2E5B2A]" sw="2.4" /> Pilih wilayah yang lebih luas, misalnya semua kota/kabupaten.</li>
                        @endif
                        @if (! empty($f['q']))
                            <li class="flex gap-2"><x-ikon nama="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#2E5B2A]" sw="2.4" /> Gunakan kata kunci yang lebih umum.</li>
                        @endif
                        <li class="flex gap-2"><x-ikon nama="check" class="mt-0.5 h-4 w-4 shrink-0 text-[#2E5B2A]" sw="2.4" /> Coba kelompok lain (Ternak Hidup atau Hasil Ternak).</li>
                    </ul>
                    <a href="{{ route('buyer.katalog.index') }}" class="mt-6 inline-flex h-11 items-center rounded-[0.625rem] bg-[#2E5B2A] px-6 text-sm font-semibold text-white hover:bg-[#1F421C]">Atur ulang filter</a>
                @else
                    <h3 class="mt-4 text-base font-bold text-[#1C2118]">Belum ada ternak atau hasil ternak</h3>
                    <p class="mt-1 text-sm text-[#6B7265]">Katalog akan terisi saat peternak mulai menambahkan dagangannya.</p>
                @endif
            </div>
        @else
            <div class="mt-[0.9375rem] grid grid-cols-3 gap-5">
                @foreach ($items as $r)
                    <x-kartu-ternak :r="$r" />
                @endforeach
            </div>

            {{-- ===== Pagination (tombol 40 x 40) ===== --}}
            @if ($terakhir > 1)
                <nav class="mt-7 flex justify-center gap-1" aria-label="Halaman">
                    <a href="{{ $items->previousPageUrl() ?? '#' }}" class="{{ $kotakHalaman }} border-[#E4E4DC] bg-white text-[#4B5245] {{ $items->onFirstPage() ? 'pointer-events-none opacity-50' : 'hover:border-[#2E5B2A]' }}" aria-label="Sebelumnya">‹</a>
                    @foreach ($daftarHalaman as $h)
                        @if ($h === '…')
                            <span class="{{ $kotakHalaman }} border-[#E4E4DC] bg-white text-[#4B5245]">…</span>
                        @else
                            <a href="{{ $items->url($h) }}" class="{{ $kotakHalaman }} {{ $h === $sekarang ? 'border-[#2E5B2A] bg-[#2E5B2A] text-white' : 'border-[#E4E4DC] bg-white text-[#4B5245] hover:border-[#2E5B2A]' }}">{{ $h }}</a>
                        @endif
                    @endforeach
                    <a href="{{ $items->nextPageUrl() ?? '#' }}" class="{{ $kotakHalaman }} border-[#E4E4DC] bg-white text-[#4B5245] {{ $items->hasMorePages() ? 'hover:border-[#2E5B2A]' : 'pointer-events-none opacity-50' }}" aria-label="Berikutnya">›</a>
                </nav>
            @endif
        @endif
    </section>
</form>

{{-- Form tersendiri untuk tombol keranjang di kartu (form tidak boleh bersarang) --}}
@if (Route::has('buyer.keranjang.tambah'))
    <form id="form-keranjang" method="POST" action="{{ route('buyer.keranjang.tambah') }}">@csrf</form>
@endif
@endsection
