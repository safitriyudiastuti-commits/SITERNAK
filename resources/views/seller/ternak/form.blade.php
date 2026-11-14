@extends('layouts.seller')

@section('title', $livestock ? 'Ubah Ternak' : 'Tambah Ternak')

@section('content')
@php
    $selectedCategory = old('category_id', $livestock?->commodityType?->category_id);
    $selectedTypeId = old('commodity_type_id', $livestock?->commodity_type_id);
    $selectedType = $types->first(fn ($type) => (int) $type['id'] === (int) $selectedTypeId);
    $fieldConfig = $selectedType['form_fields'] ?? [];
    $initialModes = $selectedType['selling_modes'] ?? [];
    $initialPurposes = $selectedType['purposes'] ?? [];
    $initialMode = old('mode', $livestock?->mode);
    if (! in_array($initialMode, $initialModes, true)) {
        $initialMode = $initialModes[0] ?? '';
    }
    $initialStock = $initialMode === 'individual'
        ? 1
        : old('stok', old('jumlah', $livestock?->stok ?? 1));
    $removedMediaIds = old('media_remove', []);
    $removedMediaIds = is_array($removedMediaIds) ? $removedMediaIds : [];
    $removedMediaIds = array_map('strval', array_filter($removedMediaIds, 'is_scalar'));
    $initialStatus = old('status', $livestock?->status ?? 'aktif');
    $initialPurposeLabel = $purposeLabels[old('purpose', $livestock?->purpose)] ?? 'Pilih peruntukan';
    $initialPrice = old('harga', $livestock?->harga);
    $initialName = old('nama_kode', $livestock?->nama_kode) ?: ($selectedType['name'] ?? 'Nama ternak');
    $initialImage = $livestock?->media->firstWhere('type', 'image');
    $initialUnit = old('satuan_jual', $livestock?->satuan_jual);
