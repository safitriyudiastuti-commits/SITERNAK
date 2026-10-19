@extends('admin.layouts.app')
@section('content')
<h1>Peringkat Keuangan</h1>
<div class="card">
    <p>Menampilkan nilai penjualan agregat. Admin tidak membuka rincian transaksi individual.</p>
    <form class="actions" method="get">
        <label>Periode awal <input type="date" name="from" value="{{ $from->toDateString() }}"></label>
        <label>Periode akhir <input type="date" name="to" value="{{ $to->toDateString() }}"></label>
        <label>Provinsi <select name="province"><option value="">Semua</option>@foreach($provinces as $province)<option value="{{ $province }}" @selected(request('province')===$province)>{{ $province }}</option>@endforeach</select></label>
        <label>Kota/kabupaten <select name="regency"><option value="">Semua</option>@foreach($regencies as $regency)<option value="{{ $regency }}" @selected(request('regency')===$regency)>{{ $regency }}</option>@endforeach</select></label>
        <label>Kecamatan <select name="district"><option value="">Semua</option>@foreach($districts as $district)<option value="{{ $district }}" @selected(request('district')===$district)>{{ $district }}</option>@endforeach</select></label>
        <button>Terapkan</button>
        <a class="button" href="{{ route('admin.reports.export',request()->query()) }}">Ekspor CSV (bisa dibuka di Excel)</a>
    </form>
    <p>Pesanan selesai: <b>{{ $summary->order_count }}</b> · Total penjualan: <b>Rp {{ number_format((float)$summary->revenue,0,',','.') }}</b></p>
</div>
<div class="card">
    <h2>Peringkat peternak</h2>
    <table><tr><th>No.</th><th>Toko</th><th>Jumlah pesanan selesai</th><th>Total penjualan</th></tr>
    @forelse($ranking as $index => $store)<tr><td>{{ $index + 1 }}</td><td>{{ $store->name }}</td><td>{{ $store->order_count }}</td><td>Rp {{ number_format((float)$store->revenue,0,',','.') }}</td></tr>
    @empty<tr><td colspan="4">Belum ada data pada filter ini.</td></tr>@endforelse
    </table>
</div>
<div class="card">
    <h2>Peringkat wilayah</h2>
    <table><tr><th>No.</th><th>Kecamatan</th><th>Kota/kabupaten</th><th>Provinsi</th><th>Jumlah pesanan</th><th>Total penjualan</th></tr>
    @forelse($regionRanking as $index => $region)<tr><td>{{ $index + 1 }}</td><td>{{ $region->district ?: '—' }}</td><td>{{ $region->regency ?: '—' }}</td><td>{{ $region->province ?: '—' }}</td><td>{{ $region->order_count }}</td><td>Rp {{ number_format((float)$region->revenue,0,',','.') }}</td></tr>
    @empty<tr><td colspan="6">Belum ada data wilayah pada filter ini.</td></tr>@endforelse
    </table>
</div>
@endsection
