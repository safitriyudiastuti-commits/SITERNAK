@extends('layouts.admin')

@section('title', 'Data induk')

@section('content')
    @include('admin.data-induk._styles')

    @php
        $selectedModes = old('selling_modes', $type->selling_modes ?? []);
        $selectedPurposes = old('purposes', $type->purposes ?? []);
        $requiredFields = old('required_fields', collect(array_keys(\App\Models\CommodityType::FORM_FIELDS))
            ->filter(fn ($key) => $type->isFieldRequired($key))->values()->all());
    @endphp

    <div class="page-head">
        <h1>Data induk komoditas</h1>
        <p>Kelola kelompok, kategori, jenis, mode penjualan, dan peruntukan.</p>
        <div class="di-toolbar">
            <button type="button" class="btn btn-primary" data-open="dlg-add">Tambah Jenis</button>
        </div>
    </div>

    @if (session('status'))
        <div class="di-alert di-alert-ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="di-alert di-alert-err">
            <strong>Data belum bisa disimpan:</strong>
            <ul>@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="split">
        <aside>
            @include('admin.data-induk._list')
        </aside>

        <section>
            <article class="card">
                <header class="detail-head">
                    <span class="avatar avatar-lg">{{ $type->initials }}</span>
                    <div class="detail-title">
                        <h2>{{ $type->name }}</h2>
                        <p>{{ $type->category->group_label }} · {{ $type->category->name }}</p>
                    </div>
                    <span class="badge {{ $type->is_active ? 'badge-success' : 'badge-danger' }}">
                        {{ $type->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </header>

                <form method="POST" action="{{ route('admin.data-induk.update', $type) }}">
                    @csrf
                    @method('PUT')

                    <div class="block">
                        <h3>Data jenis</h3>

                        <label class="field-label" for="name">Nama jenis</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $type->name) }}" class="di-input" maxlength="80" required>

                        <label class="field-label" for="category_id">Kategori</label>
                        <select id="category_id" name="category_id" class="di-input">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $type->category_id) == $category->id)>
                                    {{ $category->group_label }} · {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="block">
                        <h3>Mode penjualan</h3>
                        @foreach (\App\Models\CommodityType::MODES as $key => $label)
                            <label class="check">
                                <input type="checkbox" name="selling_modes[]" value="{{ $key }}" @checked(in_array($key, $selectedModes))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="block">
                        <h3>Peruntukan yang tersedia</h3>
                        @foreach (\App\Models\CommodityType::PURPOSES as $key => $label)
                            <label class="check">
                                <input type="checkbox" name="purposes[]" value="{{ $key }}" @checked(in_array($key, $selectedPurposes))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                        <p class="note">Kosongkan semua untuk hasil ternak (daging, telur, susu).</p>
                    </div>

                    <div class="block">
                        <h3>Kolom formulir peternak <small>Centang = wajib diisi</small></h3>
                        @foreach (\App\Models\CommodityType::FORM_FIELDS as $key => $label)
                            <label class="check">
                                <input type="checkbox" name="required_fields[]" value="{{ $key }}" @checked(in_array($key, $requiredFields))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach

                        <label class="field-label" for="contest_categories">Kategori kontes (pisahkan dengan koma)</label>
                        <input type="text" id="contest_categories" name="contest_categories" class="di-input"
                               value="{{ old('contest_categories', implode(', ', $type->contest_categories ?? [])) }}"
                               placeholder="Postur, Bobot badan, Tanduk">
                    </div>

                    <div class="block">
                        <p class="note">Jenis yang sudah dipakai transaksi tidak bisa dihapus, hanya dinonaktifkan.</p>
                        <div class="actions">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                            @if ($type->is_active)
                                <button type="button" class="btn btn-danger-outline" data-open="dlg-toggle">Nonaktifkan</button>
                            @else
                                <button type="button" class="btn btn-ghost" data-open="dlg-toggle">Aktifkan Kembali</button>
                            @endif
                        </div>
                    </div>
                </form>
            </article>
        </section>
    </div>

    {{-- Konfirmasi nonaktifkan / aktifkan (di luar form utama agar form tidak bersarang) --}}
    <dialog id="dlg-toggle" class="modal">
        <form method="POST" action="{{ route('admin.data-induk.toggle', $type) }}">
            @csrf
            @if ($type->is_active)
                <h3>Nonaktifkan jenis ini?</h3>
                <p>Jenis tidak akan muncul di pilihan peternak yang baru. Produk dan transaksi yang sudah ada tetap aman.</p>
            @else
                <h3>Aktifkan kembali jenis ini?</h3>
                <p>Jenis akan muncul lagi di pilihan peternak.</p>
            @endif
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" data-close>Batal</button>
                <button type="submit" class="btn {{ $type->is_active ? 'btn-danger-outline' : 'btn-primary' }}">
                    {{ $type->is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}
                </button>
            </div>
        </form>
    </dialog>

    @include('admin.data-induk._modal-tambah')
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-open]').forEach(function (btn) {
        btn.addEventListener('click', function () { document.getElementById(btn.dataset.open).showModal(); });
    });
    document.querySelectorAll('[data-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('dialog').close(); });
    });
</script>
@endpush
