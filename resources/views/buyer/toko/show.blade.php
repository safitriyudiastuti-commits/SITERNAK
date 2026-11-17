@extends('layouts.buyer')

@section('title', $store->name . ' | Toko SITERNAK')

@section('content')
@php
    $logoUrl = $store->logo_path
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($store->logo_path)
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($store->logo_path)
            : null;

    $inisial = \Illuminate\Support\Str::upper(
        \Illuminate\Support\Str::substr($store->name, 0, 1)
    );

    $kategoriToko = $store->category_labels ?? [];
@endphp

<nav class="flex items-center text-sm text-[#6B7265]">
    <a href="{{ route('buyer.beranda') }}" class="hover:underline">Beranda</a>
    <span class="mx-2">›</span>
    <a href="{{ route('buyer.katalog.index') }}" class="hover:underline">Katalog Ternak</a>
    <span class="mx-2">›</span>
    <span class="font-semibold text-[#1C2118]">{{ $store->name }}</span>
</nav>

<section class="mt-5 rounded-2xl border border-[#E4E4DC] bg-white p-6">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
        @if ($logoUrl)
            <img
                src="{{ $logoUrl }}"
                alt="Logo {{ $store->name }}"
                class="h-24 w-24 rounded-2xl object-cover"
            >
        @else
            <div class="flex h-24 w-24 items-center justify-center rounded-2xl bg-[#E9F1E6] text-3xl font-bold text-[#2E5B2A]">
                {{ $inisial }}
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-extrabold text-[#1C2118]">
                    {{ $store->name }}
                </h1>

                <span class="rounded-full bg-[#DCF3E3] px-3 py-1 text-xs font-semibold text-[#166534]">
                    Toko terverifikasi
                </span>
            </div>

            <p class="mt-2 text-sm text-[#6B7265]">
                {{ $store->city }}, {{ $store->province }}
            </p>

            @if ($kategoriToko)
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($kategoriToko as $kategori)
                        <span class="rounded-full bg-[#F1F3EE] px-3 py-1 text-xs font-medium text-[#4B5245]">
                            {{ $kategori }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($store->description)
        <div class="mt-6 border-t border-[#EDEDE6] pt-5">
            <h2 class="font-bold text-[#1C2118]">Tentang toko</h2>
            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-[#4B5245]">
                {{ $store->description }}
            </p>
        </div>
    @endif
</section>

@if ($libur)
    <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Toko sedang dalam mode libur. Produk mungkin belum dapat dipesan.
        {{ $store->vacation_message }}
    </div>
@endif

<section class="mt-8">
    <h2 class="text-xl font-bold text-[#1C2118]">Ternak hidup</h2>

    @if ($hidup->isEmpty())
        <p class="mt-3 rounded-xl border border-dashed border-[#E4E4DC] bg-white p-6 text-sm text-[#6B7265]">
            Toko ini belum memiliki ternak hidup yang tersedia.
        </p>
    @else
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($hidup as $item)
                <x-kartu-ternak :r="$item" />
            @endforeach
        </div>
    @endif
</section>

<section class="mt-8">
    <h2 class="text-xl font-bold text-[#1C2118]">Hasil ternak</h2>

    @if ($hasil->isEmpty())
        <p class="mt-3 rounded-xl border border-dashed border-[#E4E4DC] bg-white p-6 text-sm text-[#6B7265]">
            Toko ini belum memiliki hasil ternak yang tersedia.
        </p>
    @else
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($hasil as $item)
                <x-kartu-ternak :r="$item" />
            @endforeach
        </div>
    @endif
</section>
@endsection