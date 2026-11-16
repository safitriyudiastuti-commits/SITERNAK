@extends('layouts.buyer')

@section('title', 'Beranda')

@section('content')
@php
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');
    $label = ['bibit' => 'Bibit', 'pembesaran' => 'Pembesaran', 'siap_potong' => 'Siap potong', 'indukan_kontes' => 'Indukan'];
    $bukaToko = collect(['peternak.buka-toko', 'seller.toko.pengajuan'])->first(fn ($n) => Route::has($n));
    $bukanPeternak = (auth()->user()->role ?? 'pembeli') !== 'peternak';
@endphp

{{-- ===== Hero (304 px, radius 20) ===== --}}
<section class="relative h-[19rem] overflow-hidden rounded-[1.25rem] bg-gradient-to-br from-[#18361A] to-[#2F5F2B]">
    <svg viewBox="0 0 1156 304" preserveAspectRatio="xMaxYMax slice" class="pointer-events-none absolute inset-0 h-full w-full" aria-hidden="true">
        <path d="M0 244C220 184 440 234 660 204S956 174 1156 224V304H0Z" fill="#4C8143"/>
        <path d="M0 274C260 224 520 284 780 254S1036 234 1156 264V304H0Z" fill="#2B5726"/>
        <rect x="906" y="208" width="92" height="62" rx="2" fill="#183615"/>
        <path d="M898 208L952 176L1006 208Z" fill="#183615"/>
        <rect x="560" y="258" width="16" height="8" rx="4" fill="#F1E8D8"/>
        <rect x="610" y="266" width="16" height="8" rx="4" fill="#F1E8D8"/>
        <rect x="660" y="252" width="16" height="8" rx="4" fill="#F1E8D8"/>
        <rect x="760" y="274" width="16" height="8" rx="4" fill="#F1E8D8"/>
    </svg>

    <div class="relative z-10 pl-10 pt-[2.125rem] text-white">
        <span class="inline-flex h-[1.625rem] items-center rounded-full bg-white/15 px-3.5 text-[0.71875rem] font-semibold">Sistem informasi ternak nasional</span>
        <h1 class="mt-[0.875rem] text-[2.375rem] font-extrabold leading-[2.875rem]">Pasar ternak terpercaya<br>untuk Indonesia</h1>
        <p class="mt-[0.625rem] text-[0.9375rem] leading-[1.4375rem] text-[#E4EEDF]">Ternak hidup dan hasil ternak langsung dari peternak terverifikasi<br>di seluruh Indonesia.</p>
        <div class="mt-6 flex gap-[0.875rem] text-sm font-semibold">
            <a href="{{ $ke('buyer.katalog.index') }}" class="flex h-[2.875rem] w-[10.625rem] items-center justify-center rounded-[0.625rem] bg-white text-[#2E5B2A] hover:bg-[#F1F3EE]">Jelajahi Katalog</a>
            <a href="{{ $ke('buyer.cara-memesan') }}" class="flex h-[2.875rem] w-[9.375rem] items-center justify-center rounded-[0.625rem] border border-white hover:bg-white/10">Cara Memesan</a>
        </div>
    </div>
</section>

{{-- ===== Keunggulan (3 kartu, tinggi 96) ===== --}}
<section class="mt-6 grid grid-cols-3 gap-6">
    @foreach ([
        ['shield', 'Peternak terverifikasi', 'Setiap akun peternak diperiksa admin<br>sebelum boleh berjualan.'],
        ['trend', 'Harga langsung peternak', 'Beli tanpa perantara, harga tercantum jelas<br>per ekor, kg, atau satuan.'],
        ['card2', 'Tiga cara membayar', 'Transfer bank, QRIS, atau COD untuk<br>pembeli satu kota/kabupaten.'],
    ] as [$ikon, $judul, $isi])
        <div class="flex h-24 items-center rounded-[0.875rem] border border-[#E4E4DC] bg-white px-[1.125rem]">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#E9F1E6] text-[#2E5B2A]"><x-ikon :nama="$ikon" class="h-[1.375rem] w-[1.375rem]" sw="1.8" /></span>
            <div class="ml-[0.875rem]">
                <p class="text-sm font-bold leading-[1.125rem] text-[#1C2118]">{{ $judul }}</p>
                <p class="mt-[0.3125rem] text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">{!! $isi !!}</p>
            </div>
        </div>
    @endforeach
</section>

{{-- ===== Kategori pilihan ===== --}}
<section class="mt-10">
    <div class="flex items-baseline justify-between">
        <h2 class="text-[1.375rem] font-bold leading-7 text-[#1C2118]">Kategori pilihan</h2>
        <a href="{{ $ke('buyer.katalog.index') }}" class="text-[0.8125rem] font-semibold text-[#2E5B2A] hover:underline">Lihat semua kategori</a>
    </div>
    <div class="mt-4 grid grid-cols-6 gap-4">
        @foreach ($kategori as [$nama, $sub, $ikon, $kotak, $warnaIkon, $param])
            <a href="{{ $ke('buyer.katalog.index', $param) }}" class="flex h-28 flex-col items-center rounded-[0.875rem] border border-[#E4E4DC] bg-white pt-[0.875rem] transition hover:border-[#2E5B2A]">
                <span class="flex h-[3.25rem] w-[3.25rem] items-center justify-center rounded-2xl {{ $kotak }} {{ $warnaIkon }}"><x-ikon :nama="$ikon" class="h-7 w-7" sw="1.7" /></span>
                <span class="mt-2 text-[0.84375rem] font-bold leading-[1.125rem] text-[#1C2118]">{{ $nama }}</span>
                <span class="text-[0.6875rem] leading-4 text-[#6B7265]">{{ $sub }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- ===== Rekomendasi ===== --}}
