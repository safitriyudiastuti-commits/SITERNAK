@extends('layouts.seller')

@section('title', $item->nama_kode ?: $item->commodityType->name)

@section('content')
@php
    $displayName = $item->nama_kode ?: $item->commodityType->name;
    $pedigree = [
        'Nama induk betina' => $item->nama_induk_betina,
        'Ras induk betina' => $item->ras_induk_betina,
        'Nama pejantan' => $item->nama_pejantan,
        'Ras pejantan' => $item->ras_pejantan,
    ];
@endphp

<div class="mx-auto max-w-5xl space-y-5">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="mb-2 text-xs text-gray-500">
                <a href="{{ route('seller.ternak.index') }}" class="hover:text-[#2d5a27]">Toko Saya</a>
                <span class="px-1 text-gray-300">›</span>
                <a href="{{ route('seller.ternak.index') }}" class="hover:text-[#2d5a27]">Ternak Hidup</a>
                <span class="px-1 text-gray-300">›</span>
                <span class="font-medium text-gray-800">{{ $displayName }}</span>
            </p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $displayName }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $item->commodityType->name }} · {{ $item->mode === 'individual' ? 'Individual' : 'Batch' }}</p>
        </div>
        <a href="{{ route('seller.ternak.edit', $item->id) }}"
           class="inline-flex items-center justify-center rounded-lg bg-[#2d5a27] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#234a1f]">
            Ubah Ternak
        </a>
    </header>

    <section class="rounded-xl border border-gray-200 bg-white p-5">
        <h2 class="text-base font-bold text-gray-900">Informasi ternak</h2>
        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs text-gray-500">Ras</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $item->ras ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Jenis kelamin</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $item->jenis_kelamin ? ucfirst($item->jenis_kelamin) : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Umur</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $item->umur !== null ? $item->umur.' bulan' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Berat</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $item->berat !== null ? $item->berat.' kg' : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Peruntukan</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">
                    {{ $item->purpose ? (\App\Models\CommodityType::PURPOSES[$item->purpose] ?? ucfirst(str_replace('_', ' ', $item->purpose))) : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Harga</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">Rp {{ number_format((float) $item->harga, 0, ',', '.') }} / {{ $item->satuan_jual }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Stok</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $item->stok }} {{ $item->satuan_jual }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">Status</dt>
                <dd class="mt-1 text-sm font-medium text-gray-900">{{ $item->status === 'aktif' ? 'Tersedia' : 'Nonaktif' }}</dd>
            </div>
        </dl>
    </section>

    @if ($item->mode === 'individual')
        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-bold text-gray-900">Silsilah</h2>
                <span class="rounded-full bg-[#f0f0ec] px-2.5 py-1 text-[10px] font-medium text-gray-600">Opsional</span>
            </div>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($pedigree as $label => $value)
                    <div>
                        <dt class="text-xs text-gray-500">{{ $label }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    @if ($item->media->isNotEmpty())
        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <h2 class="text-base font-bold text-gray-900">Foto dan video</h2>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($item->media as $media)
                    @if ($media->type === 'video')
                        <video controls class="aspect-square w-full rounded-lg border border-gray-200 bg-gray-50 object-cover">
                            <source src="{{ Storage::disk('public')->url($media->path) }}" type="{{ $media->mime_type }}">
                        </video>
                    @else
                        <img src="{{ Storage::disk('public')->url($media->path) }}" alt="{{ $media->original_name }}"
                             class="aspect-square w-full rounded-lg border border-gray-200 object-cover">
                    @endif
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