@endphp
<style>
    .livestock-form { --form-green: #2d5a27; }
    .livestock-form-card { border: 1px solid #e8e9e3; border-radius: 0.8rem; background: #fff; padding: 1.15rem; }
    .livestock-form-label { display: block; color: #414841; font-size: 0.72rem; font-weight: 600; }
    .livestock-form-control {
        display: block; width: 100%; margin-top: 0.35rem; border: 1px solid #e4e6df; border-radius: 0.55rem;
        background: #fff; padding: 0.6rem 0.7rem; color: #343a34; font-size: 0.75rem; outline: none;
    }
    select.livestock-form-control { appearance: none; padding-right: 2.25rem; }
    .livestock-form-control:focus { border-color: #2d5a27; box-shadow: 0 0 0 3px rgb(45 90 39 / 10%); }
    .livestock-select-wrap { position: relative; }
    .livestock-select-chevron {
        position: absolute; right: 0.75rem; top: 50%; width: 0.8rem; height: 0.8rem;
        transform: translateY(-30%); pointer-events: none; color: #7b8179;
    }
    .livestock-purpose-option.is-selected { border-color: #2d5a27; background: #2d5a27; color: #fff; }
    .livestock-mode-option.is-selected { border-color: #2d5a27; background: #2d5a27; color: #fff; }
    .livestock-thumb { aspect-ratio: 1 / 1; }
    .livestock-upload-slot { aspect-ratio: 1 / 1; }
    .livestock-form [hidden] { display: none !important; }
    @media (max-width: 640px) {
        .livestock-form-card { padding: 1rem; }
    }
</style>

<div class="livestock-form mx-auto max-w-6xl space-y-5">
    <header>
        <p class="mb-2 text-xs text-gray-500">
            <a href="{{ route('seller.ternak.index') }}" class="hover:text-[#2d5a27]">Toko Saya</a>
            <span class="px-1 text-gray-300">›</span>
            <a href="{{ route('seller.ternak.index') }}" class="hover:text-[#2d5a27]">Ternak Hidup</a>
            <span class="px-1 text-gray-300">›</span>
            <span class="font-medium text-gray-800">{{ $livestock ? 'Ubah Ternak' : 'Tambah Ternak' }}</span>
        </p>
        <h1 class="text-2xl font-bold text-gray-900">{{ $livestock ? 'Ubah ternak hidup' : 'Tambah ternak hidup' }}</h1>
        <p class="mt-1 text-sm text-gray-500">Kolom formulir menyesuaikan jenis ternak yang Anda pilih.</p>
    </header>

    <form id="livestock-form" method="POST"
          action="{{ $livestock ? route('seller.ternak.update', $livestock->id) : route('seller.ternak.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($livestock)
            @method('PUT')
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_290px]">
            <div class="space-y-4">
                <section class="livestock-form-card space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">1</span>
                        <h2 class="text-sm font-bold text-gray-900">Jenis ternak</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="livestock-form-label">
                            Kategori <span class="text-red-600">*</span>
                            <span class="livestock-select-wrap block">
                                <select name="category_id" id="category_id" required class="livestock-form-control">
                                    <option value="">Pilih kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" @selected((string) $selectedCategory === (string) $category->id)>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="livestock-select-chevron">
                                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            @error('category_id')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="livestock-form-label">
                            Jenis ternak <span class="text-red-600">*</span>
                            <span class="livestock-select-wrap block">
                                <select name="commodity_type_id" id="commodity_type_id" required class="livestock-form-control">
                                    <option value="">Pilih jenis</option>
                                    @foreach ($types as $type)
                                        @if ((string) $type['category_id'] === (string) $selectedCategory)
                                            <option value="{{ $type['id'] }}" @selected((string) $selectedTypeId === (string) $type['id'])>
                                                {{ $type['name'] }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="livestock-select-chevron">
                                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            @error('commodity_type_id')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <div id="mode-note" class="rounded-lg bg-[#edf4e9] px-3 py-2.5 text-xs leading-5 text-[#456541] {{ $selectedType ? '' : 'hidden' }}">
                        <div class="flex items-center gap-2">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="mr-2 h-4 w-4 shrink-0">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.7"/>
                                <path d="M12 11v5m0-8h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                            <span id="mode-note-text">
                                @if ($initialMode === 'individual')
                                    Mode pencatatan: Individual. Satu data mewakili satu ekor ternak.
                                @elseif ($initialMode === 'batch')
                                    Mode pencatatan: Batch. Masukkan jumlah ternak dalam kelompok ini.
                                @endif
                            </span>
                        </div>
                        <input type="hidden" name="mode" id="mode" value="{{ $initialMode }}">
                        <div id="mode-options" class="mt-2 flex flex-wrap gap-1.5 {{ count($initialModes) === 2 ? '' : 'hidden' }}">
                            @foreach ($initialModes as $mode)
                                <button type="button" data-mode-option="{{ $mode }}"
                                        class="livestock-mode-option rounded-full border border-[#cbd8c5] bg-white px-3 py-1 text-[10px] font-medium text-[#456541] hover:border-[#2d5a27] {{ $initialMode === $mode ? 'is-selected' : '' }}"
                                        aria-pressed="{{ $initialMode === $mode ? 'true' : 'false' }}">
                                    {{ $modeLabels[$mode] ?? ucfirst($mode) }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @error('mode')<span id="mode-error" class="block text-xs text-red-600">{{ $message }}</span>@enderror
                </section>

                <section class="livestock-form-card space-y-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">2</span>
                            <h2 class="text-sm font-bold text-gray-900">Data ternak</h2>
                        </div>
                        <p class="ml-8 mt-1 text-xs text-gray-500">Isian di bawah ini berbeda untuk setiap jenis ternak.</p>
                    </div>

                    <div class="grid gap-x-3 gap-y-3 sm:grid-cols-2">
                        <label data-form-field="nama_kode" class="livestock-form-label {{ array_key_exists('nama_kode', $fieldConfig) ? '' : 'hidden' }}">
                            Nama / kode ternak <span class="dynamic-required text-red-600">{{ ! empty($fieldConfig['nama_kode']) ? '*' : '' }}</span>
                            <input type="text" name="nama_kode" id="nama_kode" value="{{ old('nama_kode', $livestock?->nama_kode) }}"
                                   @required(! empty($fieldConfig['nama_kode'])) maxlength="100" class="livestock-form-control">
                            @error('nama_kode')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                            <span class="mt-1 block text-[10px] font-normal text-gray-400">Opsional, membantu Anda mengenali ternak.</span>
                        </label>
                        <label data-form-field="ras" class="livestock-form-label {{ array_key_exists('ras', $fieldConfig) ? '' : 'hidden' }}">
                            Ras <span class="dynamic-required text-red-600">{{ ! empty($fieldConfig['ras']) ? '*' : '' }}</span>
                            <input type="text" name="ras" id="ras" value="{{ old('ras', $livestock?->ras) }}"
                                   @required(! empty($fieldConfig['ras'])) maxlength="100" class="livestock-form-control">
                            @error('ras')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label data-form-field="jenis_kelamin" class="livestock-form-label {{ array_key_exists('jenis_kelamin', $fieldConfig) ? '' : 'hidden' }}">
                            Jenis kelamin <span class="dynamic-required text-red-600">{{ ! empty($fieldConfig['jenis_kelamin']) ? '*' : '' }}</span>
                            <span class="livestock-select-wrap block">
                                <select name="jenis_kelamin" id="jenis_kelamin" @required(! empty($fieldConfig['jenis_kelamin'])) class="livestock-form-control">
                                    <option value="">Pilih jenis kelamin</option>
                                    @foreach (['jantan' => 'Jantan', 'betina' => 'Betina', 'campuran' => 'Campuran'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('jenis_kelamin', $livestock?->jenis_kelamin) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="livestock-select-chevron">
                                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            @error('jenis_kelamin')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label data-form-field="umur_bulan" class="livestock-form-label {{ array_key_exists('umur_bulan', $fieldConfig) ? '' : 'hidden' }}">
                            Umur <span class="dynamic-required text-red-600">{{ ! empty($fieldConfig['umur_bulan']) ? '*' : '' }}</span>
                            <span class="relative block">
                                <input type="number" name="umur" id="umur_bulan" min="0" max="1200" step="1"
                                       value="{{ old('umur', $livestock?->umur) }}" @required(! empty($fieldConfig['umur_bulan']))
                                       class="livestock-form-control pr-14">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-normal text-gray-400">bulan</span>
                            </span>
                            @error('umur')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label data-form-field="berat_kg" class="livestock-form-label {{ array_key_exists('berat_kg', $fieldConfig) ? '' : 'hidden' }}">
                            Berat <span class="dynamic-required text-red-600">{{ ! empty($fieldConfig['berat_kg']) ? '*' : '' }}</span>
                            <span class="relative block">
                                <input type="number" name="berat" id="berat_kg" min="0" max="1000000" step="0.01"
                                       value="{{ old('berat', $livestock?->berat) }}" @required(! empty($fieldConfig['berat_kg']))
                                       class="livestock-form-control pr-10">
                                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-normal text-gray-400">kg</span>
                            </span>
                            @error('berat')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label id="jumlah-field" class="livestock-form-label {{ $initialMode === 'batch' ? '' : 'hidden' }}">
                            Jumlah ternak <span class="text-red-600">*</span>
                            <input type="number" name="jumlah" id="jumlah" min="1" max="1000000"
                                   value="{{ old('jumlah', $livestock?->jumlah) }}" @required($initialMode === 'batch')
                                   class="livestock-form-control">
                            @error('jumlah')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                            @error('stok')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <input type="hidden" name="stok" id="stok" value="{{ $initialStock }}">
                        <label class="livestock-form-label">
                            Status ketersediaan <span class="text-red-600">*</span>
                            <span class="livestock-select-wrap block">
                                <select name="status" id="status" required class="livestock-form-control">
                                    <option value="aktif" @selected($initialStatus === 'aktif')>Tersedia</option>
                                    <option value="nonaktif" @selected($initialStatus === 'nonaktif')>Nonaktif</option>
                                </select>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="livestock-select-chevron">
                                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            @error('status')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>

                    <div id="purpose-field" class="{{ $initialPurposes ? '' : 'hidden' }}">
                        <p class="mb-2 text-xs font-semibold text-gray-700">
                            Peruntukan <span id="purpose-required-marker" class="text-red-600 {{ $initialPurposes ? '' : 'hidden' }}">*</span>
                        </p>
                        <div id="purpose-options" class="flex flex-wrap gap-1.5">
                            @foreach ($initialPurposes as $purpose)
                                <label class="cursor-pointer">
                                    <input type="radio" name="purpose" value="{{ $purpose }}"
                                           @checked(old('purpose', $livestock?->purpose) === $purpose)
                                           @required($loop->first)
                                           class="peer sr-only" data-purpose-option>
                                    <span class="livestock-purpose-option inline-flex rounded-full border border-gray-200 px-3 py-1.5 text-[11px] text-gray-700 hover:border-[#2d5a27] peer-checked:border-[#2d5a27] peer-checked:bg-[#2d5a27] peer-checked:text-white">
                                        {{ $purposeLabels[$purpose] ?? ucfirst(str_replace('_', ' ', $purpose)) }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('purpose')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="livestock-form-label">
                            Harga <span class="text-red-600">*</span>
                            <span class="relative block">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-normal text-gray-500">Rp</span>
                                <input type="number" name="harga" id="harga" min="0" max="9999999999.99" step="0.01" required
                                       value="{{ old('harga', $livestock?->harga) }}" class="livestock-form-control pl-9">
                            </span>
                            @error('harga')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="livestock-form-label">
                            Satuan jual <span class="text-red-600">*</span>
                            <span class="livestock-select-wrap block">
                                <select name="satuan_jual" id="satuan_jual" required class="livestock-form-control">
                                    <option value="">Pilih satuan</option>
                                    @foreach (['ekor' => 'Per ekor', 'kg' => 'Per kg', 'paket' => 'Per paket', 'kandang' => 'Per kandang'] as $unit => $label)
                                        <option value="{{ $unit }}" @selected($initialUnit === $unit)>{{ $label }}</option>
                                    @endforeach
                                    @if ($initialUnit && ! in_array($initialUnit, ['ekor', 'kg', 'paket', 'kandang'], true))
                                        <option value="{{ $initialUnit }}" selected>{{ $initialUnit }}</option>
                                    @endif
                                </select>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="livestock-select-chevron">
                                    <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            @error('satuan_jual')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </section>

                <section class="livestock-form-card space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">3</span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900">Foto dan video</h2>
                            <p class="mt-0.5 text-[10px] text-gray-500">Maksimal 5 berkas, masing-masing 10 MB</p>
                        </div>
                    </div>

                    @if ($livestock?->media->isNotEmpty())
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                            @foreach ($livestock->media as $media)
                                <label class="relative cursor-pointer">
                                    @if ($media->type === 'video')
                                        <video class="livestock-thumb w-full rounded-lg border border-gray-200 bg-gray-50 object-cover">
                                            <source src="{{ Storage::disk('public')->url($media->path) }}" type="{{ $media->mime_type }}">
                                        </video>
                                    @else
                                        <img src="{{ Storage::disk('public')->url($media->path) }}" alt="{{ $media->original_name }}"
                                             class="livestock-thumb w-full rounded-lg border border-gray-200 object-cover">
                                    @endif
                                    <span class="absolute left-1 top-1 rounded bg-white/90 px-1.5 py-0.5 text-[9px] text-gray-600">Tersimpan</span>
                                    <span class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-gray-800/70 text-xs text-white">×</span>
                                    <input type="checkbox" name="media_remove[]" value="{{ $media->id }}"
                                           @checked(in_array((string) $media->id, $removedMediaIds, true))
                                           class="absolute bottom-1 right-1 h-4 w-4 accent-red-600" aria-label="Hapus {{ $media->original_name }}">
                                    <span class="mt-1 block truncate text-[10px] text-gray-500">{{ $media->original_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                        <label class="livestock-upload-slot flex cursor-pointer flex-col items-center justify-center rounded-lg border border-dashed border-[#cbd8c5] bg-[#f7faf5] text-[#2d5a27] hover:bg-[#eef3ea]">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                            <span class="mt-1 text-[10px] font-medium">Tambah</span>
                            <input type="file" name="media[]" id="media" multiple
                                   accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm"
                                   class="sr-only">
                        </label>
                        <div id="new-media-preview" class="contents"></div>
                    </div>
                    @error('media')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                    @error('media.*')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                </section>

                <section id="pedigree-card" class="livestock-form-card space-y-4 {{ $initialMode === 'individual' ? '' : 'hidden' }}">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">4</span>
                            <h2 class="text-sm font-bold text-gray-900">Silsilah</h2>
                        </div>
                        <span class="rounded-full bg-[#f0f0ec] px-2.5 py-1 text-[10px] font-medium text-gray-600">Opsional</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="livestock-form-label">
                            Nama induk betina
                            <input type="text" name="nama_induk_betina" value="{{ old('nama_induk_betina', $livestock?->nama_induk_betina) }}"
                                   maxlength="100" data-pedigree-field class="livestock-form-control">
                            @error('nama_induk_betina')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="livestock-form-label">
                            Ras induk betina
                            <input type="text" name="ras_induk_betina" value="{{ old('ras_induk_betina', $livestock?->ras_induk_betina) }}"
                                   maxlength="100" data-pedigree-field class="livestock-form-control">
                            @error('ras_induk_betina')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="livestock-form-label">
                            Nama pejantan
                            <input type="text" name="nama_pejantan" value="{{ old('nama_pejantan', $livestock?->nama_pejantan) }}"
                                   maxlength="100" data-pedigree-field class="livestock-form-control">
                            @error('nama_pejantan')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="livestock-form-label">
                            Ras pejantan
                            <input type="text" name="ras_pejantan" value="{{ old('ras_pejantan', $livestock?->ras_pejantan) }}"
                                   maxlength="100" data-pedigree-field class="livestock-form-control">
                            @error('ras_pejantan')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <p class="text-[10px] text-gray-500">Jika diisi, semua kolom silsilah wajib lengkap.</p>
                </section>

                <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                    <a href="{{ route('seller.ternak.index') }}"
                       class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-[#2d5a27] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#234a1f]">
                        {{ $livestock ? 'Simpan Perubahan' : 'Simpan Ternak' }}
                    </button>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-20">
                <section class="livestock-form-card">
                    <h2 class="text-sm font-bold text-gray-900">Pratinjau di katalog</h2>
                    <article class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="relative flex h-44 items-center justify-center overflow-hidden bg-[#f7f8f4]">
                            @if ($initialImage)
                                <img id="preview-image" src="{{ Storage::disk('public')->url($initialImage->path) }}"
                                     alt="Pratinjau ternak" class="h-full w-full object-cover">
                            @else
                                <img id="preview-image" src="" alt="Pratinjau ternak" class="hidden h-full w-full object-cover">
                                <svg id="preview-placeholder" aria-hidden="true" viewBox="0 0 64 64" fill="none" class="h-14 w-14 text-[#78956f]">
                                    <path d="M16 28c0-9 7-14 16-14s16 5 16 14v14c0 5-4 8-9 8H25c-5 0-9-3-9-8V28Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                                    <path d="m18 23-7-8m35 8 7-8M25 33h.1m14 0h.1M27 42c3 3 7 3 10 0" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                </svg>
                            @endif
                            <span class="absolute left-2 top-2 rounded-full bg-[#2d5a27] px-2 py-1 text-[9px] font-semibold text-white">Terverifikasi</span>
                        </div>
                        <div class="space-y-2.5 p-3">
                            <h3 id="preview-title" class="text-xs font-semibold text-gray-900">
                                {{ $initialName }}{{ $initialPurposeLabel !== 'Pilih peruntukan' ? ' – '.$initialPurposeLabel : '' }}
                            </h3>
                            <div class="flex flex-wrap gap-1">
                                <span id="preview-mode" class="rounded bg-gray-100 px-2 py-1 text-[9px] text-gray-600">
                                    {{ $modeLabels[old('mode', $livestock?->mode)] ?? 'Mode ternak' }}
                                </span>
                                <span id="preview-purpose" class="rounded bg-gray-100 px-2 py-1 text-[9px] text-gray-600">
                                    {{ $initialPurposeLabel }}
                                </span>
                            </div>
                            <p class="border-b border-gray-100 pb-2 text-sm font-bold text-[#2d5a27]">
                                <span id="preview-price">{{ $initialPrice !== null && $initialPrice !== '' ? 'Rp '.number_format((float) $initialPrice, 0, ',', '.') : 'Rp 0' }}</span>
                                <span class="text-[10px] font-normal text-gray-500"> / <span id="preview-unit">{{ $initialUnit ?: 'ekor' }}</span></span>
                            </p>
                            <div class="flex items-center justify-between gap-2 text-[10px] text-gray-500">
                                <span>{{ $store->city }}, {{ $store->province }}</span>
                                <span id="preview-status" class="font-medium text-[#2d5a27]">{{ $initialStatus === 'aktif' ? 'Tersedia' : 'Nonaktif' }}</span>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="livestock-form-card">
                    <h2 class="text-sm font-bold text-gray-900">Yang perlu diketahui</h2>
                    <ul class="mt-3 space-y-2 text-[11px] leading-4 text-gray-600">
                        <li class="flex gap-2"><span class="text-[#2d5a27]">✓</span> Kolom umur dan berat mengikuti jenis ternak.</li>
                        <li class="flex gap-2"><span class="text-[#2d5a27]">✓</span> Mode individual mewakili satu ekor ternak.</li>
                        <li class="flex gap-2"><span class="text-[#2d5a27]">✓</span> Foto dan video dapat ditambahkan hingga 5 berkas.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </form>
</div>

<script>
    (() => {
        const categorySelect = document.getElementById('category_id');
        const typeSelect = document.getElementById('commodity_type_id');
        const modeSelect = document.getElementById('mode');
        const modeNote = document.getElementById('mode-note');
        const stockInput = document.getElementById('stok');
        const quantityInput = document.getElementById('jumlah');
        const pedigreeCard = document.getElementById('pedigree-card');
        const pedigreeFields = [...document.querySelectorAll('[data-pedigree-field]')];
        const types = @json($types);
        const modeLabels = @json($modeLabels);
        const purposeLabels = @json($purposeLabels);
        const selectedTypeId = @json((string) $selectedTypeId);
        const previousMode = @json(old('mode', $livestock?->mode));
        const previousPurpose = @json(old('purpose', $livestock?->purpose));
        const fieldKeys = {
            nama_kode: 'nama_kode',
            ras: 'ras',
            jenis_kelamin: 'jenis_kelamin',
            umur_bulan: 'umur',
            berat_kg: 'berat',
        };

        function updatePreview() {
            const type = types.find((item) => String(item.id) === typeSelect.value);
            const name = document.getElementById('nama_kode').value.trim() || type?.name || 'Nama ternak';
            const gender = document.getElementById('jenis_kelamin').selectedOptions[0]?.textContent.trim();
            const selectedPurpose = document.querySelector('input[name="purpose"]:checked')?.value ?? '';
            const purpose = selectedPurpose ? purposeLabels[selectedPurpose] : '';
            const suffix = [gender && gender !== 'Pilih jenis kelamin' ? gender : '', purpose].filter(Boolean).join(' – ');
            document.getElementById('preview-title').textContent = suffix ? `${name} – ${suffix}` : name;
            document.getElementById('preview-mode').textContent = modeLabels[modeSelect.value] ?? 'Mode ternak';
            document.getElementById('preview-purpose').textContent = purpose || 'Pilih peruntukan';
            const price = document.getElementById('harga').value;
            document.getElementById('preview-price').textContent = price
                ? `Rp ${Number(price).toLocaleString('id-ID')}`
                : 'Rp 0';
            document.getElementById('preview-unit').textContent = document.getElementById('satuan_jual').value || 'ekor';
            document.getElementById('preview-status').textContent =
                document.getElementById('status').value === 'aktif' ? 'Tersedia' : 'Nonaktif';
        }

        function updatePurposeOptions(purposes, preserveSelection) {
            const field = document.getElementById('purpose-field');
            const options = document.getElementById('purpose-options');
            const chosen = preserveSelection && purposes.includes(previousPurpose) ? previousPurpose : '';
            field.classList.toggle('hidden', purposes.length === 0);
            document.getElementById('purpose-required-marker').classList.toggle('hidden', purposes.length === 0);
            options.replaceChildren();
            purposes.forEach((value) => {
                const label = document.createElement('label');
                label.className = 'cursor-pointer';
                const radio = document.createElement('input');
                radio.type = 'radio';
                radio.name = 'purpose';
                radio.value = value;
                radio.required = options.childElementCount === 0;
                radio.checked = value === chosen;
                radio.className = 'peer sr-only';
                radio.dataset.purposeOption = '';
                const chip = document.createElement('span');
                chip.className = 'livestock-purpose-option inline-flex rounded-full border border-gray-200 px-3 py-1.5 text-[11px] text-gray-700 hover:border-[#2d5a27] peer-checked:border-[#2d5a27] peer-checked:bg-[#2d5a27] peer-checked:text-white';
                chip.textContent = purposeLabels[value] ?? value.replaceAll('_', ' ');
                label.append(radio, chip);
                options.append(label);
            });
            updatePreview();
        }

        function updateModeFields() {
            const selectedMode = modeSelect.value;
            const isBatch = selectedMode === 'batch';
            const type = types.find((item) =>
                String(item.id) === typeSelect.value && String(item.category_id) === categorySelect.value
            );
            const field = document.getElementById('jumlah-field');
            const input = document.getElementById('jumlah');
            field.classList.toggle('hidden', !isBatch);
            input.required = isBatch;
            if (!isBatch) input.value = '';
            stockInput.value = isBatch ? input.value : '1';
            modeNote.classList.toggle('hidden', !type);
            document.getElementById('mode-error')?.classList.toggle('hidden', Boolean(type));
            document.getElementById('mode-note-text').textContent = selectedMode === 'individual'
                ? 'Mode pencatatan: Individual. Satu data mewakili satu ekor ternak.'
                : selectedMode === 'batch'
                    ? 'Mode pencatatan: Batch. Masukkan jumlah ternak dalam kelompok ini.'
                    : '';
            document.querySelectorAll('[data-mode-option]').forEach((button) => {
                const selected = button.dataset.modeOption === selectedMode;
                button.classList.toggle('is-selected', selected);
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
            updatePedigreeFields();
            updatePreview();
        }

        function updatePedigreeFields() {
            const isIndividual = modeSelect.value === 'individual';
            const hasPedigreeValue = pedigreeFields.some((field) => field.value.trim() !== '');
            pedigreeCard.classList.toggle('hidden', !isIndividual);
            pedigreeFields.forEach((field) => {
                field.disabled = !isIndividual;
                field.required = isIndividual && hasPedigreeValue;
            });
        }

        function updateTypeFields(preserveSelection = false) {
            const type = types.find((item) => String(item.id) === typeSelect.value);
            const fields = type?.form_fields ?? {};

            Object.entries(fieldKeys).forEach(([key, name]) => {
                const wrapper = document.querySelector(`[data-form-field="${key}"]`);
                if (!wrapper) return;
                const input = wrapper.querySelector(`[name="${name}"]`);
                if (!input) return;
                const requiredMarker = wrapper.querySelector('.dynamic-required');
                const available = key in fields;
                const required = Boolean(fields[key]);
                wrapper.classList.toggle('hidden', !available);
                input.disabled = !available;
                input.required = available && required;
                if (requiredMarker) {
                    requiredMarker.textContent = required ? '*' : '';
                }
            });

            const currentMode = modeSelect.value;
            const chosenMode = preserveSelection && previousMode && type?.selling_modes.includes(previousMode)
                ? previousMode
                : type?.selling_modes.includes(currentMode)
                    ? currentMode
                    : (type?.selling_modes[0] ?? '');
            modeSelect.value = chosenMode;
            const modeOptions = document.getElementById('mode-options');
            modeOptions.replaceChildren();
            modeOptions.classList.toggle('hidden', (type?.selling_modes.length ?? 0) !== 2);
            (type?.selling_modes ?? []).forEach((mode) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.modeOption = mode;
                button.className = `livestock-mode-option rounded-full border border-[#cbd8c5] bg-white px-3 py-1 text-[10px] font-medium text-[#456541] hover:border-[#2d5a27]${mode === chosenMode ? ' is-selected' : ''}`;
                button.setAttribute('aria-pressed', mode === chosenMode ? 'true' : 'false');
                button.textContent = modeLabels[mode] ?? mode;
                modeOptions.append(button);
            });
            updateModeFields();
            updatePurposeOptions(type?.purposes ?? [], preserveSelection);
            updatePreview();
        }

        function updateTypes(preserveSelection = false) {
            const categoryId = categorySelect.value;
            const availableTypes = types.filter((item) => String(item.category_id) === categoryId);
            typeSelect.replaceChildren(new Option('Pilih jenis', ''));
            availableTypes.forEach((item) => {
                typeSelect.add(new Option(item.name, item.id, false, preserveSelection && String(item.id) === selectedTypeId));
            });
            typeSelect.value = preserveSelection && availableTypes.some((item) => String(item.id) === selectedTypeId)
                ? selectedTypeId
                : '';
            updateTypeFields(preserveSelection);
        }

        const mediaInput = document.getElementById('media');
        mediaInput.addEventListener('change', () => {
            const preview = document.getElementById('new-media-preview');
            preview.replaceChildren();
            [...mediaInput.files].slice(0, 5).forEach((file) => {
                const tile = document.createElement('div');
                tile.className = 'livestock-thumb overflow-hidden rounded-lg border border-gray-200 bg-gray-50';
                if (file.type.startsWith('image/')) {
                    const image = document.createElement('img');
                    image.src = URL.createObjectURL(file);
                    image.alt = file.name;
                    image.className = 'h-full w-full object-cover';
                    tile.append(image);
                } else {
                    const video = document.createElement('video');
                    video.src = URL.createObjectURL(file);
                    video.className = 'h-full w-full object-cover';
                    tile.append(video);
                }
                preview.append(tile);
            });
            const firstImage = [...mediaInput.files].find((file) => file.type.startsWith('image/'));
            if (firstImage) {
                const image = document.getElementById('preview-image');
                image.src = URL.createObjectURL(firstImage);
                image.classList.remove('hidden');
                document.getElementById('preview-placeholder')?.classList.add('hidden');
            }
        });

        categorySelect.addEventListener('change', () => updateTypes(false));
        typeSelect.addEventListener('change', () => updateTypeFields(false));
        document.getElementById('mode-options').addEventListener('click', (event) => {
            const button = event.target.closest('[data-mode-option]');
            if (!button) return;
            modeSelect.value = button.dataset.modeOption;
            updateModeFields();
        });
        document.getElementById('purpose-options').addEventListener('change', (event) => {
            if (!event.target.matches('input[name="purpose"]')) return;
            updatePreview();
        });
        quantityInput.addEventListener('input', () => {
            if (modeSelect.value === 'batch') stockInput.value = quantityInput.value;
        });
        pedigreeFields.forEach((field) => {
            field.addEventListener('input', updatePedigreeFields);
        });
        document.getElementById('livestock-form').addEventListener('submit', () => {
            stockInput.value = modeSelect.value === 'batch' ? quantityInput.value : '1';
        });
        ['nama_kode', 'jenis_kelamin', 'harga', 'satuan_jual', 'status'].forEach((id) => {
            document.getElementById(id).addEventListener('input', updatePreview);
            document.getElementById(id).addEventListener('change', updatePreview);
        });
        updateTypes(true);
        updatePreview();
    })();
</script>
@endsection