<section class="mt-10">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-[1.375rem] font-bold leading-7 text-[#1C2118]">Rekomendasi untuk Anda</h2>
            <p class="mt-1 text-[0.8125rem] leading-[1.125rem] text-[#6B7265]">Pilihan terbaik dari peternak di seluruh Indonesia</p>
        </div>
        <div class="flex gap-2 text-[0.78125rem]">
            @foreach (['semua' => 'Semua', 'ternak_hidup' => 'Ternak Hidup', 'hasil_ternak' => 'Hasil Ternak'] as $nilai => $teks)
                <button type="button" data-filter="{{ $nilai }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                        class="h-9 rounded-full border border-[#E4E4DC] bg-white px-4 font-medium text-[#4B5245] aria-pressed:border-[#2E5B2A] aria-pressed:bg-[#2E5B2A] aria-pressed:font-semibold aria-pressed:text-white">{{ $teks }}</button>
            @endforeach
        </div>
    </div>

    @if ($rekomendasi->isEmpty())
        <div class="mt-5 rounded-[0.875rem] border border-dashed border-[#E4E4DC] bg-white p-12 text-center">
            <p class="font-semibold text-[#1C2118]">Belum ada ternak atau hasil ternak yang ditampilkan.</p>
            <p class="mt-1 text-sm text-[#6B7265]">Daftar ini terisi otomatis saat peternak mulai menambahkan dagangannya.</p>
        </div>
    @else
        <div class="mt-5 grid grid-cols-4 gap-6">
            @foreach ($rekomendasi as $r)
                @php
                    $nm = mb_strtolower($r->nama);
                    $hidup = $r->tipe !== 'produk';
                    $segar = $r->tipe === 'produk' && $r->tanggal_produksi
                        && \Illuminate\Support\Carbon::parse($r->tanggal_produksi)->gte(today()->subDays(3));

                    [$badge, $badgeWarna] = match (true) {
                        (bool) $r->berprestasi    => ['Berprestasi', 'bg-[#FEF3C7] text-[#B45309]'],
                        $r->tipe === 'individual' => ['Terverifikasi', 'bg-[#2E5B2A] text-white'],
                        $r->tipe === 'batch'      => ['Batch', 'bg-[#1C2118] text-white'],
                        $segar                    => ['Segar', 'bg-[#DCF3E3] text-[#166534]'],
                        default                   => ['Hasil ternak', 'bg-[#E9F1E6] text-[#2E5B2A]'],
                    };

                    // Ilustrasi pengganti foto: dipilih dari kata kunci nama (tinta + warna latar sesuai prototype)
                    [$ikon, $tinta, $latar] = match (true) {
                        str_contains($nm, 'telur')                                  => ['egg', 'text-[#C2A24A]', 'from-[#F6ECC9] to-[#E7DDBC]'],
                        str_contains($nm, 'susu')                                   => ['milk', 'text-[#6A9AB8]', 'from-[#E1EEF6] to-[#D3DFE7]'],
                        str_contains($nm, 'daging')                                 => ['meat', 'text-[#B86E60]', 'from-[#F2DAD4] to-[#E6C9C2]'],
                        str_contains($nm, 'bebek')                                  => ['duck', 'text-[#5F8F74]', 'from-[#DAE8E0] to-[#CCDAD2]'],
                        str_contains($nm, 'domba')                                  => ['sheep', 'text-[#7B879A]', 'from-[#E4E8EE] to-[#D6DADF]'],
                        str_contains($nm, 'kambing')                                => ['sheep', 'text-[#8C8272]', 'from-[#E9E3DA] to-[#DBD5CC]'],
                        str_contains($nm, 'sapi')                                   => ['cow', 'text-[#6E8F62]', 'from-[#DCE7D5] to-[#CED9C8]'],
                        str_contains($nm, 'ayam') || str_contains($nm, 'unggas')    => ['bird', 'text-[#B08A5A]', 'from-[#F2E4D0] to-[#E3D6C3]'],
                        $r->tipe === 'produk'                                       => ['egg', 'text-[#C2A24A]', 'from-[#F6ECC9] to-[#E7DDBC]'],
                        default                                                     => ['cow', 'text-[#6E8F62]', 'from-[#DCE7D5] to-[#CED9C8]'],
                    };
                @endphp

                <a href="{{ $ke('buyer.katalog.show', [$r->tipe, $r->id]) }}" data-kelompok="{{ $hidup ? 'ternak_hidup' : 'hasil_ternak' }}"
                   class="flex h-[22.3125rem] flex-col overflow-hidden rounded-[0.875rem] border border-[#E4E4DC] bg-white transition hover:border-[#2E5B2A]">
                    <div class="relative h-[11.875rem] shrink-0 bg-gradient-to-br {{ $latar }}">
                        @if ($r->foto)
                            <img src="{{ asset('storage/' . $r->foto) }}" alt="{{ $r->nama }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center {{ $tinta }}"><x-ikon :nama="$ikon" class="h-20 w-20" sw="1.3" /></div>
                        @endif
                        <span class="absolute left-2.5 top-2.5 flex h-[1.375rem] items-center rounded-full px-2 text-[0.6875rem] font-semibold {{ $badgeWarna }}">{{ $badge }}</span>
                    </div>

                    <div class="flex-1 px-[0.875rem] pt-[0.875rem]">
                        <h3 class="line-clamp-2 min-h-[2.375rem] text-sm font-semibold leading-[1.1875rem] text-[#1C2118]">{{ $r->nama }}</h3>

                        <div class="mt-2 flex gap-1.5">
                            @php
                                $tag = match ($r->tipe) {
                                    'produk'     => array_filter([$r->grade ? 'Grade ' . $r->grade : null, $r->butuh_pendingin ? 'Simpan dingin' : ($r->tanggal_produksi ? 'Produksi ' . \Illuminate\Support\Carbon::parse($r->tanggal_produksi)->translatedFormat('d M') : null)]),
                                    'batch'      => array_filter([$label[$r->peruntukan] ?? null, 'Per ' . $r->satuan]),
                                    default      => array_filter(['Individual', $label[$r->peruntukan] ?? null]),
                                };
                            @endphp
                            @foreach ($tag as $t)
                                <span class="flex h-[1.375rem] items-center rounded-md bg-[#F6F6F2] px-2 text-[0.6875rem] font-medium text-[#4B5245]">{{ $t }}</span>
                            @endforeach
                        </div>

                        <p class="mt-4 flex items-baseline gap-1.5 leading-5">
                            <span class="text-[1.0625rem] font-bold text-[#2E5B2A]">{{ $rp($r->harga) }}</span>
                            <span class="text-xs text-[#6B7265]">/ {{ $r->satuan }}</span>
                        </p>
                    </div>

                    <div class="mx-[0.875rem] flex h-[2.1875rem] shrink-0 items-center justify-between border-t border-[#EDEDE6] text-xs text-[#6B7265]">
                        <span class="flex min-w-0 items-center gap-1.5"><x-ikon nama="pin" class="h-3.5 w-3.5 shrink-0" /><span class="truncate">{{ $r->kota }}, {{ $r->provinsi }}</span></span>
                        <span class="ml-2 shrink-0 text-[0.71875rem] font-semibold text-[#2E5B2A]">{{ $r->tipe === 'individual' ? 'Tersedia' : 'Stok ' . $angka($r->stok) }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-9 flex justify-center">
            <a href="{{ $ke('buyer.katalog.index') }}" class="flex h-[2.875rem] w-[12.5rem] items-center justify-center gap-2 rounded-[0.625rem] border border-[#E4E4DC] bg-white text-sm font-semibold text-[#1C2118] hover:border-[#2E5B2A]">
                <x-ikon nama="chevron" class="h-4 w-4" /> Lihat Lebih Banyak
            </a>
        </div>
    @endif
</section>

{{-- ===== Ajakan buka toko (tinggi 132) ===== --}}
@if ($bukanPeternak)
    <section class="mt-10 flex h-[8.25rem] items-center justify-between rounded-[1.25rem] bg-gradient-to-r from-[#1B3A18] to-[#2B5726] px-10 text-white">
        <div>
            <h2 class="text-[1.375rem] font-bold leading-7">Punya ternak atau hasil ternak untuk dijual?</h2>
            <p class="mt-1 text-[0.84375rem] text-[#D5E4CF]">Buka Toko Saya dari akun yang sama, lengkapi data usaha, dan mulai berjualan setelah diverifikasi admin.</p>
        </div>
        <a href="{{ $bukaToko ? route($bukaToko) : '#' }}" class="flex h-[2.875rem] w-40 shrink-0 items-center justify-center rounded-[0.625rem] bg-white text-sm font-semibold text-[#2E5B2A] hover:bg-[#F1F3EE]">Buka Toko Saya</a>
    </section>
@endif
@endsection

@push('scripts')
<script>
    // Filter rekomendasi (Semua / Ternak Hidup / Hasil Ternak) tanpa reload halaman
    document.querySelectorAll('[data-filter]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var pilihan = tombol.dataset.filter;
            document.querySelectorAll('[data-filter]').forEach(function (t) {
                t.setAttribute('aria-pressed', t === tombol ? 'true' : 'false');
            });
            document.querySelectorAll('[data-kelompok]').forEach(function (kartu) {
                kartu.classList.toggle('hidden', pilihan !== 'semua' && kartu.dataset.kelompok !== pilihan);
            });
        });
    });
</script>
@endpush
