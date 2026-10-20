@extends('layouts.seller')
@section('title', 'Buka Toko')
@section('content')
<h1 class="text-2xl font-bold mb-4">Buka Toko</h1>

@if ($pengajuan && $pengajuan->status === 'pending_verification')
    <div class="rounded border border-yellow-300 bg-yellow-50 p-4">
        <p class="font-semibold">Pengajuan Anda sedang diverifikasi admin.</p>
        <p class="text-sm mt-1">Toko: {{ $pengajuan->nama_toko }} · dikirim {{ $pengajuan->created_at->translatedFormat('d F Y H:i') }}.
           Estimasi maksimal 2x24 jam kerja. Selama menunggu, Anda tetap dapat berbelanja.</p>
    </div>
@else
    @if ($pengajuan && $pengajuan->status === 'rejected')
        <div class="mb-4 rounded border border-red-300 bg-red-50 p-4 text-sm">
            <p class="font-semibold text-red-700">Pengajuan sebelumnya ditolak.</p>
            <p>Alasan: {{ $pengajuan->catatan_admin ?? '-' }}</p>
            <p class="mt-1">Silakan perbaiki data dan ajukan kembali.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('seller.toko.pengajuan.store') }}" enctype="multipart/form-data" class="bg-white rounded shadow p-6 space-y-4">
        @csrf
        <div class="grid md:grid-cols-2 gap-4">
            <label class="block text-sm">Nama toko
                <input name="nama_toko" value="{{ old('nama_toko') }}" class="mt-1 w-full border rounded px-3 py-2" required></label>
            <label class="block text-sm">Nama pemilik (sesuai KTP)
                <input name="nama_pemilik" value="{{ old('nama_pemilik', auth()->user()->name) }}" class="mt-1 w-full border rounded px-3 py-2" required></label>
        </div>

        <fieldset>
            <legend class="text-sm font-medium">Kategori usaha (pilih satu atau lebih)</legend>
            <div class="flex flex-wrap gap-4 mt-1 text-sm">
                @foreach (['unggas' => 'Unggas', 'ternak_kecil' => 'Ternak Kecil', 'ternak_besar' => 'Ternak Besar', 'hasil_ternak' => 'Hasil Ternak'] as $v => $l)
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="kategori_usaha[]" value="{{ $v }}" @checked(in_array($v, old('kategori_usaha', [])))> {{ $l }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="grid md:grid-cols-3 gap-4">
            <label class="block text-sm">Kecamatan
                <input name="kecamatan" value="{{ old('kecamatan') }}" class="mt-1 w-full border rounded px-3 py-2"></label>
            <label class="block text-sm">Kota/Kabupaten
                <input name="kota_kabupaten" value="{{ old('kota_kabupaten') }}" class="mt-1 w-full border rounded px-3 py-2" required></label>
            <label class="block text-sm">Provinsi
                <input name="provinsi" value="{{ old('provinsi') }}" class="mt-1 w-full border rounded px-3 py-2" required></label>
        </div>
        <label class="block text-sm">Alamat usaha
            <textarea name="alamat" rows="2" class="mt-1 w-full border rounded px-3 py-2" required>{{ old('alamat') }}</textarea></label>

        <div class="grid md:grid-cols-3 gap-4 text-sm">
            <label>Foto KTP <input type="file" name="foto_ktp" accept="image/*" class="mt-1 block w-full" required></label>
            <label>Foto lokasi usaha <input type="file" name="foto_lokasi" accept="image/*" class="mt-1 block w-full" required></label>
            <label>Buku rekening (JPG/PNG/PDF) <input type="file" name="buku_rekening" accept="image/*,application/pdf" class="mt-1 block w-full" required></label>
        </div>
        <p class="text-xs text-gray-500">Maksimal 5 MB per berkas. Dokumen disimpan privat dan hanya dilihat admin verifikator.</p>

        <div class="grid md:grid-cols-3 gap-4">
            <label class="block text-sm">Bank
                <input name="bank_nama" value="{{ old('bank_nama') }}" class="mt-1 w-full border rounded px-3 py-2" required></label>
            <label class="block text-sm">No. rekening
                <input name="bank_no_rekening" value="{{ old('bank_no_rekening') }}" inputmode="numeric" class="mt-1 w-full border rounded px-3 py-2" required></label>
            <label class="block text-sm">Atas nama
                <input name="bank_atas_nama" value="{{ old('bank_atas_nama') }}" class="mt-1 w-full border rounded px-3 py-2" required></label>
        </div>

        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="pernyataan" value="1" class="mt-1" @checked(old('pernyataan'))>
            Saya menyatakan bahwa seluruh data yang saya isi adalah benar.
        </label>

        <button class="bg-green-700 text-white px-5 py-2 rounded">Kirim Pengajuan</button>
    </form>
@endif
@endsection
