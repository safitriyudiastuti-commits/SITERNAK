@extends('layouts.buyer')

@section('title', $r->nama)

@section('content')
@php
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');

    $labelPeruntukan = [
        'bibit' => 'Bibit',
        'pembesaran' => 'Pembesaran',
        'siap_potong' => 'Siap potong',
        'indukan_kontes' => 'Indukan/kontes',
    ];

    $individual = $r->tipe === 'individual';
    $hidup = $r->tipe !== 'produk';
    $prestasi = $d['prestasi'] ?? [];
    $berprestasi = $r->berprestasi || count($prestasi) > 0;
    $ras = $d['ras'] ?? \Illuminate\Support\Str::words($r->nama, 2, '');
    $jumlahFoto = max(1, count($d['galeri'] ?? []));

    $tgl = fn ($t) => $t
        ? \Illuminate\Support\Carbon::parse($t)->translatedFormat('d F Y')
        : '-';

    $rinci = match ($r->tipe) {
        'individual' => [
            ['Jenis / ras', $ras],
            ['Jenis kelamin', $d['kelamin'] ?? '-'],
            ['Umur', $d['umur'] ?? '-'],
            ['Berat', $d['berat'] ?? '-'],
            ['Peruntukan', $labelPeruntukan[$r->peruntukan] ?? '-'],
            ['Satuan jual', 'Per ' . $r->satuan],
        ],

        'batch' => [
            ['Jenis / ras', $ras],
            ['Stok tersedia', $angka($r->stok) . ' ' . $r->satuan],
            ['Umur', $d['umur'] ?? '-'],
            ['Berat rata-rata', $d['berat'] ?? '-'],
            ['Peruntukan', $labelPeruntukan[$r->peruntukan] ?? '-'],
            ['Satuan jual', 'Per ' . $r->satuan],
        ],

        default => [
            ['Stok tersedia', $angka($r->stok) . ' ' . $r->satuan],
            ['Satuan jual', 'Per ' . $r->satuan],
            ['Grade', $r->grade ? 'Grade ' . $r->grade : '-'],
            ['Penyimpanan', $r->butuh_pendingin ? 'Simpan dingin' : 'Suhu ruang'],
            ['Tanggal produksi', $tgl($r->tanggal_produksi)],
            ['Kedaluwarsa', '-'],
        ],
    };

    $lencana = array_filter([
        $individual
            ? ['Terverifikasi', 'bg-[#2E5B2A] text-white']
            : ($hidup
                ? ['Batch', 'bg-[#1C2118] text-white']
                : ['Hasil ternak', 'bg-[#E9F1E6] text-[#2E5B2A]']),

        $berprestasi
            ? ['Berprestasi', 'bg-[#FEF3C7] text-[#B45309]']
            : null,
    ]);

$urlPesan = $ke('buyer.pemesanan.create', [    'tipe' => $r->tipe,
    'item' => $r->id,
    ]);

    $pill = 'inline-flex h-6 items-center rounded-full px-2 text-[0.71875rem] font-semibold';
@endphp

{{-- Breadcrumb --}}
<nav class="flex items-center text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">
    <a href="{{ $ke('buyer.beranda') }}" class="hover:underline">
        Beranda
    </a>

    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />

    <a href="{{ $ke('buyer.katalog.index') }}" class="hover:underline">
        Katalog Ternak
    </a>

    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />

    <a href="{{ $ke('buyer.katalog.index', ['kelompok' => $hidup ? 'ternak_hidup' : 'hasil_ternak']) }}" class="hover:underline">
        {{ $hidup ? 'Ternak Hidup' : 'Hasil Ternak' }}
    </a>

    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />

    <span class="font-semibold text-[#1C2118]">{{ $ras }}</span>
</nav>

@if (session('success') || session('error'))
    <p class="mt-3 rounded-lg border px-3 py-2 text-[0.8125rem] {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-[#CFE3C9] bg-[#EEF6EB] text-[#1F421C]' }}">
        {{ session('success') ?? session('error') }}
    </p>
@endif

