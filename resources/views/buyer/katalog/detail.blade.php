@extends('layouts.buyer')

@section('title', $item->nama)

@section('content')
@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');
    $peternak = $item->peternak;
    $user = auth()->user();
    $labelPeruntukan = ['bibit' => 'Bibit', 'pembesaran' => 'Pembesaran', 'siap_potong' => 'Siap potong', 'indukan_kontes' => 'Indukan/kontes'];
    $fotos = collect([$item->foto_utama])->merge($item->galeri ?? [])->filter()->values();
    $desimal = in_array($satuan, ['kg', 'liter'], true);
    $namaPenerima = old('nama_penerima', $user->name ?? '');
@endphp

<div class="mx-auto max-w-7xl">
    <nav class="text-xs text-[#6B7265]">
        <a href="{{ route('buyer.katalog.index') }}" class="hover:underline">Katalog Ternak</a>
        <span class="mx-1">›</span>
        <span class="font-semibold text-[#1C2118]">{{ $item->jenisTernak->nama ?? ($tipe === 'produk' ? 'Hasil Ternak' : 'Ternak Hidup') }}</span>
    </nav>

    @if (session('success') || session('error') || session('info'))
        <div class="mt-4 rounded-lg border px-4 py-3 text-sm {{ session('error') ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' }}">
            {{ session('success') ?? session('error') ?? session('info') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mt-5 grid grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] gap-8">
        {{-- ===== Galeri ===== --}}
        <div>
            <div class="aspect-[4/3] overflow-hidden rounded-2xl border border-[#E4E4DC] bg-[#EEF1EA]">
                @if ($fotos->isNotEmpty())
                    <img id="foto-utama" src="{{ asset('storage/' . $fotos[0]) }}" alt="{{ $item->nama }}" class="h-full w-full object-cover">
                @else
                    <div class="flex h-full items-center justify-center text-sm text-[#9AA093]">Belum ada foto</div>
                @endif
            </div>
            @if ($fotos->count() > 1)
                <div class="mt-3 flex gap-2">
                    @foreach ($fotos as $foto)
                        <img src="{{ asset('storage/' . $foto) }}" alt=""
                             onclick="document.getElementById('foto-utama').src = this.src"
                             class="h-16 w-20 cursor-pointer rounded-lg border border-[#E4E4DC] object-cover hover:border-[#2E5B2A]">
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ===== Info + form pesan ===== --}}
        <div>
            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                <span class="rounded-full bg-[#E9F1E6] px-3 py-1 text-[#2E5B2A]">{{ $tipe === 'produk' ? 'Hasil ternak' : 'Ternak hidup' }}</span>
                <span class="rounded-full bg-[#F6F6F2] px-3 py-1 text-[#4B5245]">{{ ['individual' => 'Individual', 'batch' => 'Batch', 'produk' => 'Produk'][$tipe] }}</span>
                @if ($tipe !== 'produk' && ! empty($item->peruntukan))
                    <span class="rounded-full bg-[#F6F6F2] px-3 py-1 text-[#4B5245]">{{ $labelPeruntukan[$item->peruntukan] ?? $item->peruntukan }}</span>
                @endif
                @if ($tipe === 'produk' && $item->grade_kualitas)
                    <span class="rounded-full bg-[#F6F6F2] px-3 py-1 text-[#4B5245]">Grade {{ $item->grade_kualitas }}</span>
                @endif
                @if ($prestasi->isNotEmpty())
                    <span class="rounded-full bg-[#FEF3C7] px-3 py-1 text-[#B45309]">Berprestasi</span>
                @endif
            </div>

            <h1 class="mt-3 text-2xl font-extrabold leading-snug text-[#1C2118]">{{ $item->nama }}</h1>
            <p class="mt-2 text-3xl font-extrabold text-[#2E5B2A]">{{ $rp($harga) }} <span class="text-sm font-medium text-[#6B7265]">/ {{ $satuan }}</span></p>

            {{-- Penjual --}}
            <div class="mt-4 flex items-center justify-between rounded-2xl border border-[#E4E4DC] bg-white p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-full bg-[#D5E4CF] font-bold text-[#2E5B2A]">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($peternak->nama_usaha, 0, 2)) }}
                    </div>
                    <div>
                        <p class="font-bold text-[#1C2118]">{{ $peternak->nama_usaha }}</p>
                        <p class="text-xs text-[#6B7265]">{{ $peternak->kecamatan ? $peternak->kecamatan . ', ' : '' }}{{ $peternak->kota_kabupaten }}, {{ $peternak->provinsi }}</p>
                    </div>
                    <span class="rounded-full bg-[#DCF3E3] px-2.5 py-0.5 text-[11px] font-semibold text-[#166534]">Terverifikasi</span>
                </div>
                @auth
                    @unless ($punyaSendiri)
                        <form method="POST" action="{{ route('buyer.pesan.mulai') }}">
                            @csrf
                            <input type="hidden" name="peternak_id" value="{{ $peternak->id }}">
                            <button class="rounded-lg border border-[#2E5B2A] px-3 py-2 text-sm font-semibold text-[#2E5B2A] hover:bg-[#E9F1E6]">Hubungi Penjual</button>
                        </form>
                    @endunless
                @endauth
            </div>

            {{-- Data fisik / informasi produk --}}
            <div class="mt-4 rounded-2xl border border-[#E4E4DC] bg-white p-5">
                <h2 class="font-bold text-[#1C2118]">{{ $tipe === 'produk' ? 'Informasi produk' : 'Data fisik' }}</h2>
                <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @if ($tipe === 'produk')
                        <div><dt class="text-[#6B7265]">Stok tersedia</dt><dd class="font-semibold">{{ $angka($stok) }} {{ $satuan }}</dd></div>
                        <div><dt class="text-[#6B7265]">Satuan</dt><dd class="font-semibold">{{ \Illuminate\Support\Str::headline($satuan) }}</dd></div>
                        <div><dt class="text-[#6B7265]">Tanggal produksi</dt><dd class="font-semibold">{{ $item->tanggal_produksi ? \Illuminate\Support\Carbon::parse($item->tanggal_produksi)->translatedFormat('d F Y') : '-' }}</dd></div>
                        <div><dt class="text-[#6B7265]">Kedaluwarsa</dt><dd class="font-semibold">{{ $item->tanggal_kadaluwarsa ? \Illuminate\Support\Carbon::parse($item->tanggal_kadaluwarsa)->translatedFormat('d F Y') : '-' }}</dd></div>
                        <div><dt class="text-[#6B7265]">Grade / kualitas</dt><dd class="font-semibold">{{ $item->grade_kualitas ?: '-' }}</dd></div>
                        <div><dt class="text-[#6B7265]">Penyimpanan</dt><dd class="font-semibold">{{ $item->butuh_pendingin ? 'Simpan dingin' : 'Suhu ruang' }}</dd></div>
                    @else
                        <div><dt class="text-[#6B7265]">Jenis / ras</dt><dd class="font-semibold">{{ $item->jenisTernak->nama ?? '-' }}</dd></div>
                        @if ($tipe === 'individual')
                            <div><dt class="text-[#6B7265]">Jenis kelamin</dt><dd class="font-semibold">{{ \Illuminate\Support\Str::headline($item->jenis_kelamin ?? '-') }}</dd></div>
                            <div><dt class="text-[#6B7265]">Umur</dt><dd class="font-semibold">{{ $item->umur_label ?? ($item->umur ?? '-') }}</dd></div>
                            <div><dt class="text-[#6B7265]">Berat</dt><dd class="font-semibold">{{ $item->berat ? $angka($item->berat) . ' kg' : '-' }}</dd></div>
                        @else
                            <div><dt class="text-[#6B7265]">Stok tersedia</dt><dd class="font-semibold">{{ $angka($stok) }} {{ $satuan }}</dd></div>
                        @endif
                        <div><dt class="text-[#6B7265]">Peruntukan</dt><dd class="font-semibold">{{ $labelPeruntukan[$item->peruntukan] ?? '-' }}</dd></div>
                        <div><dt class="text-[#6B7265]">Satuan jual</dt><dd class="font-semibold">Per {{ $satuan }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Form pemesanan (UC-11) --}}
            <div class="mt-4 rounded-2xl border border-[#E4E4DC] bg-white p-5">
                @guest
                    <p class="text-sm text-[#4B5245]">Masuk atau daftar untuk memesan.</p>
                    <a href="{{ route('login') }}" class="mt-3 block rounded-lg bg-[#2E5B2A] py-3 text-center font-semibold text-white">Masuk untuk Memesan</a>
                @elseif ($punyaSendiri)
                    <p class="text-sm text-[#6B7265]">Ini produk dari toko Anda sendiri, jadi tidak bisa dipesan.</p>
                @else
                    <form method="POST" action="{{ route('buyer.pesanan.store') }}" id="form-pesan"
                          data-harga="{{ $harga }}" data-kota-peternak="{{ $peternak->kota_kabupaten }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="tipe" value="{{ $tipe }}">
                        <input type="hidden" name="item_id" value="{{ $item->id }}">

                        @if ($tipe !== 'individual')
                            <div>
                                <label class="mb-1 block text-sm font-semibold">Jumlah ({{ $satuan }})</label>
                                <input type="number" name="jumlah" id="jumlah" value="{{ old('jumlah', 1) }}" min="{{ $desimal ? '0.01' : '1' }}"
                                       step="{{ $desimal ? '0.01' : '1' }}" max="{{ $stok }}"
                                       class="w-40 rounded-lg border border-[#E4E4DC] px-3 py-2" required>
                                <span class="ml-2 text-xs text-[#6B7265]">Stok {{ $angka($stok) }} {{ $satuan }}</span>
                            </div>
                        @endif

                        <div>
                            <p class="mb-2 text-sm font-semibold">Data penerima</p>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <input name="nama_penerima" value="{{ $namaPenerima }}" placeholder="Nama lengkap" class="rounded-lg border border-[#E4E4DC] px-3 py-2" required>
                                <input name="no_hp_penerima" value="{{ old('no_hp_penerima', $user->no_hp ?? '') }}" placeholder="No. HP / WhatsApp" class="rounded-lg border border-[#E4E4DC] px-3 py-2" required>
                                <input name="alamat_penerima" value="{{ old('alamat_penerima', $user->alamat ?? '') }}" placeholder="Alamat" class="col-span-2 rounded-lg border border-[#E4E4DC] px-3 py-2" required>
                                <input name="kota_kabupaten" id="kota" value="{{ old('kota_kabupaten', $user->kota_kabupaten ?? '') }}" placeholder="Kota/kabupaten" class="rounded-lg border border-[#E4E4DC] px-3 py-2" required>
                                <input name="provinsi" value="{{ old('provinsi', $user->provinsi ?? '') }}" placeholder="Provinsi" class="rounded-lg border border-[#E4E4DC] px-3 py-2" required>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-sm font-semibold">Metode pembayaran</p>
                            <div class="space-y-2 text-sm">
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-[#E4E4DC] p-3 has-[:checked]:border-[#2E5B2A] has-[:checked]:bg-[#E9F1E6]">
                                    <input type="radio" name="metode_bayar" value="transfer_bank" class="mt-1" @checked(old('metode_bayar') === 'transfer_bank')>
                                    <span><b>Transfer Bank Manual</b><br><span class="text-xs text-[#6B7265]">Unggah bukti, diverifikasi admin maksimal 1×24 jam.</span></span>
                                </label>
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-[#E4E4DC] p-3 has-[:checked]:border-[#2E5B2A] has-[:checked]:bg-[#E9F1E6]">
                                    <input type="radio" name="metode_bayar" value="qris" class="mt-1" @checked(old('metode_bayar') === 'qris')>
                                    <span><b>QRIS</b><br><span class="text-xs text-[#6B7265]">Otomatis lunas setelah pembayaran berhasil.</span></span>
                                </label>
                                <label id="opsi-cod" class="flex cursor-pointer items-start gap-3 rounded-lg border border-[#E4E4DC] p-3 has-[:checked]:border-[#2E5B2A] has-[:checked]:bg-[#E9F1E6]">
                                    <input type="radio" name="metode_bayar" value="cod" id="radio-cod" class="mt-1" @checked(old('metode_bayar') === 'cod')>
                                    <span><b>COD (bayar di tempat)</b><br><span id="ket-cod" class="text-xs text-[#6B7265]"></span></span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-[#EDEDE6] pt-4">
                            <span class="text-sm text-[#6B7265]">Total <span class="text-xs">(tanpa ongkir, tidak ada kurir)</span></span>
                            <span id="total" class="text-xl font-extrabold text-[#2E5B2A]">{{ $rp($harga * ($tipe === 'individual' ? 1 : old('jumlah', 1))) }}</span>
                        </div>

                        <button type="submit" class="w-full rounded-lg bg-[#2E5B2A] py-3 font-semibold text-white hover:bg-[#1F421C]">Pesan Sekarang</button>
                        <p class="text-xs text-[#6B7265]">
                            @if ($tipe === 'individual') Setelah dipesan, ternak ini dikunci untuk Anda sampai peternak mengonfirmasi.
                            @else Stok yang Anda pesan ditahan sampai pesanan lunas atau dibatalkan. @endif
                        </p>
                    </form>

                    <script>
                        (function () {
                            const f = document.getElementById('form-pesan');
                            const kunci = v => (v || '').toLowerCase().trim().replace(/^(kota|kabupaten|kab\.?)\s+/, '').trim();
                            const kotaPeternak = kunci(f.dataset.kotaPeternak);
                            const harga = parseFloat(f.dataset.harga);
                            const jumlah = document.getElementById('jumlah');
                            const kota = document.getElementById('kota');
                            const cod = document.getElementById('radio-cod');
                            const ket = document.getElementById('ket-cod');

                            function hitung() {
                                const n = jumlah ? parseFloat(jumlah.value) || 0 : 1;
                                document.getElementById('total').textContent =
                                    'Rp ' + Math.round(harga * n).toLocaleString('id-ID');
                            }
                            function cekCod() {
                                const boleh = kunci(kota.value) === kotaPeternak;
                                cod.disabled = !boleh;
                                document.getElementById('opsi-cod').classList.toggle('opacity-50', !boleh);
                                ket.textContent = boleh
                                    ? 'Bayar tunai saat serah terima langsung. Tersedia karena satu kota/kabupaten.'
                                    : 'Tidak tersedia: COD hanya untuk pembeli satu kota/kabupaten dengan peternak.';
                                if (!boleh && cod.checked) cod.checked = false;
                            }
                            if (jumlah) jumlah.addEventListener('input', hitung);
                            kota.addEventListener('input', cekCod);
                            hitung(); cekCod();
                        })();
                    </script>
                @endguest
            </div>
        </div>
    </div>

    {{-- ===== Deskripsi, silsilah, prestasi ===== --}}
    <div class="mt-8 space-y-5">
        @if ($item->deskripsi)
            <section class="rounded-2xl border border-[#E4E4DC] bg-white p-6">
                <h2 class="font-bold text-[#1C2118]">Deskripsi</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-[#4B5245]">{{ $item->deskripsi }}</p>
                @if ($tipe === 'produk')
                    <p class="mt-3 text-xs text-[#6B7265]">Pesanan otomatis ditolak jika produk melewati tanggal kedaluwarsa.</p>
                @endif
            </section>
        @endif

        @if ($pedigree)
            <section class="rounded-2xl border border-[#E4E4DC] bg-white p-6">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-bold text-[#1C2118]">Silsilah</h2>
                    <span class="text-xs text-[#9AA093]">Opsional dari peternak</span>
                </div>
                <dl class="mt-3 grid grid-cols-3 gap-4 text-sm">
                    <div><dt class="text-[#6B7265]">Ternak ini</dt><dd class="font-semibold">{{ $item->jenisTernak->nama ?? '-' }}</dd></div>
                    <div><dt class="text-[#6B7265]">Induk betina</dt><dd class="font-semibold">{{ $pedigree->induk_betina_nama }} · Ras {{ $pedigree->induk_betina_ras }}</dd></div>
                    <div><dt class="text-[#6B7265]">Pejantan</dt><dd class="font-semibold">{{ $pedigree->pejantan_nama }} · Ras {{ $pedigree->pejantan_ras }}</dd></div>
                </dl>
            </section>
        @endif

        @if ($prestasi->isNotEmpty())
            <section class="rounded-2xl border border-[#E4E4DC] bg-white p-6">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-bold text-[#1C2118]">Riwayat prestasi</h2>
                    <span class="text-xs text-[#9AA093]">Hanya yang terverifikasi</span>
                </div>
                <div class="mt-3 divide-y divide-[#EDEDE6]">
                    @foreach ($prestasi as $p)
                        <div class="flex items-center justify-between py-3 text-sm">
                            <div>
                                <p class="font-semibold text-[#1C2118]">{{ $p->nama_kontes }}</p>
                                <p class="text-xs text-[#6B7265]">
                                    Kategori {{ $p->kategori }} · {{ $p->peringkat }}<br>
                                    {{ \Illuminate\Support\Carbon::parse($p->tanggal_kontes)->translatedFormat('d F Y') }} · {{ $p->penyelenggara }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full bg-[#DCF3E3] px-2.5 py-0.5 text-[11px] font-semibold text-[#166534]">Terverifikasi</span>
                                @if ($p->berkas_sertifikat)
                                    <a href="{{ asset('storage/' . $p->berkas_sertifikat) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-[#2E5B2A] hover:underline">Lihat sertifikat</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
@endsection