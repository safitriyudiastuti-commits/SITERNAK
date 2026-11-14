@props(['r'])

@php
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');
    $label = ['bibit' => 'Bibit', 'pembesaran' => 'Pembesaran', 'siap_potong' => 'Siap potong', 'indukan_kontes' => 'Indukan'];

    $nm = mb_strtolower($r->nama);
    $segar = $r->tipe === 'produk' && $r->tanggal_produksi
        && \Illuminate\Support\Carbon::parse($r->tanggal_produksi)->gte(today()->subDays(3));

    [$badge, $badgeWarna] = match (true) {
        (bool) $r->berprestasi    => ['Berprestasi', 'bg-[#FEF3C7] text-[#B45309]'],
        $r->tipe === 'individual' => ['Terverifikasi', 'bg-[#2E5B2A] text-white'],
        $r->tipe === 'batch'      => ['Batch', 'bg-[#1C2118] text-white'],
        $segar                    => ['Segar', 'bg-[#DCF3E3] text-[#166534]'],
        default                   => ['Hasil ternak', 'bg-[#E9F1E6] text-[#2E5B2A]'],
    };

    // Ilustrasi pengganti foto (warna sesuai prototype), dipilih dari kata kunci nama
    [$ikon, $tinta, $latar] = match (true) {
        str_contains($nm, 'telur')                               => ['egg', 'text-[#C2A24A]', 'from-[#F6ECC9] to-[#E7DDBC]'],
        str_contains($nm, 'susu')                                => ['milk', 'text-[#6A9AB8]', 'from-[#E1EEF6] to-[#D3DFE7]'],
        str_contains($nm, 'daging')                              => ['meat', 'text-[#B86E60]', 'from-[#F2DAD4] to-[#E3CCC7]'],
        str_contains($nm, 'bebek')                               => ['duck', 'text-[#5F8F74]', 'from-[#DAE8E0] to-[#CCDAD2]'],
        str_contains($nm, 'domba')                               => ['sheep', 'text-[#7B879A]', 'from-[#E4E8EE] to-[#D6DADF]'],
        str_contains($nm, 'kambing')                             => ['sheep', 'text-[#8C8272]', 'from-[#E9E3DA] to-[#DBD5CC]'],
        str_contains($nm, 'sapi')                                => ['cow', 'text-[#6E8F62]', 'from-[#DCE7D5] to-[#CED9C8]'],
        str_contains($nm, 'ayam') || str_contains($nm, 'unggas') => ['bird', 'text-[#B08A5A]', 'from-[#F2E4D0] to-[#E3D6C3]'],
        $r->tipe === 'produk'                                    => ['egg', 'text-[#C2A24A]', 'from-[#F6ECC9] to-[#E7DDBC]'],
        default                                                  => ['cow', 'text-[#6E8F62]', 'from-[#DCE7D5] to-[#CED9C8]'],
    };

    $tag = match ($r->tipe) {
        'produk' => array_filter([
            $r->grade ? 'Grade ' . $r->grade : null,
            $r->butuh_pendingin ? 'Simpan dingin' : ($r->tanggal_produksi ? 'Produksi ' . \Illuminate\Support\Carbon::parse($r->tanggal_produksi)->translatedFormat('d M') : null),
        ]),
        'batch'  => array_filter([$label[$r->peruntukan] ?? null, 'Per ' . $r->satuan]),
        default  => array_filter(['Individual', $label[$r->peruntukan] ?? null]),
    };
@endphp

{{-- Kartu ternak/produk 357 px (gambar 190): dipakai di katalog; markup sama dengan rekomendasi di Beranda. --}}
<div class="group relative">
<a href="{{ $ke('buyer.katalog.show', [$r->tipe, $r->id]) }}"
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
            @foreach ($tag as $t)
                <span class="flex h-[1.375rem] items-center rounded-md bg-[#F6F6F2] px-2 text-[0.6875rem] font-medium text-[#4B5245]">{{ $t }}</span>
            @endforeach
        </div>
        <p class="mt-4 flex items-baseline gap-[0.28rem] leading-5">
            <span class="text-[1.0625rem] font-bold text-[#2E5B2A]">{{ $rp($r->harga) }}</span>
            <span class="text-xs text-[#6B7265]">/ {{ $r->satuan }}</span>
        </p>
    </div>

    <div class="mx-[0.875rem] flex h-[2.1875rem] shrink-0 items-center justify-between border-t border-[#EDEDE6] text-xs text-[#6B7265]">
        <span class="flex min-w-0 items-center gap-1.5"><x-ikon nama="pin" class="h-3.5 w-3.5 shrink-0" /><span class="truncate">{{ $r->kota }}, {{ $r->provinsi }}</span></span>
        <span class="ml-2 shrink-0 text-[0.71875rem] font-semibold text-[#2E5B2A]">{{ $r->tipe === 'individual' ? 'Tersedia' : 'Stok ' . $angka($r->stok) }}</span>
    </div>
</a>

{{-- Tambah ke keranjang: hanya batch & produk, muncul saat kartu di-hover.
     Memakai atribut form="form-keranjang" karena kartu berada di dalam form filter (form tidak boleh bersarang). --}}
@if (in_array($r->tipe, ['batch', 'produk'], true) && Route::has('buyer.keranjang.tambah'))
    <button type="submit" form="form-keranjang" name="item" value="{{ $r->tipe }}:{{ $r->id }}" title="Tambah ke keranjang" aria-label="Tambah ke keranjang"
            class="absolute right-2.5 top-2.5 flex h-8 w-8 items-center justify-center rounded-full bg-white/95 text-[#2E5B2A] opacity-0 shadow transition hover:bg-[#2E5B2A] hover:text-white focus:opacity-100 group-hover:opacity-100">
        <x-ikon nama="cart" class="h-4 w-4" />
    </button>
@endif
</div>
