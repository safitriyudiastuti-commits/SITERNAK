@extends('layouts.seller')
@section('title', 'Buka Toko Saya')
@section('content')
@php
    $kategori = ['unggas' => 'Unggas', 'ternak_kecil' => 'Ternak kecil', 'ternak_besar' => 'Ternak besar', 'hasil_ternak' => 'Hasil ternak'];
    $provinsi = ['Aceh', 'Sumatera Utara', 'Sumatera Barat', 'Riau', 'Kepulauan Riau', 'Jambi', 'Sumatera Selatan',
        'Kepulauan Bangka Belitung', 'Bengkulu', 'Lampung', 'DKI Jakarta', 'Jawa Barat', 'Banten', 'Jawa Tengah',
        'DI Yogyakarta', 'Jawa Timur', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Kalimantan Barat',
        'Kalimantan Tengah', 'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara', 'Sulawesi Utara',
        'Gorontalo', 'Sulawesi Tengah', 'Sulawesi Barat', 'Sulawesi Selatan', 'Sulawesi Tenggara', 'Maluku',
        'Maluku Utara', 'Papua', 'Papua Barat', 'Papua Selatan', 'Papua Tengah', 'Papua Pegunungan', 'Papua Barat Daya'];
    $menunggu = $pengajuan && $pengajuan->status === 'pending_verification';
@endphp

<p class="text-xs text-gray-500 mb-2">Profil › Toko Saya › <b class="text-gray-800">Buka Toko</b></p>
<h1 class="text-2xl font-bold">Buka Toko Saya</h1>
<p class="text-sm text-gray-500 mb-6">Lengkapi data usaha untuk mulai berjualan. Diverifikasi tim SITERNAK maks. 2×24 jam kerja.</p>

<div class="grid lg:grid-cols-3 gap-6 items-start">
    <div class="lg:col-span-2">

        @if ($menunggu)
            <div class="rounded-xl border border-yellow-300 bg-yellow-50 p-5">
                <p class="font-semibold">Pengajuan Anda sedang diverifikasi.</p>
                <p class="text-sm mt-1">Toko <b>{{ $pengajuan->nama_toko }}</b> · dikirim {{ $pengajuan->created_at->translatedFormat('d F Y H:i') }}.
                   Selama menunggu, Anda tetap dapat berbelanja.</p>
            </div>
        @else
            @if ($pengajuan && $pengajuan->status === 'rejected')
                <div class="mb-4 rounded-xl border border-red-300 bg-red-50 p-4 text-sm">
                    <p class="font-semibold text-red-700">Pengajuan sebelumnya ditolak.</p>
                    <p>Alasan: {{ $pengajuan->catatan_admin ?? '-' }}</p>
                    <p class="mt-1">Silakan perbaiki data lalu ajukan kembali.</p>
                </div>
            @endif

            <form method="POST" action="{{ route('seller.toko.pengajuan.store') }}" enctype="multipart/form-data"
                  class="bg-white rounded-xl border p-6 space-y-5">
                @csrf
                <h2 class="font-semibold">Data usaha</h2>

                <label class="block text-sm font-medium">Nama toko <span class="text-red-600">*</span>
                    <input name="nama_toko" value="{{ old('nama_toko') }}" placeholder="Peternakan Sumber Rejeki"
                           class="mt-1 w-full border rounded-lg px-3 py-2 font-normal" required>
                </label>

                <fieldset>
                    <legend class="text-sm font-medium">Kategori usaha <span class="text-red-600">*</span></legend>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($kategori as $v => $l)
                            <label class="cursor-pointer">
                                <input type="checkbox" name="kategori_usaha[]" value="{{ $v }}" class="peer sr-only"
                                       @checked(in_array($v, old('kategori_usaha', [])))>
                                <span class="inline-block px-4 py-1.5 rounded-full border text-sm text-gray-600
                                             peer-checked:bg-green-800 peer-checked:text-white peer-checked:border-green-800">{{ $l }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="grid md:grid-cols-2 gap-4">
                    <label class="block text-sm font-medium">Kota/Kabupaten <span class="text-red-600">*</span>
                        <input name="kota_kabupaten" value="{{ old('kota_kabupaten') }}" placeholder="Banyuwangi"
                               class="mt-1 w-full border rounded-lg px-3 py-2 font-normal" required>
                    </label>
                    <label class="block text-sm font-medium">Provinsi <span class="text-red-600">*</span>
                        <select name="provinsi" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal bg-white" required>
                            <option value="">Pilih provinsi</option>
                            @foreach ($provinsi as $p)
                                <option value="{{ $p }}" @selected(old('provinsi') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="block text-sm font-medium">Alamat usaha <span class="text-red-600">*</span>
                    <textarea name="alamat" rows="2" placeholder="Dusun, desa, kecamatan"
                              class="mt-1 w-full border rounded-lg px-3 py-2 font-normal" required>{{ old('alamat') }}</textarea>
                </label>

                <div>
                    <p class="text-sm font-medium">Dokumen verifikasi</p>
                    <p class="text-xs text-gray-500 mb-2">Jelas dan terbaca. JPG/PNG (buku rekening boleh PDF), maks. 5 MB per berkas.</p>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach ([['foto_ktp', 'Foto KTP', 'image/*'], ['foto_lokasi', 'Foto lokasi usaha', 'image/*'], ['buku_rekening', 'Buku rekening', 'image/*,application/pdf']] as [$nama, $label, $accept])
                            <label class="flex flex-col items-center justify-center gap-1 h-28 px-2 text-center text-xs text-gray-500
                                          border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 16V4m0 0l-4 4m4-4l4 4M4 20h16"/></svg>
                                <span class="break-all">{{ $label }}</span>
                                <input type="file" name="{{ $nama }}" accept="{{ $accept }}" class="sr-only" required
                                       data-label="{{ $label }}"
                                       onchange="this.previousElementSibling.textContent = this.files[0] ? this.files[0].name : this.dataset.label">
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="flex items-start gap-2 text-sm">
                    <input type="checkbox" name="pernyataan" value="1" class="mt-1" @checked(old('pernyataan'))>
                    Saya menyatakan data di atas benar dan bersedia identitas dicek oleh admin.
                </label>

                <button class="bg-green-800 hover:bg-green-900 text-white text-sm font-medium px-5 py-2 rounded-lg">Ajukan Verifikasi</button>
            </form>
        @endif
    </div>

    <aside class="bg-white rounded-xl border p-5 space-y-4 text-sm">
        <h2 class="font-semibold">Yang perlu diketahui</h2>
        <div>
            <p class="font-medium">Diverifikasi manual</p>
            <p class="text-xs text-gray-500">Tim SITERNAK memeriksa dokumen Anda sebelum toko diaktifkan.</p>
        </div>
        <div>
            <p class="font-medium">Maksimal 2×24 jam kerja</p>
            <p class="text-xs text-gray-500">Hasilnya ditampilkan di halaman ini, termasuk alasan jika ditolak.</p>
        </div>
        <div>
            <p class="font-medium">Tetap bisa belanja</p>
            <p class="text-xs text-gray-500">Hak berjualan aktif setelah disetujui; berbelanja tidak terganggu.</p>
        </div>
    </aside>
</div>
@endsection