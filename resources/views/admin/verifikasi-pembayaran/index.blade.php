@extends('layouts.admin')

@section('title', 'Verifikasi pembayaran')

@section('content')
    <div class="page-head">
        <h1>Verifikasi pembayaran</h1>
        <p>
            Periksa pembayaran pembeli melalui transfer bank atau QRIS DEMO.
            Pastikan nominal sesuai sebelum memberikan keputusan.
        </p>
    </div>

    @include('admin.verifikasi-pembayaran._tabs')

    <div class="split">
        <aside>
            @include('admin.verifikasi-pembayaran._list')
        </aside>

        <section>
            <div class="empty-detail">
                Pilih satu pembayaran di sebelah kiri untuk melihat
                rincian dan melakukan verifikasi.
            </div>
        </section>
    </div>
@endsection