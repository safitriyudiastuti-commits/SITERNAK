@extends('admin.layouts.app')
@section('content')
<h1>Data Induk Komoditas</h1>
<div class="card">
    <form method="get" class="actions">
        <label>Kelompok data<select name="type"><option value="livestock" @selected($type==='livestock')>Ternak hidup</option><option value="product" @selected($type==='product')>Hasil ternak</option></select></label>
        <button>Tampilkan</button>
    </form>
</div>
<div class="card">
    <h2>Tambah kelompok/kategori komoditas</h2>
    <form method="post" action="{{ route('admin.master-data.store') }}">@csrf
        <input type="hidden" name="kind" value="category"><input type="hidden" name="type" value="{{ $type }}">
        <label>Nama kelompok/kategori<input name="name" required placeholder="Contoh: Ternak besar"></label>
        <label>Slug (opsional)<input name="slug"></label><button>Simpan kategori</button>
    </form>
</div>
<div class="card">
    <h2>Kelompok/kategori tersedia</h2>
    <table><tr><th>Nama</th><th>Slug</th><th>Status</th><th>Aksi</th></tr>
    @forelse($categories as $category)<tr><td>{{ $category->name }}</td><td>{{ $category->slug }}</td><td>{{ $category->is_active?'Aktif':'Nonaktif' }}</td><td>
        @if($category->is_active)<form method="post" action="{{ route('admin.master-data.destroy',$category) }}" onsubmit="return confirm('Nonaktifkan kategori ini?')">@csrf @method('DELETE')<button class="danger">Nonaktifkan</button></form>@endif
    </td></tr>@empty<tr><td colspan="4">Belum ada kategori.</td></tr>@endforelse</table>
</div>
@if($type==='livestock')
<div class="card">
    <h2>Tambah jenis ternak</h2>
    <form method="post" action="{{ route('admin.master-data.store') }}">@csrf
        <input type="hidden" name="kind" value="breed"><input type="hidden" name="type" value="livestock">
        <label>Nama jenis<input name="name" required placeholder="Contoh: Sapi Limousin"></label>
        <label>Kelompok<select name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select></label>
        <p><b>Mode penjualan</b></p>
        <label><input type="checkbox" name="sale_modes[]" value="individual"> Individual</label>
        <label><input type="checkbox" name="sale_modes[]" value="batch"> Batch</label>
        <p><b>Peruntukan yang tersedia</b></p>
        @foreach(['bibit'=>'Bibit','pembesaran'=>'Pembesaran','siap_potong'=>'Siap potong','indukan'=>'Indukan','kontes'=>'Kontes'] as $value=>$label)
            <label><input type="checkbox" name="purposes[]" value="{{ $value }}"> {{ $label }}</label>
        @endforeach
        <p><b>Kolom tambahan pada formulir penjual</b></p>
        @foreach(['code'=>'Kode ternak','gender'=>'Jenis kelamin','age_months'=>'Umur (bulan)','weight_kg'=>'Berat (kg)','contest_category'=>'Kategori kontes'] as $value=>$label)
            <label><input type="checkbox" name="dynamic_fields[]" value="{{ $value }}"> {{ $label }}</label>
        @endforeach
        <button>Simpan jenis ternak</button>
    </form>
</div>
<div class="card">
    <h2>Jenis ternak</h2>
    <p>Jenis yang sudah dipakai transaksi sebaiknya dinonaktifkan, bukan dihapus.</p>
    <table><tr><th>Nama</th><th>Kelompok</th><th>Mode</th><th>Status</th><th>Aksi</th></tr>
    @forelse($breeds as $breed)<tr><td>{{ $breed->name }}</td><td>{{ $breed->category?->name }}</td><td>{{ implode(', ', $breed->sale_modes ?? []) }}</td><td>{{ $breed->is_active?'Aktif':'Nonaktif' }}</td><td>
        @if($breed->is_active)<form method="post" action="{{ route('admin.master-data.breeds.disable',$breed) }}" onsubmit="return confirm('Nonaktifkan jenis ini?')">@csrf @method('PATCH')<button class="danger">Nonaktifkan</button></form>@endif
    </td></tr>@empty<tr><td colspan="5">Belum ada jenis ternak.</td></tr>@endforelse</table>
</div>
@endif
<div class="card">
    <h2>Kelas kontes tersedia</h2>
    <form method="post" action="{{ route('admin.master-data.store') }}">@csrf
        <input type="hidden" name="kind" value="contest"><input type="hidden" name="type" value="{{ $type }}">
        <label>Nama kelas<input name="name" required placeholder="Contoh: Postur"></label>
        <label>Kelompok ternak<select name="livestock_group"><option value="">Semua kelompok</option><option value="unggas">Unggas</option><option value="ternak_kecil">Ternak kecil</option><option value="ternak_besar">Ternak besar</option></select></label>
        <button>Tambah kelas kontes</button>
    </form>
    <ul>@foreach($contestCategories as $contestCategory)<li>{{ $contestCategory->name }} ({{ $contestCategory->livestock_group ?: 'semua kelompok' }})</li>@endforeach</ul>
</div>
@endsection
