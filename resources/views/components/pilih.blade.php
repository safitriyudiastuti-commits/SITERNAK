@props(['tipe' => 'checkbox', 'nama', 'nilai' => '1', 'aktif' => false, 'varian' => 'kotak', 'besar' => false])

{{-- Checkbox / radio / chip untuk filter katalog.
     Pakai: <x-pilih tipe="radio" nama="kelompok" nilai="ternak_hidup" :aktif="..." besar> Ternak hidup </x-pilih>
     'besar' = teks 13 px (kelompok & jenis), tanpa itu 12,5 px (prestasi & kesegaran). --}}
<label class="relative flex cursor-pointer items-center {{ $varian === 'kotak' ? ($tipe === 'radio' ? 'h-7' : 'h-[1.6875rem]') . ' w-full gap-2.5 text-[#1C2118] ' . ($besar ? 'text-[0.8125rem]' : 'text-[0.78125rem]') : '' }}">
    <input type="{{ $tipe }}" name="{{ $nama }}" value="{{ $nilai }}" @checked($aktif) class="peer sr-only">

    @if ($varian === 'chip')
        <span class="flex h-[1.875rem] items-center rounded-full border border-[#E4E4DC] bg-white px-3 text-[0.78125rem] font-medium text-[#4B5245] peer-checked:border-[#2E5B2A] peer-checked:bg-[#2E5B2A] peer-checked:font-semibold peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-[#2E5B2A]/40">{{ $slot }}</span>
    @elseif ($tipe === 'radio')
        <span class="flex h-[1.125rem] w-[1.125rem] shrink-0 items-center justify-center rounded-full border-[1.25px] border-[#C9CEC3] bg-white after:h-[0.5625rem] after:w-[0.5625rem] after:rounded-full after:bg-[#2E5B2A] after:opacity-0 peer-checked:border-2 peer-checked:border-[#2E5B2A] peer-checked:after:opacity-100"></span>
        <span>{{ $slot }}</span>
    @else
        <span class="flex h-[1.125rem] w-[1.125rem] shrink-0 items-center justify-center rounded-[0.3125rem] border-[1.25px] border-[#C9CEC3] bg-white peer-checked:border-[#2E5B2A] peer-checked:bg-[#2E5B2A] peer-checked:[&>svg]:opacity-100">
            <x-ikon nama="check" class="h-3 w-3 text-white opacity-0" sw="3.2" />
        </span>
        <span>{{ $slot }}</span>
    @endif
</label>
