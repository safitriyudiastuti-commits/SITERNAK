@extends('layouts.buyer')

@section('title', 'Beri Ulasan')

@section('content')
<div class="mx-auto max-w-2xl">
    <nav class="text-xs text-[#6B7265]">
        <a href="{{ route('buyer.pesanan.index') }}" class="hover:underline">Pesanan Saya</a> <span class="mx-1">›</span>
        <a href="{{ route('buyer.pesanan.show', $order) }}" class="hover:underline">{{ $order->nomor_order }}</a> <span class="mx-1">›</span>
        <span class="font-semibold text-[#1C2118]">Ulasan</span>
    </nav>
    <h1 class="mt-3 text-3xl font-extrabold text-[#1C2118]">Beri Ulasan</h1>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="mt-5 rounded-2xl border border-[#E4E4DC] bg-white p-5 text-sm">
        <p class="font-semibold text-[#1C2118]">{{ $order->nama_item }}</p>
        <p class="text-[#6B7265]">{{ $order->peternak->nama_usaha ?? '' }} · {{ $order->nomor_order }}</p>
    </section>

    <form method="POST" action="{{ route('buyer.ulasan.store', $order) }}" enctype="multipart/form-data"
          class="mt-5 space-y-5 rounded-2xl border border-[#E4E4DC] bg-white p-6">
        @csrf

        <div>
            <p class="mb-2 text-sm font-semibold">Rating <span class="text-red-600">*</span></p>
            <div class="flex flex-row-reverse justify-end gap-1">
                @for ($i = 5; $i >= 1; $i--)
                    <input type="radio" id="bintang{{ $i }}" name="rating" value="{{ $i }}" class="peer sr-only" @checked((int) old('rating') === $i) required>
                    <label for="bintang{{ $i }}" title="{{ $i }} bintang"
                           class="cursor-pointer text-4xl leading-none text-gray-300 hover:text-amber-400 peer-checked:text-amber-400 peer-focus-visible:outline">★</label>
                @endfor
            </div>
        </div>

        <div>
            <label for="komentar" class="mb-1 block text-sm font-semibold">Komentar <span class="font-normal text-[#9AA093]">(opsional)</span></label>
            <textarea id="komentar" name="komentar" rows="4" maxlength="1000" placeholder="Ceritakan kondisi ternak/produk dan pelayanan peternak"
                      class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2 text-sm focus:border-[#2E5B2A] focus:outline-none">{{ old('komentar') }}</textarea>
        </div>

        <div>
            <label for="foto" class="mb-1 block text-sm font-semibold">Foto <span class="font-normal text-[#9AA093]">(opsional, maksimal 3)</span></label>
            <input id="foto" type="file" name="foto[]" multiple accept=".jpg,.jpeg,.png,.webp"
                   class="block w-full rounded-lg border border-dashed border-[#C9D3C3] p-3 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#E9F1E6] file:px-3 file:py-1.5 file:font-semibold file:text-[#2E5B2A]">
            <p class="mt-1 text-xs text-[#6B7265]">JPG, PNG, atau WEBP, maksimal 2 MB per foto.</p>
        </div>

        <div>
            <label for="feedback" class="mb-1 block text-sm font-semibold">Feedback atau keluhan <span class="font-normal text-[#9AA093]">(opsional)</span></label>
            <textarea id="feedback" name="feedback" rows="3" maxlength="1000" placeholder="Ada kendala pada pesanan ini? Tuliskan di sini."
                      class="w-full rounded-lg border border-[#E4E4DC] px-3 py-2 text-sm focus:border-[#2E5B2A] focus:outline-none">{{ old('feedback') }}</textarea>
        </div>

        <div class="flex justify-end gap-2">
            <a href="{{ route('buyer.pesanan.show', $order) }}" class="rounded-lg border border-[#E4E4DC] px-5 py-2.5 text-sm font-semibold text-[#4B5245]">Nanti saja</a>
            <button class="rounded-lg bg-[#2E5B2A] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#1F421C]">Kirim Ulasan</button>
        </div>
    </form>
</div>
@endsection