<div class="mt-4 grid grid-cols-1 items-start gap-6 lg:grid-cols-2">

    {{-- ================= Kolom kiri ================= --}}
    <div>

        {{-- Foto utama --}}
        <div class="relative">
            @if ($r->foto)
                <img
                    src="{{ asset('storage/' . $r->foto) }}"
                    alt="{{ $r->nama }}"
                    class="h-[27.5rem] w-full rounded-2xl object-cover"
                >
            @else
                <x-ilustrasi
                    :r="$r"
                    class="h-[27.5rem] rounded-2xl"
                    ikon="h-[11.55rem] w-[11.55rem]"
                    :sw="1.3"
                />
            @endif

            <div class="absolute left-3.5 top-3.5 flex gap-1.5">
                @foreach ($lencana as [$teks, $warna])
                    <span class="{{ $pill }} {{ $warna }}">
                        {{ $teks }}
                    </span>
                @endforeach
            </div>

            <span class="absolute right-3.5 top-3.5 flex h-6 w-[3.25rem] items-center justify-center rounded-full bg-[#1C2118] text-[0.71875rem] font-semibold text-white">
                1 / {{ $jumlahFoto }}
            </span>
        </div>

        {{-- Foto kecil + video --}}
        <div class="mt-4 grid grid-cols-4 gap-3">
            @foreach ([0, 1, 2] as $i)
                <x-ilustrasi
                    :r="$r"
                    class="h-[5.75rem] rounded-xl {{ $i === 0 ? 'ring-[2.5px] ring-inset ring-[#2E5B2A]' : '' }}"
                    ikon="h-10 w-10"
                    :sw="1.3"
                />
            @endforeach

            <div class="flex h-[5.75rem] items-center justify-center rounded-xl bg-[#1C2118]/45">
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-[#1F421C]">
                    <svg viewBox="0 0 24 24" class="h-[0.875rem] w-[0.875rem]" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round">
                        <path d="M8 5l11 7-11 7z"/>
                    </svg>
                </span>
            </div>
        </div>

        {{-- Deskripsi --}}
        <section class="mt-6 rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <h2 class="text-[0.9375rem] font-bold leading-[1.125rem] text-[#1C2118]">
                Deskripsi
            </h2>

            <p class="mt-[0.7rem] whitespace-pre-line text-[0.8125rem] leading-5 text-[#4B5245]">
                {{ $d['deskripsi'] ?? 'Peternak belum menambahkan deskripsi.' }}
            </p>
        </section>
    </div>

    {{-- ================= Kolom kanan ================= --}}
    <div>

        <div class="flex gap-1.5">
            <span class="{{ $pill }} bg-[#EEEEE8] text-[#4B5245]">
                {{ $hidup ? 'Ternak hidup' : 'Hasil ternak' }}
            </span>

            <span class="{{ $pill }} bg-[#E0EAFF] text-[#1D4ED8]">
                {{ ['individual' => 'Individual', 'batch' => 'Batch', 'produk' => 'Produk'][$r->tipe] }}
            </span>

            @if ($r->peruntukan)
                <span class="{{ $pill }} bg-[#E9F1E6] text-[#2E5B2A]">
                    {{ $labelPeruntukan[$r->peruntukan] ?? $r->peruntukan }}
                </span>
            @endif
        </div>

        <h1 class="mt-3 text-[1.625rem] font-extrabold leading-[2.0625rem] text-[#1C2118]">
            {{ $r->nama }}
        </h1>

        <p class="mt-4 flex items-baseline gap-1.5 leading-10">
            <span class="text-[2rem] font-extrabold text-[#2E5B2A]">
                {{ $rp($r->harga) }}
            </span>

            <span class="text-sm text-[#6B7265]">
                / {{ $r->satuan }}
            </span>
        </p>

        {{-- Peternak --}}
        <div class="mt-3 flex h-[4.75rem] items-center rounded-[0.875rem] border border-[#E4E4DC] bg-white px-4">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#D5E4CF] text-[1.045rem] font-bold text-[#2E5B2A]">
                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($r->penjual, 0, 2)) }}
            </span>

            <div class="ml-3.5 min-w-0 leading-tight">
                <p class="truncate text-[0.90625rem] font-bold text-[#1C2118]">
                    {{ $r->penjual }}
                </p>

                <p class="mt-1 flex items-center gap-1 text-[0.78125rem] text-[#6B7265]">
                    <x-ikon nama="pin" class="h-[0.8125rem] w-[0.8125rem]" />
                    {{ $r->kota }}, {{ $r->provinsi }}
                </p>
            </div>

            <span class="ml-auto inline-flex h-[1.375rem] items-center gap-1 rounded-full bg-[#DCF3E3] px-[0.4375rem] text-[0.6875rem] font-semibold text-[#166534]">
                <x-ikon nama="shield" class="h-[0.6875rem] w-[0.6875rem]" sw="2.4" />
                Terverifikasi
            </span>
        </div>

        {{-- Data fisik / informasi --}}
        <section class="mt-4 min-h-[12.375rem] rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <h2 class="text-[0.9375rem] font-bold leading-[1.125rem] text-[#1C2118]">
                {{ $hidup ? 'Data fisik' : 'Informasi produk' }}
            </h2>

            <dl class="mt-[1.1rem] grid grid-cols-2 gap-y-2.5">
                @foreach ($rinci as [$label, $nilai])
                    <div>
                        <dt class="text-[0.71875rem] leading-4 text-[#6B7265]">
                            {{ $label }}
                        </dt>

                        <dd class="text-[0.84375rem] font-semibold leading-5 text-[#1C2118]">
                            {{ $nilai }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Harga + tombol pesan --}}
        <section class="mt-4 rounded-[0.875rem] border border-[#D5E4CF] bg-[#E9F1E6] px-5 pb-5 pt-[1.15rem]">
            <div class="flex items-baseline justify-between">
                <span class="text-[0.78125rem] text-[#6B7265]">
                    Harga per {{ $r->satuan }}
                </span>

                <span class="text-base font-extrabold text-[#1F421C]">
                    {{ $rp($r->harga) }}
                </span>
            </div>

            <a
                href="{{ $urlPesan }}"
                class="mt-3 flex h-[3.125rem] items-center justify-center rounded-[0.625rem] bg-[#2E5B2A] text-[0.9375rem] font-semibold text-white hover:bg-[#1F421C]"
            >
                Pesan Sekarang
            </a>

            @if (! $individual && Route::has('buyer.keranjang.tambah'))
                <form method="POST" action="{{ route('buyer.keranjang.tambah') }}" class="mt-2">
                    @csrf

                    <input
                        type="hidden"
                        name="item"
                        value="{{ $r->tipe }}:{{ $r->id }}"
                    >

                    <button class="flex h-11 w-full items-center justify-center gap-2 rounded-[0.625rem] border border-[#2E5B2A] bg-white text-sm font-semibold text-[#2E5B2A] hover:bg-[#F1F6EE]">
                        <x-ikon nama="cart" class="h-4 w-4" />
                        Tambah ke Keranjang
                    </button>
                </form>
            @endif

            <p class="mt-3 text-[0.71875rem] leading-4 text-[#6B7265]">
                {{ $individual
                    ? 'Setelah dipesan, ternak ini dikunci untuk Anda sampai peternak mengonfirmasi.'
                    : 'Stok yang Anda pesan ditahan sampai peternak mengonfirmasi.' }}

                <br>

                Bayar dengan transfer bank, QRIS, atau COD (satu kota/kabupaten).
            </p>
        </section>
    </div>
