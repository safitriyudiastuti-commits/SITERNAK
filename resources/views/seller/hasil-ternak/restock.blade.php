@extends('layouts.seller')

@section('title', 'Restock Hasil Ternak')

@section('content')
<div class="mx-auto max-w-3xl space-y-5">
    <header>
        <p class="mb-2 text-xs text-gray-500">
            <a href="{{ route('seller.hasil-ternak.index') }}" class="hover:text-[#2d5a27]">Toko Saya</a>
            <span class="px-1 text-gray-300">›</span>
            <a href="{{ route('seller.hasil-ternak.index') }}" class="hover:text-[#2d5a27]">Hasil Ternak</a>
            <span class="px-1 text-gray-300">›</span>
            <span class="font-medium text-gray-800">Restock</span>
        </p>
        <h1 class="text-2xl font-bold text-gray-900">Tambah stok produk</h1>
        <p class="mt-1 text-sm text-gray-500">Jumlah tambahan akan ditambahkan ke stok saat ini.</p>
    </header>

    @if ($product->tanggal_kedaluwarsa->lt(today()))
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Produk ini sudah kedaluwarsa. Perbarui data kedaluwarsanya sebelum melakukan restock.
        </div>
    @endif

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <div class="flex items-start gap-4">
            @if ($product->media->isNotEmpty())
                <img src="{{ Storage::disk('public')->url($product->media->first()->path) }}" alt="" class="h-16 w-16 rounded-lg object-cover">
            @else
                <span class="flex h-16 w-16 items-center justify-center rounded-lg bg-[#f0f4ed] text-[#63845e]">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-7 w-7"><path d="M7 4h10v16H7zM7 9h10" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                </span>
            @endif
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold text-gray-900">{{ $product->commodityType->name }}</h2>
                <p class="mt-1 text-xs text-gray-500">Kedaluwarsa {{ $product->tanggal_kedaluwarsa->translatedFormat('d M Y') }}</p>
                <p class="mt-2 text-sm text-gray-700">
                    Stok saat ini:
                    <strong>{{ rtrim(rtrim(number_format((float) $product->stok, 3, ',', '.'), '0'), ',') }} {{ $product->satuan }}</strong>
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('seller.hasil-ternak.restock', $product) }}" class="mt-5 space-y-4">
            @csrf
            <label class="block text-xs font-semibold text-gray-700">
                Jumlah stok tambahan <span class="text-red-600">*</span>
                <div class="relative">
                    <input type="number" name="stok" value="{{ old('stok') }}" min="0.001" step="0.001" max="999999999.999"
                           required class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 pr-16 text-sm text-gray-800 outline-none focus:border-[#2d5a27] focus:ring-2 focus:ring-[#2d5a27]/10">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-normal text-gray-500">{{ $product->satuan }}</span>
                </div>
                @error('stok')<span class="mt-1 block text-xs font-normal text-red-600">{{ $message }}</span>@enderror
            </label>
            <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                <a href="{{ route('seller.hasil-ternak.index') }}" class="rounded-lg border border-gray-200 px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                @unless ($product->tanggal_kedaluwarsa->lt(today()))
                    <button type="submit" class="rounded-lg bg-[#2d5a27] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#234a1f]">Tambah Stok</button>
                @endunless
            </div>
        </form>
    </section>
</div>
@endsection
