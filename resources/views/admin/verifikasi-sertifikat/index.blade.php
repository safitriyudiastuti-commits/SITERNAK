@extends('layouts.admin')

@section('title', 'Verifikasi sertifikat kontes')

@section('content')
    <div class="page-head">
        <h1>Verifikasi sertifikat kontes</h1>
        <p>Pastikan sertifikat asli sebelum riwayat prestasi tampil ke pembeli.</p>
    </div>

    @if (session('status'))
        <div class="result result-ok" style="margin-bottom:16px">{{ session('status') }}</div>
    @endif

    @include('admin.verifikasi-sertifikat._tabs')

    <div class="split">
        <aside>
            @include('admin.verifikasi-sertifikat._list')
        </aside>
        <section>
            <div class="empty-detail">
                Pilih satu sertifikat di sebelah kiri untuk memeriksa data dan gambarnya.
            </div>
        </section>
    </div>
@endsection