</div>

{{-- ================= Silsilah + Riwayat Prestasi ================= --}}
@if ($individual)

    <div class="mt-6 grid grid-cols-1 items-start gap-6 xl:grid-cols-2">

        {{-- Silsilah --}}
        <section class="min-h-[16.75rem] rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-[0.9375rem] font-bold text-[#1C2118]">
                    Silsilah
                </h2>

                <span class="inline-flex h-[1.375rem] items-center rounded-full bg-[#EEEEE8] px-2 text-[0.6875rem] font-semibold text-[#4B5245]">
                    Opsional dari peternak
                </span>
            </div>

            @if (($d['induk'] ?? null) || ($d['pejantan'] ?? null))
                <div class="relative mt-[1.6rem] h-[11.5rem]">

                    <svg viewBox="0 0 520 184" class="absolute inset-0 h-full w-full" fill="none" aria-hidden="true">
                        <path d="M180 68H216V34H244" stroke="#E4E4DC" stroke-width="2"/>
                        <path d="M180 68H216V150H244" stroke="#E4E4DC" stroke-width="2"/>
                    </svg>

                    <div class="absolute left-1 top-[2.125rem] flex h-[4.25rem] w-44 flex-col items-center justify-center rounded-xl border-[1.5px] border-[#2E5B2A] bg-[#E9F1E6]">
                        <span class="text-[0.71875rem] text-[#6B7265]">
                            Ternak ini
                        </span>

                        <span class="mt-0.5 text-[0.84375rem] font-bold text-[#1F421C]">
                            {{ $ras }} {{ $d['kelamin'] ?? '' }}
                        </span>
                    </div>

                    <div class="absolute left-[15.25rem] top-0 flex h-[4.25rem] w-64 flex-col justify-center rounded-xl border border-[#E4E4DC] bg-white px-4">
                        <span class="text-[0.71875rem] text-[#6B7265]">
                            Induk betina
                        </span>

                        <span class="mt-0.5 text-[0.84375rem] font-bold text-[#1C2118]">
                            {{ $d['induk'] ?? '-' }}
                        </span>
                    </div>

                    <div class="absolute left-[15.25rem] top-[7.25rem] flex h-[4.25rem] w-64 flex-col justify-center rounded-xl border border-[#E4E4DC] bg-white px-4">
                        <span class="text-[0.71875rem] text-[#6B7265]">
                            Pejantan
                        </span>

                        <span class="mt-0.5 text-[0.84375rem] font-bold text-[#1C2118]">
                            {{ $d['pejantan'] ?? '-' }}
                        </span>
                    </div>
                </div>
            @else
                <p class="mt-6 text-[0.8125rem] text-[#6B7265]">
                    Peternak belum mengisi silsilah ternak ini.
                </p>
            @endif
        </section>

        {{-- Riwayat Prestasi --}}
        <section class="rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">

            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-[0.9375rem] font-bold text-[#1C2118]">
                    Riwayat prestasi
                </h2>

                <span class="inline-flex h-[1.5rem] items-center gap-1 rounded-full bg-[#DCF3E3] px-2.5 text-[0.6875rem] font-semibold text-[#166534]">
                    <span>✓</span>
                    Hanya yang terverifikasi
                </span>
            </div>

            @forelse ($prestasi as $item)
                <div class="mt-4 flex items-center gap-3 rounded-xl bg-[#F5F6F1] p-4">

                    {{-- Ikon medali --}}
                    <div class="flex h-[3.5rem] w-[3.5rem] shrink-0 items-center justify-center rounded-2xl bg-[#FEF3C7] text-[#B45309]">
                        <svg
                            viewBox="0 0 24 24"
                            class="h-7 w-7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <circle cx="12" cy="8" r="5"/>
                            <path d="m8.5 12-1 9 4.5-2.5 4.5 2.5-1-9"/>
                        </svg>
                    </div>

                    {{-- Informasi prestasi --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-[0.875rem] font-bold text-[#1C2118]">
                            {{ $item['nama'] ?? '-' }}
                        </p>

                        <p class="mt-1 text-[0.75rem] text-[#4B5245]">
                            Kategori {{ $item['kategori'] ?? '-' }}
                            · {{ $item['peringkat'] ?? '-' }}
                        </p>

                        <p class="mt-1 text-[0.6875rem] text-[#6B7265]">
                            {{ !empty($item['tanggal']) ? \Illuminate\Support\Carbon::parse($item['tanggal'])->translatedFormat('d F Y') : '-' }}
                            ·
                            {{ $item['penyelenggara'] ?? '-' }}
                        </p>
                    </div>

                    {{-- Status dan sertifikat --}}
                    <div class="flex shrink-0 flex-col items-end gap-3">

                        <span class="inline-flex h-[1.5rem] items-center gap-1 rounded-full bg-[#DCF3E3] px-2.5 text-[0.6875rem] font-semibold text-[#166534]">
                            ✓ Terverifikasi
                        </span>

                        @if (!empty($item['berkas']))
                            @php
                                $berkas = $item['berkas'];

                                $urlSertifikat = \Illuminate\Support\Str::startsWith($berkas, ['http://', 'https://'])
                                    ? $berkas
                                    : asset('storage/' . ltrim(preg_replace('#^storage/#', '', $berkas), '/'));
                            @endphp

                            <a
                                href="{{ $urlSertifikat }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-[0.75rem] font-bold text-[#1F421C] hover:underline"
                            >
                                Lihat sertifikat
                            </a>
                        @else
                            <span class="text-[0.6875rem] text-[#9AA093]">
                                Sertifikat belum tersedia
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="mt-4 text-sm text-[#9AA093]">
                    Belum ada prestasi kontes yang tercatat.
                </p>
            @endforelse

        </section>
    </div>
@endif

@endsection