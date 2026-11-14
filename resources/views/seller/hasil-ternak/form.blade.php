@extends('layouts.seller')

@section('title', $product ? 'Ubah Hasil Ternak' : 'Tambah Hasil Ternak')

@section('content')
@php
    $selectedTypeId = old('commodity_type_id', $product?->commodity_type_id);
    $selectedGrade = old('grade', $product?->grade);
    $selectedProductionDate = old('tanggal_produksi', $product?->tanggal_produksi?->format('Y-m-d') ?? today()->toDateString());
    $selectedExpiryDate = old('tanggal_kedaluwarsa', $product?->tanggal_kedaluwarsa?->format('Y-m-d') ?? today()->addDays(7)->toDateString());
    $removedMediaIds = old('media_remove', []);
    $removedMediaIds = is_array($removedMediaIds) ? array_map('strval', $removedMediaIds) : [];
    $initialImage = $product?->media->first();
@endphp
<style>
    .product-form-card { border: 1px solid #e8e9e3; border-radius: 0.8rem; background: #fff; padding: 1.15rem; }
    .product-form-label { display: block; color: #414841; font-size: 0.72rem; font-weight: 600; }
    .product-form-control {
        display: block; width: 100%; margin-top: 0.35rem; border: 1px solid #e4e6df; border-radius: 0.55rem;
        background: #fff; padding: 0.6rem 0.7rem; color: #343a34; font-size: 0.75rem; outline: none;
    }
    select.product-form-control { appearance: none; padding-right: 2.25rem; }
    .product-form-control:focus { border-color: #2d5a27; box-shadow: 0 0 0 3px rgb(45 90 39 / 10%); }
    .product-select-wrap { position: relative; }
    .product-select-chevron { position: absolute; right: 0.75rem; top: 50%; width: 0.8rem; height: 0.8rem; transform: translateY(-30%); pointer-events: none; color: #7b8179; }
    .product-grade-option:checked + span { border-color: #2d5a27; background: #2d5a27; color: #fff; }
    .product-thumb { aspect-ratio: 1 / 1; }
    .product-form [hidden] { display: none !important; }
    @media (max-width: 640px) { .product-form-card { padding: 1rem; } }
</style>

<div class="product-form mx-auto max-w-6xl space-y-5">
    <header>
        <p class="mb-2 text-xs text-gray-500">
            <a href="{{ route('seller.hasil-ternak.index') }}" class="hover:text-[#2d5a27]">Toko Saya</a>
            <span class="px-1 text-gray-300">›</span>
            <a href="{{ route('seller.hasil-ternak.index') }}" class="hover:text-[#2d5a27]">Hasil Ternak</a>
            <span class="px-1 text-gray-300">›</span>
            <span class="font-medium text-gray-800">{{ $product ? 'Ubah Produk' : 'Tambah Hasil Ternak' }}</span>
        </p>
        <h1 class="text-2xl font-bold text-gray-900">{{ $product ? 'Ubah hasil ternak' : 'Tambah hasil ternak' }}</h1>
        <p class="mt-1 text-sm text-gray-500">Produk berbasis stok, dengan tanggal produksi dan kedaluwarsa.</p>
    </header>

    @if (session('success'))
        <div role="status" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form id="product-form" method="POST"
          action="{{ $product ? route('seller.hasil-ternak.update', $product) : route('seller.hasil-ternak.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if ($product)
            @method('PUT')
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_290px]">
            <div class="space-y-4">
                <section class="product-form-card space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">1</span>
                        <h2 class="text-sm font-bold text-gray-900">Jenis produk</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="product-form-label">
                            Jenis produk <span class="text-red-600">*</span>
                            <span class="product-select-wrap block">
                                <select id="commodity-type" name="commodity_type_id" required class="product-form-control">
                                    <option value="">Pilih jenis produk</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type->id }}" @selected((string) $selectedTypeId === (string) $type->id)>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="product-select-chevron"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            <span class="mt-1 block text-[10px] font-normal text-gray-500">Pilihan berasal dari data induk komoditas.</span>
                            @error('commodity_type_id')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="product-form-label">
                            Asal ternak
                            <input id="origin" type="text" name="asal_ternak" list="livestock-origin-options"
                                   value="{{ old('asal_ternak', $product?->asal_ternak) }}" maxlength="150"
                                   placeholder="Contoh: Sapi Perah FH" class="product-form-control">
                            <datalist id="livestock-origin-options">
                                @foreach ($livestockOrigins as $origin)
                                    <option value="{{ $origin->nama_kode ?: $origin->commodityType->name }}">
                                @endforeach
                            </datalist>
                            <span class="mt-1 block text-[10px] font-normal text-gray-500">Opsional. Bisa memilih ternak milik toko atau mengetik asal lainnya.</span>
                            @error('asal_ternak')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                </section>

                <section class="product-form-card space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">2</span>
                        <h2 class="text-sm font-bold text-gray-900">Stok dan harga</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="product-form-label">
                            Jumlah stok <span class="text-red-600">*</span>
                            <div class="relative">
                                <input id="stock" type="number" name="stok" value="{{ old('stok', $product?->stok ?? '1') }}"
                                       min="0" step="0.001" max="999999999.999" required class="product-form-control pr-16">
                                <span id="stock-unit" class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] font-normal text-gray-500">
                                    {{ old('satuan', $product?->satuan ?? 'kg') }}
                                </span>
                            </div>
                            @error('stok')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="product-form-label">
                            Satuan <span class="text-red-600">*</span>
                            <span class="product-select-wrap block">
                                <select id="unit" name="satuan" required class="product-form-control">
                                    @foreach (['kg' => 'Kilogram (kg)', 'gram' => 'Gram', 'liter' => 'Liter', 'ml' => 'Mililiter (ml)', 'butir' => 'Butir', 'pak' => 'Pak', 'ikat' => 'Ikat', 'karung' => 'Karung'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('satuan', $product?->satuan ?? 'kg') === $value)>{{ $label }}</option>
                                    @endforeach
                                    <option value="lainnya" @selected(old('satuan', $product?->satuan) === 'lainnya')>Lainnya</option>
                                </select>
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="product-select-chevron"><path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                            @error('satuan')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="product-form-label">
                            Harga per satuan <span class="text-red-600">*</span>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-normal text-gray-500">Rp</span>
                                <input id="price" type="number" name="harga" value="{{ old('harga', $product?->harga) }}"
                                       min="0" step="0.01" max="9999999999.99" required class="product-form-control pl-9">
                            </div>
                            @error('harga')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <fieldset class="product-form-label">
                            <legend>Grade / kualitas</legend>
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <label class="cursor-pointer">
                                    <input class="product-grade-option peer sr-only" type="radio" name="grade" value=""
                                           @checked(old('grade', $product?->grade) === '')>
                                    <span class="inline-flex rounded-full border border-gray-200 px-3 py-1.5 text-[11px] text-gray-700 hover:border-[#2d5a27]">Tanpa grade</span>
                                </label>
                                @foreach (['A' => 'Grade A', 'B' => 'Grade B', 'C' => 'Grade C'] as $grade => $label)
                                    <label class="cursor-pointer">
                                        <input class="product-grade-option peer sr-only" type="radio" name="grade" value="{{ $grade }}"
                                               @checked(old('grade', $product?->grade) === $grade)>
                                        <span class="inline-flex rounded-full border border-gray-200 px-3 py-1.5 text-[11px] text-gray-700 hover:border-[#2d5a27]">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('grade')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </fieldset>
                    </div>
                </section>

                <section class="product-form-card space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">3</span>
                        <h2 class="text-sm font-bold text-gray-900">Kesegaran</h2>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="product-form-label">
                            Tanggal produksi <span class="text-red-600">*</span>
                            <input id="production-date" type="date" name="tanggal_produksi" value="{{ $selectedProductionDate }}"
                                   required class="product-form-control">
                            @error('tanggal_produksi')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="product-form-label">
                            Tanggal kedaluwarsa <span class="text-red-600">*</span>
                            <input id="expiry-date" type="date" name="tanggal_kedaluwarsa" value="{{ $selectedExpiryDate }}"
                                   min="{{ today()->toDateString() }}" required class="product-form-control">
                            <span class="mt-1 block text-[10px] font-normal text-gray-500">Tidak boleh sebelum hari ini atau tanggal produksi.</span>
                            @error('tanggal_kedaluwarsa')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-gray-200 bg-[#fafaf8] px-3 py-3">
                        <span class="flex items-center gap-3">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M12 3v18m6.4-14.4L5.6 17.4m12.8 0L5.6 6.6M3 12h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                            </span>
                            <span>
                                <span class="block text-xs font-semibold text-gray-800">Perlu penyimpanan dingin</span>
                                <span class="mt-0.5 block text-[10px] text-gray-500">Ditampilkan pada halaman produk agar pembeli tahu cara menyimpan.</span>
                            </span>
                        </span>
                        <input id="cold-storage" type="checkbox" name="penyimpanan_dingin" value="1"
                               @checked(old('penyimpanan_dingin', $product?->penyimpanan_dingin ?? false))
                               class="h-4 w-4 accent-[#2d5a27]">
                    </label>
                    @error('penyimpanan_dingin')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                </section>

                <section class="product-form-card space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#eef3ea] text-xs font-bold text-[#2d5a27]">4</span>
                        <h2 class="text-sm font-bold text-gray-900">Foto produk</h2>
                    </div>
                    @if ($product?->media->isNotEmpty())
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                            @foreach ($product->media as $media)
                                <label class="product-thumb relative block cursor-pointer overflow-hidden rounded-lg border border-gray-200">
                                    <img src="{{ Storage::disk('public')->url($media->path) }}" alt="{{ $media->original_name }}" class="h-full w-full object-cover">
                                    <input type="checkbox" name="media_remove[]" value="{{ $media->id }}"
                                           @checked(in_array((string) $media->id, $removedMediaIds, true))
                                           class="absolute right-2 top-2 h-4 w-4 accent-red-600">
                                    <span class="absolute inset-x-0 bottom-0 bg-black/55 px-1 py-1 text-center text-[9px] text-white">Hapus foto</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    <label for="product-media" class="flex min-h-28 cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-[#cbd8c5] bg-[#fbfcf9] px-4 py-4 text-center hover:bg-[#f4f8f1]">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6 text-[#2d5a27]"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <span class="mt-1 text-xs font-semibold text-[#2d5a27]">Tambah foto</span>
                        <span class="mt-1 text-[10px] text-gray-500">Maksimal 5 berkas, masing-masing 10 MB. JPG, PNG, atau WEBP.</span>
                    </label>
                    <input id="product-media" type="file" name="media[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple class="sr-only">
                    @error('media')<span class="block text-xs text-red-600">{{ $message }}</span>@enderror
                    @foreach ($errors->get('media.*') as $messages)
                        @foreach ($messages as $message)<span class="block text-xs text-red-600">{{ $message }}</span>@endforeach
                    @endforeach
                    <div id="product-media-preview" class="grid grid-cols-3 gap-2 sm:grid-cols-5"></div>
                </section>

                <div class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                    <a href="{{ route('seller.hasil-ternak.index') }}" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                    <button type="submit" class="rounded-lg bg-[#2d5a27] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#234a1f]">
                        {{ $product ? 'Simpan Perubahan' : 'Simpan Produk' }}
                    </button>
                </div>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-20">
                <section>
                    <h2 class="text-sm font-bold text-gray-900">Pratinjau di katalog</h2>
                    <article class="mt-3 overflow-hidden rounded-xl border border-gray-200 bg-white">
                        <div class="relative flex h-44 items-center justify-center overflow-hidden bg-[#f7f8f4]">
                            @if ($initialImage)
                                <img id="preview-image" src="{{ Storage::disk('public')->url($initialImage->path) }}" alt="Pratinjau produk" class="h-full w-full object-cover">
                            @else
                                <img id="preview-image" src="" alt="Pratinjau produk" class="hidden h-full w-full object-cover">
                                <svg id="preview-placeholder" aria-hidden="true" viewBox="0 0 64 64" fill="none" class="h-14 w-14 text-[#78956f]">
                                    <path d="M24 7h16v13l7 8v27H17V28l7-8V7Zm0 13h16M17 33h30" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/>
                                </svg>
                            @endif
                            <span class="absolute left-2 top-2 rounded-full bg-[#e0f3e7] px-2 py-1 text-[9px] font-semibold text-[#247341]">Segar</span>
                        </div>
                        <div class="space-y-2.5 p-3">
                            <h3 id="preview-title" class="text-xs font-semibold text-gray-900">{{ $types->firstWhere('id', (int) $selectedTypeId)?->name ?? 'Pilih jenis produk' }}</h3>
                            <div class="flex flex-wrap gap-1">
                                <span id="preview-grade" class="rounded bg-gray-100 px-2 py-1 text-[9px] text-gray-600">{{ $selectedGrade ? 'Grade '.$selectedGrade : 'Grade —' }}</span>
                                <span id="preview-cold" class="rounded bg-gray-100 px-2 py-1 text-[9px] text-gray-600">{{ old('penyimpanan_dingin', $product?->penyimpanan_dingin ?? false) ? 'Simpan dingin' : 'Simpan biasa' }}</span>
                            </div>
                            <p class="border-b border-gray-100 pb-2 text-sm font-bold text-[#2d5a27]">
                                <span id="preview-price">{{ old('harga', $product?->harga) !== null && old('harga', $product?->harga) !== '' ? 'Rp '.number_format((float) old('harga', $product?->harga), 0, ',', '.') : 'Rp 0' }}</span>
                                <span class="text-[10px] font-normal text-gray-500"> / <span id="preview-unit">{{ old('satuan', $product?->satuan ?? 'kg') }}</span></span>
                            </p>
                            <div class="flex items-center justify-between gap-2 text-[10px] text-gray-500">
                                <span>{{ $store->city }}, {{ $store->province }}</span>
                                <span class="font-medium text-[#2d5a27]">Stok <span id="preview-stock">{{ old('stok', $product?->stok ?? '1') }}</span></span>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="product-form-card">
                    <h2 class="text-sm font-bold text-gray-900">Kapan produk tampil?</h2>
                    <p class="mt-3 rounded-lg bg-[#eaf2e6] px-3 py-2 text-[10px] leading-4 text-[#456541]">
                        Produk dapat tampil selama stok tersedia dan belum kedaluwarsa.
                    </p>
                    <p class="mt-2 text-[10px] leading-4 text-gray-500">Produk akan disembunyikan otomatis dari katalog saat tanggal kedaluwarsa terlewati atau stok habis.</p>
                </section>
            </aside>
        </div>
    </form>
</div>

<script>
    (() => {
        const typeSelect = document.getElementById('commodity-type');
        const types = @json($types->map(fn ($type) => ['id' => $type->id, 'name' => $type->name])->values());
        const unitSelect = document.getElementById('unit');
        const stockUnit = document.getElementById('stock-unit');
        const stockInput = document.getElementById('stock');
        const priceInput = document.getElementById('price');
        const gradeInputs = [...document.querySelectorAll('input[name="grade"]')];
        const coldStorage = document.getElementById('cold-storage');
        const productionDate = document.getElementById('production-date');
        const expiryDate = document.getElementById('expiry-date');
        const mediaInput = document.getElementById('product-media');
        const mediaPreview = document.getElementById('product-media-preview');

        function updatePreview() {
            const title = document.getElementById('preview-title');
            const grade = document.getElementById('preview-grade');
            const cold = document.getElementById('preview-cold');
            const price = document.getElementById('preview-price');
            const previewUnit = document.getElementById('preview-unit');
            const previewStock = document.getElementById('preview-stock');
            if (title && typeSelect) {
                title.textContent = types.find((type) => String(type.id) === typeSelect.value)?.name ?? 'Pilih jenis produk';
            }
            if (grade) {
                const selectedGrade = gradeInputs.find((input) => input.checked)?.value;
                grade.textContent = selectedGrade ? `Grade ${selectedGrade}` : 'Grade —';
            }
            if (cold && coldStorage) cold.textContent = coldStorage.checked ? 'Simpan dingin' : 'Simpan biasa';
            if (price && priceInput) {
                price.textContent = priceInput.value ? `Rp ${Number(priceInput.value).toLocaleString('id-ID')}` : 'Rp 0';
            }
            if (previewUnit && unitSelect) previewUnit.textContent = unitSelect.value;
            if (stockUnit && unitSelect) stockUnit.textContent = unitSelect.value;
            if (previewStock && stockInput) previewStock.textContent = stockInput.value || '0';
        }

        typeSelect?.addEventListener('change', updatePreview);
        unitSelect?.addEventListener('change', updatePreview);
        stockInput?.addEventListener('input', updatePreview);
        priceInput?.addEventListener('input', updatePreview);
        coldStorage?.addEventListener('change', updatePreview);
        gradeInputs.forEach((input) => input.addEventListener('change', updatePreview));
        productionDate?.addEventListener('change', () => {
            if (expiryDate && productionDate.value) expiryDate.min = productionDate.value;
        });
        mediaInput?.addEventListener('change', () => {
            if (!mediaPreview || !mediaInput) return;
            mediaPreview.replaceChildren();
            [...mediaInput.files].slice(0, 5).forEach((file) => {
                if (!file.type.startsWith('image/')) return;
                const tile = document.createElement('div');
                tile.className = 'product-thumb overflow-hidden rounded-lg border border-gray-200 bg-gray-50';
                const image = document.createElement('img');
                image.src = URL.createObjectURL(file);
                image.alt = file.name;
                image.className = 'h-full w-full object-cover';
                tile.append(image);
                mediaPreview.append(tile);
            });
        });
        updatePreview();
    })();
</script>
@endsection
