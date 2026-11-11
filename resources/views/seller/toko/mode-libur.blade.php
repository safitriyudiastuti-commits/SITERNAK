@extends('layouts.seller')
@section('title', 'Mode Libur')
@section('content')
@php
    $akhir = old('vacation_end', $store->vacation_end?->format('Y-m-d'));
@endphp

<p class="text-xs text-gray-500 mb-2">Toko Saya › <b class="text-gray-800">Mode libur</b></p>
<h1 class="text-2xl font-bold text-gray-900">Mode libur</h1>
<p class="text-sm text-gray-500 mb-6">Nonaktifkan sementara toko saat Anda sedang libur. Pembeli akan melihat pemberitahuan.</p>

<div class="grid lg:grid-cols-3 gap-6 items-start">
    <form id="form-libur" method="POST" action="{{ route('seller.toko.libur.update') }}"
          class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6 space-y-5">
        @csrf @method('PUT')

        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="font-semibold text-gray-900">Pengaturan mode libur</p>
                <p class="text-sm font-medium mt-3">Aktifkan mode libur</p>
                <p class="text-xs text-gray-500">Toko tidak menerima pesanan baru selama periode libur.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="toggle" name="vacation_mode" value="1" class="peer sr-only"
                       @checked(old('vacation_mode', $store->vacation_mode))>
                <span class="w-11 h-6 rounded-full bg-gray-300 peer-checked:bg-[#2d5a27] transition
                             after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-5 after:h-5
                             after:bg-white after:rounded-full after:transition peer-checked:after:translate-x-5"></span>
            </label>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <label class="block text-sm font-medium">Mulai
                <input type="date" name="vacation_start" value="{{ old('vacation_start', $store->vacation_start?->format('Y-m-d')) }}"
                       class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 font-normal focus:outline-none focus:ring-2 focus:ring-[#2d5a27]">
            </label>
            <label class="block text-sm font-medium">Berakhir
                <input type="date" id="f-end" name="vacation_end" value="{{ $akhir }}"
                       class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 font-normal focus:outline-none focus:ring-2 focus:ring-[#2d5a27]">
            </label>
        </div>

        <label class="block text-sm font-medium">Pesan otomatis untuk pembeli
            <textarea name="vacation_message" rows="3" maxlength="255"
                      placeholder="Mohon maaf, toko sedang libur sampai … Pesanan akan dilayani setelah toko buka kembali."
                      class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 font-normal focus:outline-none focus:ring-2 focus:ring-[#2d5a27]">{{ old('vacation_message', $store->vacation_message) }}</textarea>
        </label>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="vacation_hide_listings" value="1" class="accent-[#2d5a27]"
                   @checked(old('vacation_hide_listings', $store->vacation_hide_listings))>
            Sembunyikan semua listing saat mode libur aktif
        </label>

        <button type="button" onclick="simpanLibur()"
                class="bg-[#2d5a27] hover:bg-[#234a1f] text-white text-sm font-medium px-5 py-2 rounded-lg">Simpan Pengaturan</button>
    </form>

    {{-- Contoh tampilan ke pembeli --}}
    <aside class="bg-white rounded-xl border border-gray-200 p-5 text-sm space-y-3">
        <h2 class="font-semibold text-gray-900">Tampilan ke pembeli</h2>
        <div class="rounded-lg overflow-hidden border border-gray-200">
            <div class="relative h-24 bg-[#8a7f6a] flex items-center justify-center">
                <span class="text-white text-xs font-medium bg-black/50 rounded px-3 py-1">
                    Sedang libur sampai <span id="pv-end">{{ $store->vacation_end?->locale('id')->translatedFormat('j F Y') ?? '…' }}</span>
                </span>
            </div>
            <div class="p-3">
                <p class="font-medium text-sm">Contoh listing ternak</p>
                <p class="text-xs text-gray-500">Rp 0</p>
            </div>
        </div>
        <p class="text-xs text-gray-400">Contoh tampilan. Listing sungguhan mengikuti data ternak Anda.</p>
    </aside>
</div>

{{-- Dialog konfirmasi (31a) --}}
<dialog id="konfirmasi" class="m-auto rounded-2xl p-6 w-80 text-center shadow-xl backdrop:bg-black/40">
    <span class="mx-auto w-10 h-10 rounded-full bg-[#dfe9d8] text-[#2d5a27] flex items-center justify-center text-lg">✓</span>
    <p class="font-semibold mt-3">Aktifkan mode libur?</p>
    <p class="text-xs text-gray-500 mt-1">Toko tidak akan menerima pesanan baru selama periode libur, dan pembeli akan melihat pesan otomatis Anda.</p>
    <div class="flex gap-2 mt-4">
        <button type="button" onclick="document.getElementById('konfirmasi').close()"
                class="flex-1 border border-gray-300 rounded-lg py-2 text-sm hover:bg-gray-50">Batal</button>
        <button type="button" onclick="document.getElementById('form-libur').requestSubmit()"
                class="flex-1 bg-[#2d5a27] hover:bg-[#234a1f] text-white rounded-lg py-2 text-sm">Ya, Aktifkan</button>
    </div>
</dialog>

<script>
    function simpanLibur() {
        const form = document.getElementById('form-libur');
        if (! form.reportValidity()) return;
        if (document.getElementById('toggle').checked) {
            document.getElementById('konfirmasi').showModal();
        } else {
            form.requestSubmit();
        }
    }
    document.getElementById('f-end').addEventListener('change', e => {
        const d = e.target.value ? new Date(e.target.value + 'T00:00:00') : null;
        document.getElementById('pv-end').textContent = d
            ? d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
            : '…';
    });
</script>
@endsection