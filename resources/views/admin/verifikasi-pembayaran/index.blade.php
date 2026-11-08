@extends('layouts.admin')

@section('title', 'Verifikasi pembayaran')

@section('content')
    <div class="page-head">
        <h1>Verifikasi pembayaran transfer</h1>
        <p>Cocokkan nominal dan bukti transfer dengan rekening tujuan. Batas verifikasi 1×24 jam.</p>
    </div>

    @include('admin.verifikasi-pembayaran._tabs')

    <div class="split">
        <aside>
            @include('admin.verifikasi-pembayaran._list')
        </aside>
        <section>
            <div class="empty-detail">
                Pilih satu pembayaran di sebelah kiri untuk memeriksa bukti transfernya.
            </div>
        </section>
    </div>
@endsection