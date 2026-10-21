@extends('layouts.admin')

@section('title', 'Verifikasi peternak')

@section('content')
    <div class="page-head">
        <h1>Verifikasi peternak</h1>
        <p>Tinjau pendaftaran peternak baru. Batas verifikasi 2×24 jam kerja.</p>
    </div>

    @include('admin.verifikasi-toko._tabs')

    <div class="split">
        <aside>
            @include('admin.verifikasi-toko._list')
        </aside>
        <section>
            <div class="empty-detail">
                Pilih satu pengajuan di sebelah kiri untuk memeriksa data dan dokumennya.
            </div>
        </section>
    </div>
@endsection
