@props(['r', 'ikon' => 'h-8 w-8', 'sw' => 1.5])

@php
    $nm = mb_strtolower($r->nama);
    [$gambar, $tinta, $latar] = match (true) {
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
@endphp

<div {{ $attributes->merge(['class' => "flex items-center justify-center bg-gradient-to-br $latar $tinta"]) }}>
    <x-ikon :nama="$gambar" :class="$ikon" :sw="$sw" />
</div>
