@extends('admin.layouts.app')
@section('title','Dashboard Admin')
@section('content')
<h1>Dashboard Admin</h1><div class="grid">
@foreach(['Pengajuan toko'=>$pendingStores,'Sertifikat kontes'=>$pendingContests,'Pembayaran perlu dicek'=>$pendingPayments,'Perubahan rekening'=>$pendingBankChanges,'Pesanan selesai'=>$completedOrders] as $label=>$count)<div class="card"><div class="muted">{{ $label }}</div><h2>{{ $count }}</h2></div>@endforeach</div>
<div class="card"><h2>Pengajuan toko terbaru</h2><table><tr><th>Toko</th><th>Pemilik</th><th>Kota</th><th>Status</th><th></th></tr>@forelse($recentStores as $store)<tr><td>{{ $store->name }}</td><td>{{ $store->owner?->name }}</td><td>{{ $store->regency ?: $store->city }}</td><td>{{ $store->status }}</td><td><a href="{{ route('admin.stores.show',$store) }}">Detail</a></td></tr>@empty<tr><td colspan="5">Belum ada toko.</td></tr>@endforelse</table></div>
@endsection
