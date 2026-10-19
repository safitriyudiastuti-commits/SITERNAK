@extends('admin.layouts.app') @section('title','Detail Verifikasi Toko') @section('content')
<h1>Pengajuan: {{ $store->name }}</h1>
<div class="card">
<p><b>Pemilik:</b> {{ $store->owner?->name }} ({{ $store->owner?->email }})</p>
<p><b>Telepon:</b> {{ $store->contact_phone ?: $store->owner?->phone ?: 'Belum diisi' }}</p>
<p><b>Kategori:</b> {{ $store->business_category }}</p>
<p><b>Wilayah:</b> {{ collect([$store->district, $store->regency ?: $store->city, $store->province])->filter()->join(', ') ?: 'Belum diisi' }}</p>
<p><b>Alamat usaha:</b> {{ $store->business_address }}</p><p><b>Status:</b> {{ $store->status }}</p>
<h3>Dokumen verifikasi</h3>
<ul>@forelse($store->documents as $document)<li>{{ str_replace('_', ' ', $document->document_type) }} — {{ $document->original_name ?? 'Dokumen' }} — {{ $document->status }} <a href="{{ route('admin.stores.document',$document) }}">Lihat/unduh</a></li>@empty<li>Belum ada dokumen.</li>@endforelse</ul>
</div>
<div class="card"><form method="post" action="{{ route('admin.stores.update',$store) }}">@csrf
<h3>Checklist pemeriksaan (wajib dicentang untuk menyetujui)</h3>
<label><input type="checkbox" name="check_ktp" value="1"> Foto KTP jelas dan nama cocok dengan pemilik toko</label>
<label><input type="checkbox" name="check_location" value="1"> Foto lokasi usaha sesuai alamat yang diisi</label>
<label><input type="checkbox" name="check_bank_document" value="1"> Buku rekening sesuai nama pemilik toko</label>
<label>Alasan penolakan (wajib jika ditolak)<textarea name="review_note">{{ old('review_note') }}</textarea></label>
<div class="actions"><button name="decision" value="approved">Setujui peternak</button><button class="danger" name="decision" value="rejected">Tolak</button></div>
</form></div><a href="{{ route('admin.stores.index') }}">← Kembali</a>@endsection
