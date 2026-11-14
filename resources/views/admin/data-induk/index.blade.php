@extends('layouts.admin')

@section('title', 'Data induk')

@section('content')
    @include('admin.data-induk._styles')

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
            <div class="empty-detail">
                Pilih satu jenis di sebelah kiri untuk melihat dan mengubah pengaturannya.
            </div>
        </section>
    </div>

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
