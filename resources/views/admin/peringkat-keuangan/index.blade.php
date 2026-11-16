@extends('layouts.admin')

@section('title', 'Peringkat keuangan')

@section('content')
    @php
        $params = array_filter($f, fn ($v) => $v !== null && $v !== '');
        $maxValue = max(array_column($chart, 'value') ?: [1]) ?: 1;
        $isRegion = $tampil === 'wilayah';
    @endphp

    <style>
        .pk-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .pk-export { display: flex; gap: 8px; }
        .pk-filter { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 12px; margin: 16px 0; padding: 14px 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; }
        .pk-filter label { display: block; font-size: .8rem; color: #6b7280; margin-bottom: 4px; }
        .pk-filter select { min-width: 150px; padding: 9px 10px; border: 1px solid #d9d9cf; border-radius: 10px; background: #fff; font: inherit; }
        .pk-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; }
        @media (max-width: 900px) { .pk-grid { grid-template-columns: 1fr; } }
        .pk-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 8px 20px 16px; }
        .pk-table { width: 100%; border-collapse: collapse; }
        .pk-table th { text-align: left; font-size: .8rem; color: #6b7280; font-weight: 600; padding: 12px 8px; border-bottom: 1px solid #eee; }
        .pk-table td { padding: 12px 8px; border-bottom: 1px solid #f3f3ee; vertical-align: top; }
        .pk-table td small { display: block; color: #6b7280; }
        .pk-rank { width: 36px; font-weight: 700; color: #2d5a2d; }
        .pk-num { text-align: right; font-weight: 700; white-space: nowrap; }
        .pk-table th.pk-num { text-align: right; }
        .pk-side { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px 20px; }
        .pk-side h3 { margin: 0 0 2px; font-size: 1rem; }
        .pk-side > small { color: #6b7280; }
        .pk-bar { margin-top: 14px; }
        .pk-bar-head { display: flex; justify-content: space-between; font-size: .9rem; margin-bottom: 4px; }
        .pk-track { height: 8px; background: #e8f1e6; border-radius: 999px; overflow: hidden; }
        .pk-fill { height: 100%; background: #2d5a2d; border-radius: 999px; }
        .pk-foot { color: #6b7280; font-size: .85rem; margin: 12px 0 0; }
        @media print { .pk-noprint { display: none !important; } }
    </style>

    <div class="page-head">
        <div class="pk-top">
            <div>
                <h1>Peringkat keuangan</h1>
                <p>Peternak dan wilayah dengan nilai penjualan tertinggi.</p>
            </div>
            <div class="pk-export pk-noprint">
                <a class="btn btn-ghost" href="{{ route('admin.peringkat-keuangan.ekspor', $params + ['tampil' => $tampil]) }}">Ekspor Excel</a>
                <button type="button" class="btn btn-ghost" onclick="window.print()">Ekspor PDF</button>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.peringkat-keuangan.index') }}" class="pk-filter pk-noprint">
        <input type="hidden" name="tampil" value="{{ $tampil }}">

        <div>
            <label for="periode">Periode</label>
            <select id="periode" name="periode">
                @forelse ($periods as $p)
                    <option value="{{ $p }}" @selected($f['periode'] === $p)>
                        {{ \Illuminate\Support\Carbon::parse($p)->locale('id')->translatedFormat('F Y') }}
                    </option>
                @empty
                    <option value="">Belum ada data</option>
                @endforelse
            </select>
        </div>

        <div>
            <label for="provinsi">Provinsi</label>
            <select id="provinsi" name="provinsi">
                <option value="">Semua</option>
                @foreach ($provinces as $item)
                    <option value="{{ $item }}" @selected($f['provinsi'] === $item)>{{ $item }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="kota">Kota/kabupaten</label>
            <select id="kota" name="kota">
                <option value="">Semua</option>
                @foreach ($cities as $item)
                    <option value="{{ $item }}" @selected($f['kota'] === $item)>{{ $item }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="kecamatan">Kecamatan</label>
            <select id="kecamatan" name="kecamatan" @disabled($districts->isEmpty())>
                <option value="">Semua</option>
                @foreach ($districts as $item)
                    <option value="{{ $item }}" @selected($f['kecamatan'] === $item)>{{ $item }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Terapkan</button>
    </form>

    <p class="note">Admin hanya melihat total nilai penjualan. Rincian transaksi individual peternak tidak dapat dibuka.</p>

    <nav class="tabs pk-noprint">
        <a href="{{ route('admin.peringkat-keuangan.index', $params + ['tampil' => 'peternak']) }}"
           class="tab {{ ! $isRegion ? 'is-active' : '' }}">Peringkat peternak</a>
        <a href="{{ route('admin.peringkat-keuangan.index', $params + ['tampil' => 'wilayah']) }}"
           class="tab {{ $isRegion ? 'is-active' : '' }}">Peringkat wilayah</a>
    </nav>

    <div class="pk-grid">
        <section class="pk-card">
            <table class="pk-table">
                <thead>
                    @if ($isRegion)
                        <tr>
                            <th>#</th>
                            <th>Kota/kabupaten</th>
                            <th>Peternak aktif</th>
                            <th class="pk-num">Total penjualan</th>
                        </tr>
                    @else
                        <tr>
                            <th>#</th>
                            <th>Peternak</th>
                            <th>Kota/kabupaten</th>
                            <th class="pk-num">Total penjualan</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        @if ($isRegion)
                            <tr>
                                <td class="pk-rank">{{ $rows->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $row->city }}</strong><small>{{ $row->province }}</small></td>
                                <td>{{ $row->farmers }} peternak</td>
                                <td class="pk-num">{{ \App\Models\SalesTotal::juta((int) $row->total) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="pk-rank">{{ $rows->firstItem() + $loop->index }}</td>
                                <td><strong>{{ $row->store_name }}</strong><small>{{ $row->owner_name }}</small></td>
                                <td>{{ $row->city }}<small>{{ $row->province }}</small></td>
                                <td class="pk-num">{{ \App\Models\SalesTotal::juta($row->total_sales) }}</td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="4" class="empty">Belum ada data penjualan untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <p class="pk-foot">
                Menampilkan {{ $rows->count() }} dari {{ $rows->total() }} {{ $isRegion ? 'kota/kabupaten' : 'peternak' }}
            </p>
            <div class="pk-noprint">{{ $rows->links() }}</div>
        </section>

        <aside class="pk-side">
            <h3>{{ $isRegion ? 'Peringkat peternak teratas' : 'Peringkat kota/kabupaten' }}</h3>
            <small>Juta rupiah</small>

            @forelse ($chart as $i => $bar)
                <div class="pk-bar">
                    <div class="pk-bar-head">
                        <span>{{ $i + 1 }}. {{ $bar['label'] }}</span>
                        <strong>{{ number_format($bar['value'] / 1000000, 1, '.', '') }}</strong>
                    </div>
                    <div class="pk-track"><div class="pk-fill" style="width: {{ round($bar['value'] / $maxValue * 100) }}%"></div></div>
                </div>
            @empty
                <p class="pk-foot">Belum ada data.</p>
            @endforelse
        </aside>
    </div>
@endsection
