@extends('layouts.seller')
@section('title', 'Profil Toko')
@section('content')
<form id="form-profil" method="POST" action="{{ route('seller.toko.profil.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <p class="text-xs text-gray-500 mb-2">Toko Saya › <b class="text-gray-800">Profil Toko</b></p>
            <h1 class="text-2xl font-bold text-gray-900">Profil toko</h1>
            <p class="text-sm text-gray-500">Informasi ini akan dilihat pembeli di halaman toko Anda.</p>
        </div>
        <button type="button"
                onclick="if (document.getElementById('form-profil').reportValidity()) document.getElementById('konfirmasi').showModal()"
                class="bg-[#2d5a27] hover:bg-[#234a1f] text-white text-sm font-medium px-5 py-2 rounded-lg whitespace-nowrap">
            ✓ Simpan Perubahan
        </button>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6 space-y-5">
            <h2 class="font-semibold text-gray-900">Informasi toko</h2>

            {{-- Logo --}}
            <div class="flex items-center gap-4">
                @if ($store->logo_path)
                    <img src="{{ Storage::url($store->logo_path) }}" alt="Logo" class="w-14 h-14 rounded-lg object-cover border">
                @else
                    <span class="w-14 h-14 rounded-lg bg-[#eef3ea] text-[#2d5a27] text-xl font-bold flex items-center justify-center">{{ mb_strtoupper(mb_substr($store->name, 0, 1)) }}</span>
                @endif
                <label class="cursor-pointer text-sm border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                    <span id="logo-label">Ubah logo</span>
                    <input type="file" name="logo" accept="image/*" class="sr-only"
                           onchange="document.getElementById('logo-label').textContent = this.files[0] ? this.files[0].name : 'Ubah logo'">
                </label>
                <span class="text-xs text-gray-400">JPG/PNG/WEBP, maks. 2 MB</span>
            </div>

            <label class="block text-sm font-medium">Nama toko <span class="text-red-600">*</span>
                <input id="f-name" name="name" value="{{ old('name', $store->name) }}" required
                       class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 font-normal focus:outline-none focus:ring-2 focus:ring-[#2d5a27]">
            </label>

            <label class="block text-sm font-medium">Deskripsi <span class="text-red-600">*</span>
                <textarea id="f-desc" name="description" rows="3" required
                          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 font-normal focus:outline-none focus:ring-2 focus:ring-[#2d5a27]">{{ old('description', $store->description) }}</textarea>
            </label>

            <div class="grid md:grid-cols-2 gap-4">
                <fieldset>
                    <legend class="text-sm font-medium">Kategori usaha <span class="text-red-600">*</span></legend>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($kategori as $v => $l)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="categories[]" value="{{ $v }}" class="peer sr-only"
                                       @checked(in_array($v, old('categories', $store->business_categories ?? [])))>
                                <span class="inline-block px-3 py-1 rounded-full border border-gray-300 text-xs text-gray-600
                                             peer-checked:bg-[#2d5a27] peer-checked:text-white peer-checked:border-[#2d5a27]">{{ $l }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <label class="block text-sm font-medium">Nomor kontak (WhatsApp)
                    <input name="contact_phone" value="{{ old('contact_phone', $store->contact_phone) }}" placeholder="0812 3456 7890"
                           class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 font-normal focus:outline-none focus:ring-2 focus:ring-[#2d5a27]">
                </label>
            </div>

            <label class="block text-sm font-medium">Alamat usaha <span class="text-red-600">*</span>
                <textarea name="address" rows="2" required
                          class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 font-normal focus:outline-none focus:ring-2 focus:ring-[#2d5a27]">{{ old('address', $store->address) }}</textarea>
            </label>

            {{-- Jam operasional --}}
            <div>
                <p class="text-sm font-medium mb-2">Jam operasional</p>
                <div class="grid md:grid-cols-3 gap-4">
                    @foreach ($groups as $g => $label)
                        <div class="rounded-lg border border-gray-200 p-3 text-sm space-y-2">
                            <p class="font-medium">{{ $label }}</p>
                            <div class="flex items-center gap-2">
                                <input type="time" name="hours[{{ $g }}][open]" value="{{ old("hours.$g.open", $hours[$g]['open']) }}"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1">
                                <span>–</span>
                                <input type="time" name="hours[{{ $g }}][close]" value="{{ old("hours.$g.close", $hours[$g]['close']) }}"
                                       class="w-full border border-gray-300 rounded-lg px-2 py-1">
                            </div>
                            <label class="flex items-center gap-2 text-xs text-gray-600">
                                <input type="checkbox" name="hours[{{ $g }}][closed]" value="1" class="accent-[#2d5a27]"
                                       @checked(old("hours.$g.closed", $hours[$g]['closed']))> Tutup
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Pratinjau --}}
        <aside class="bg-white rounded-xl border border-gray-200 p-5 text-sm space-y-3">
            <h2 class="font-semibold text-gray-900">Pratinjau halaman toko</h2>
            <div class="flex items-center gap-3">
                @if ($store->logo_path)
                    <img src="{{ Storage::url($store->logo_path) }}" alt="" class="w-10 h-10 rounded-lg object-cover">
                @else
                    <span class="w-10 h-10 rounded-lg bg-[#eef3ea] text-[#2d5a27] font-bold flex items-center justify-center">{{ mb_strtoupper(mb_substr($store->name, 0, 1)) }}</span>
                @endif
                <div>
                    <p id="pv-name" class="font-semibold">{{ $store->name }}</p>
                    <p class="text-xs text-gray-500">{{ $store->city }}, {{ $store->province }}</p>
                </div>
            </div>
            <span class="inline-block text-[11px] px-2 py-0.5 rounded bg-[#dfe9d8] text-[#234a1f]">✓ Terverifikasi</span>
            <p class="text-xs text-gray-500">Belum ada penilaian · 0 pesanan selesai</p>
            <p id="pv-desc" class="text-xs text-gray-600 line-clamp-3">{{ $store->description }}</p>
        </aside>
    </div>
</form>

{{-- Dialog konfirmasi (32a) --}}
<dialog id="konfirmasi" class="m-auto rounded-2xl p-6 w-80 text-center shadow-xl backdrop:bg-black/40">
    <span class="mx-auto w-10 h-10 rounded-full bg-[#dfe9d8] text-[#2d5a27] flex items-center justify-center text-lg">✓</span>
    <p class="font-semibold mt-3">Simpan perubahan profil toko?</p>
    <p class="text-xs text-gray-500 mt-1">Perubahan akan langsung terlihat oleh pembeli yang melihat toko Anda.</p>
    <div class="flex gap-2 mt-4">
        <button type="button" onclick="document.getElementById('konfirmasi').close()"
                class="flex-1 border border-gray-300 rounded-lg py-2 text-sm hover:bg-gray-50">Batal</button>
        <button type="button" onclick="document.getElementById('form-profil').requestSubmit()"
                class="flex-1 bg-[#2d5a27] hover:bg-[#234a1f] text-white rounded-lg py-2 text-sm">Ya, Simpan</button>
    </div>
</dialog>

<script>
    document.getElementById('f-name').addEventListener('input', e => {
        document.getElementById('pv-name').textContent = e.target.value || 'Nama toko';
    });
    document.getElementById('f-desc').addEventListener('input', e => {
        document.getElementById('pv-desc').textContent = e.target.value;
    });
</script>
@endsection