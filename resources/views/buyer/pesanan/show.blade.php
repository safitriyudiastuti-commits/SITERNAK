@extends('layouts.buyer')

@section('title', 'Detail Pesanan')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

    <a href="{{ route('buyer.pesanan.index') }}"
       class="mb-6 inline-flex text-sm font-medium text-slate-500 hover:text-slate-800">
        ← Kembali ke Pesanan Saya
    </a>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800">
            Detail Pesanan
        </h1>
        <p class="mt-2 text-sm text-slate-500">
            Nomor pesanan: {{ $pesanan->nomor_order }}
        </p>
    </div>

    {{-- Status --}}
    @php
        $statusLabel = [
            'menunggu_konfirmasi' => 'Menunggu Konfirmasi',
            'diproses' => 'Sedang Diproses',
            'dikirim' => 'Sedang Dikirim',
            'selesai' => 'Pesanan Selesai',
            'dibatalkan' => 'Pesanan Dibatalkan',
        ];

        $statusSaatIni = $statusLabel[$pesanan->status] ?? ucfirst($pesanan->status);

        $timeline = [
            'menunggu_konfirmasi' => 'Pesanan Dibuat',
            'diproses' => 'Pesanan Diproses',
            'dikirim' => 'Pesanan Dikirim',
            'selesai' => 'Pesanan Diterima',
        ];
    @endphp

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-slate-500">Status saat ini</p>
        <h2 class="mt-2 text-xl font-bold text-slate-800">
            {{ $statusSaatIni }}
        </h2>
    </div>

    {{-- Alasan Penolakan --}}
    @if ($pesanan->status === 'ditolak')
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-6">
            <h2 class="font-bold text-red-800">
                Pesanan Ditolak
            </h2>

            <p class="mt-2 text-sm text-red-700">
                Mohon maaf, pesanan kamu tidak dapat diproses oleh peternak.
            </p>

            @if ($pesanan->alasan_penolakan)
                <div class="mt-4 rounded-xl bg-white p-4">
                    <p class="text-sm font-semibold text-slate-700">
                        Alasan penolakan:
                    </p>

                    <p class="mt-2 text-sm text-slate-600">
                        {{ $pesanan->alasan_penolakan }}
                    </p>
                </div>
            @else
                <p class="mt-3 text-sm text-red-600">
                    Peternak belum memberikan alasan penolakan.
                </p>
            @endif
        </div>
    @endif

    {{-- Timeline --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-6 text-lg font-bold text-slate-800">
            Perjalanan Pesanan
        </h2>

        @forelse ($pesanan->statusHistories as $riwayat)
            <div class="relative flex gap-4 pb-7 last:pb-0">
                <div class="flex flex-col items-center">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-100 text-green-600">
                        ✓
                    </div>

                    @if (!$loop->last)
                        <div class="mt-1 h-full w-0.5 bg-slate-200"></div>
                    @endif
                </div>

                <div class="pb-2">
                    <p class="font-semibold text-slate-800">
                        {{ $statusLabel[$riwayat->status] ?? ucfirst($riwayat->status) }}
                    </p>

                    @if ($riwayat->keterangan)
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $riwayat->keterangan }}
                        </p>
                    @endif

                    <p class="mt-1 text-xs text-slate-400">
                        {{ $riwayat->created_at?->format('d M Y, H:i') }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">
                Belum ada riwayat perubahan status pesanan.
            </p>
        @endforelse
    </div>

    {{-- Informasi Barang --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-slate-800">
            Informasi Pesanan
        </h2>

        <p class="font-semibold text-slate-800">
            {{ $pesanan->nama_item }}
        </p>

        <div class="mt-4 space-y-2 text-sm text-slate-600">
            <div class="flex justify-between gap-4">
                <span>Jumlah</span>
                <span>{{ number_format((float) $pesanan->jumlah, 2, ',', '.') }} {{ $pesanan->satuan ?? 'ekor' }}</span>
            </div>

            <div class="flex justify-between gap-4">
                <span>Harga satuan</span>
                <span>Rp{{ number_format((float) $pesanan->harga_satuan, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between gap-4 border-t pt-3 font-bold text-slate-800">
                <span>Total</span>
                <span>Rp{{ number_format((float) $pesanan->total_harga, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Informasi Penerima --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-lg font-bold text-slate-800">
            Informasi Penerima
        </h2>

        <p class="font-semibold text-slate-800">
            {{ $pesanan->nama_penerima }}
        </p>

        <p class="mt-1 text-sm text-slate-500">
            {{ $pesanan->no_hp_penerima }}
        </p>

        <p class="mt-2 text-sm text-slate-500">
            {{ $pesanan->alamat_penerima }},
            {{ $pesanan->kota_kabupaten_penerima }},
            {{ $pesanan->provinsi_penerima }}
        </p>
    </div>

    {{-- Konfirmasi Diterima --}}
    @if ($pesanan->status === 'dikirim')
        <div class="rounded-2xl border border-green-200 bg-green-50 p-6">
            <h2 class="font-bold text-green-800">
                Apakah pesanan sudah diterima?
            </h2>

            <p class="mt-2 text-sm text-green-700">
                Konfirmasikan jika ternak sudah sampai dan diterima dengan baik.
            </p>

            <form
                action="{{ route('buyer.pesanan.konfirmasi', $pesanan->id) }}"
                method="POST"
                class="mt-4"
                onsubmit="return confirm('Yakin pesanan sudah diterima?')"
            >
                @csrf

                <button
                    type="submit"
                    class="rounded-xl bg-green-700 px-5 py-3 text-sm font-semibold text-white hover:bg-green-800"
                >
                    Konfirmasi Pesanan Diterima
                </button>
            </form>
        </div>
    @endif

    {{-- Ulasan --}}
    @php($ulasan = $pesanan->review)
    @if ($pesanan->status === 'selesai')
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @if (session('success'))
                <div role="status" class="mb-4 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ((int) auth()->id() === (int) $pesanan->buyer_id && $ulasan)
                <h2 class="font-bold text-slate-800">Ulasan Anda</h2>
                <div class="mt-3 flex items-center gap-1 text-amber-500" aria-label="Rating {{ $ulasan->rating }} dari 5">
                    @for ($bintang = 1; $bintang <= 5; $bintang++)
                        <span aria-hidden="true">{{ $bintang <= $ulasan->rating ? '★' : '☆' }}</span>
                    @endfor
                    <span class="ml-2 text-sm font-semibold text-slate-700">{{ $ulasan->rating }}/5</span>
                </div>

                @if ($ulasan->komentar)
                    <p class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ $ulasan->komentar }}</p>
                @endif

                @if ($ulasan->foto_path)
                    <a href="{{ Storage::disk('public')->url($ulasan->foto_path) }}" target="_blank" rel="noopener noreferrer"
                       class="mt-4 inline-block">
                        <img src="{{ Storage::disk('public')->url($ulasan->foto_path) }}" alt="Foto ulasan pesanan"
                             class="max-h-64 rounded-xl border border-slate-200 object-cover">
                    </a>
                @endif

                @if ($ulasan->balasan)
                    <div class="mt-5 rounded-xl border border-green-200 bg-green-50 p-4">
                        <p class="font-semibold text-green-900">Balasan dari toko</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-green-800">{{ $ulasan->balasan }}</p>
                        <p class="mt-2 text-xs text-green-700">{{ $ulasan->dibalas_at?->format('d M Y, H:i') }}</p>
                    </div>
                @endif
            @elseif ((int) auth()->id() === (int) $pesanan->buyer_id)
                <h2 class="font-bold text-slate-800">Bagaimana pengalaman Anda?</h2>
                <p class="mt-1 text-sm text-slate-500">Bagikan penilaian untuk pesanan ini kepada pembeli lain.</p>

                <form action="{{ route('buyer.ulasan.store', $pesanan->id) }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                    @csrf
                    <fieldset>
                        <legend class="mb-2 text-sm font-medium text-slate-700">Rating <span class="text-red-600">*</span></legend>
                        <div class="flex flex-row-reverse justify-end gap-1">
                            @for ($bintang = 5; $bintang >= 1; $bintang--)
                                <label class="cursor-pointer text-3xl leading-none text-slate-300 has-[:checked]:text-amber-400">
                                    <input type="radio" name="rating" value="{{ $bintang }}" required
                                           @checked((string) old('rating') === (string) $bintang)
                                           class="peer sr-only">
                                    <span aria-hidden="true" class="peer-checked:text-amber-400 hover:text-amber-300">★</span>
                                    <span class="sr-only">{{ $bintang }} bintang</span>
                                </label>
                            @endfor
                        </div>
                        @error('rating')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <div>
                        <label for="komentar-ulasan" class="mb-1 block text-sm font-medium text-slate-700">Komentar (opsional)</label>
                        <textarea id="komentar-ulasan" name="komentar" rows="4" maxlength="1000"
                                  class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-green-600 focus:outline-none focus:ring-2 focus:ring-green-100">{{ old('komentar') }}</textarea>
                        @error('komentar')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="foto-ulasan" class="mb-1 block text-sm font-medium text-slate-700">Foto (opsional)</label>
                        <input id="foto-ulasan" type="file" name="foto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                               class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                        <p class="mt-1 text-xs text-slate-500">JPG, PNG, atau WEBP; maksimal 5 MB.</p>
                        @error('foto')
                            <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="rounded-xl bg-green-700 px-5 py-3 text-sm font-semibold text-white hover:bg-green-800">
                        Kirim Ulasan
                    </button>
                </form>
            @endif
        </section>
    @endif

</div>
@endsection