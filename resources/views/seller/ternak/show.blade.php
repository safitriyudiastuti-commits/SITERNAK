@extends('layouts.seller')

@section('title', $item->nama_kode ?: $item->commodityType->name)

@section('content')
@php
    $displayName = $item->nama_kode ?: $item->commodityType->name;
    $contestCategories = $item->commodityType->contest_categories ?? [];
    $hasEligibleContest = $item->mode === 'individual' && $contestCategories !== [];
    $hasVerifiedContest = $hasEligibleContest && $item->contestHistories->contains('status', 'verified');
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
            @if ($hasVerifiedContest)
                <span class="mt-2 inline-flex rounded-full bg-[#e0f3e7] px-2.5 py-1 text-[10px] font-semibold text-[#247341]">Terverifikasi</span>
            @endif
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

    @if ($item->mode === 'individual' && $contestCategories !== [])
        <section class="rounded-xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-900">Riwayat prestasi kontes</h2>
                    <p class="mt-1 text-xs text-gray-500">Sertifikat yang diunggah menunggu verifikasi.</p>
                </div>
                <span class="rounded-full bg-[#f0f0ec] px-2.5 py-1 text-[10px] font-medium text-gray-600">Opsional</span>
            </div>

            @if ($item->contestHistories->isNotEmpty())
                <div class="mt-4 space-y-3">
                    @foreach ($item->contestHistories as $history)
                        @php
                            $statusClasses = [
                                'pending' => 'bg-[#fff1c9] text-[#a16207]',
                                'verified' => 'bg-[#e0f3e7] text-[#247341]',
                                'rejected' => 'bg-[#fee2e2] text-[#b91c1c]',
                            ];
                            $statusLabels = [
                                'pending' => 'Menunggu verifikasi',
                                'verified' => 'Terverifikasi',
                                'rejected' => 'Ditolak',
                            ];
                        @endphp
                        <article class="rounded-lg border border-gray-200 bg-[#fafaf8] p-4">
                            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold text-gray-900">{{ $history->contest_name }}</h3>
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-medium {{ $statusClasses[$history->status] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ $statusLabels[$history->status] ?? ucfirst($history->status) }}
                                        </span>
                                    </div>
                                    <dl class="mt-2 grid gap-x-6 gap-y-1 text-xs text-gray-600 sm:grid-cols-2">
                                        <div><dt class="inline font-medium text-gray-700">Kategori:</dt> <dd class="inline">{{ $history->category }}</dd></div>
                                        <div><dt class="inline font-medium text-gray-700">Peringkat:</dt> <dd class="inline">{{ $history->rank }}</dd></div>
                                        <div><dt class="inline font-medium text-gray-700">Tanggal:</dt> <dd class="inline">{{ $history->contest_date->format('d M Y') }}</dd></div>
                                        <div><dt class="inline font-medium text-gray-700">Penyelenggara:</dt> <dd class="inline">{{ $history->organizer }}</dd></div>
                                    </dl>
                                    @if ($history->status === 'rejected' && $history->rejection_reason)
                                        <p class="mt-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-800">
                                            <span class="font-semibold">Alasan penolakan:</span> {{ $history->rejection_reason }}
                                        </p>
                                    @endif
                                    <a href="{{ route('seller.ternak.kontes.sertifikat', [$item->id, $history->id]) }}"
                                       class="mt-3 inline-flex text-xs font-medium text-[#2d5a27] hover:underline">
                                        Lihat sertifikat: {{ $history->certificate_original_name }}
                                    </a>
                                </div>
                                <form method="POST" action="{{ route('seller.ternak.kontes.destroy', [$item->id, $history->id]) }}"
                                      onsubmit="return confirm('Hapus riwayat kontes ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="mt-4 rounded-lg bg-[#fafaf8] px-4 py-3 text-xs text-gray-500">Belum ada riwayat kontes.</p>
            @endif

            <form method="POST" action="{{ route('seller.ternak.kontes.store', $item->id) }}"
                  enctype="multipart/form-data" class="mt-5 border-t border-gray-100 pt-5">
                @csrf
                <h3 class="text-sm font-semibold text-gray-900">Tambah riwayat kontes</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="block text-xs font-medium text-gray-700">
                        Nama kontes <span class="text-red-600">*</span>
                        <input type="text" name="contest_name" value="{{ old('contest_name') }}" required maxlength="150"
                               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#2d5a27]">
                        @error('contest_name')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-xs font-medium text-gray-700">
                        Kategori <span class="text-red-600">*</span>
                        <select name="category" required class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-[#2d5a27]">
                            <option value="">Pilih kategori</option>
                            @foreach ($contestCategories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                        @error('category')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-xs font-medium text-gray-700">
                        Peringkat <span class="text-red-600">*</span>
                        <input type="text" name="rank" value="{{ old('rank') }}" required maxlength="100"
                               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#2d5a27]">
                        @error('rank')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-xs font-medium text-gray-700">
                        Tanggal <span class="text-red-600">*</span>
                        <input type="date" name="contest_date" value="{{ old('contest_date') }}" required
                               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#2d5a27]">
                        @error('contest_date')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-xs font-medium text-gray-700 sm:col-span-2">
                        Penyelenggara <span class="text-red-600">*</span>
                        <input type="text" name="organizer" value="{{ old('organizer') }}" required maxlength="150"
                               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm outline-none focus:border-[#2d5a27]">
                        @error('organizer')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block text-xs font-medium text-gray-700 sm:col-span-2">
                        Sertifikat (JPG, PNG, atau PDF; maksimal 5 MB) <span class="text-red-600">*</span>
                        <input type="file" name="certificate" required accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                               class="mt-1 block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                        @error('certificate')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                </div>
                <button type="submit" class="mt-4 rounded-lg bg-[#2d5a27] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#234a1f]">
                    Tambah riwayat
                </button>
            </form>
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
