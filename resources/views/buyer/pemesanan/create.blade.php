@extends('layouts.buyer')

@section('title', 'Pemesanan')

@section('content')
@php
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $angka = fn ($n) => number_format((float) $n, fmod((float) $n, 1) == 0 ? 0 : 2, ',', '.');
    $individual = $r->tipe === 'individual';
    $metode = old('metode_bayar', $codBisa ? 'cod' : 'qris');

    $label = 'mb-1.5 block text-[0.78125rem] font-semibold leading-4 text-[#1C2118]';
    $isian = 'data-pembeli h-10 w-full rounded-[0.625rem] border border-[#E4E4DC] px-3.5 text-[0.84375rem] font-medium outline-none focus:border-[#2E5B2A] read-only:bg-[#F6F6F2] read-only:text-[#6B7265]';
    $kartu = 'group relative flex h-[5.75rem] cursor-pointer items-start rounded-[0.875rem] border border-[#E4E4DC] bg-white py-[1.125rem] pl-5 pr-[1.125rem] has-[:checked]:border-2 has-[:checked]:border-[#2E5B2A] has-[:checked]:bg-[#E9F1E6]';
    $titik = 'mt-[1.4rem] flex h-[1.125rem] w-[1.125rem] shrink-0 items-center justify-center rounded-full border-[1.25px] border-[#C9CEC3] bg-white after:h-[0.5625rem] after:w-[0.5625rem] after:rounded-full after:bg-[#2E5B2A] after:opacity-0 peer-checked:border-2 peer-checked:border-[#2E5B2A] peer-checked:after:opacity-100';
    $ubin = 'ml-5 flex h-12 w-12 shrink-0 items-center justify-center rounded-[0.875rem] border border-[#E4E4DC] bg-white text-[#2E5B2A]';
@endphp

{{-- Breadcrumb --}}
<nav class="flex items-center text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">
    <a href="{{ $ke('buyer.beranda') }}" class="hover:underline">Beranda</a>
    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />
    <a href="{{ $ke('buyer.katalog.index') }}" class="hover:underline">Katalog Ternak</a>
    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />
    <a href="{{ $ke('buyer.katalog.show', [$r->tipe, $r->id]) }}" class="hover:underline">{{ \Illuminate\Support\Str::words($r->nama, 3, '') }}</a>
    <x-ikon nama="chevronr" class="mx-2 h-3 w-3 text-[#9AA093]" sw="2.2" />
    <span class="font-semibold text-[#1C2118]">Pemesanan</span>
</nav>
<h1 class="mt-3 text-[1.75rem] font-extrabold leading-[2.125rem] text-[#1C2118]">Pemesanan</h1>

@if (session('error') || $errors->any())
    <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-[0.8125rem] text-red-800">
        @if (session('error')) <p>{{ session('error') }}</p> @endif
        @if ($errors->any())
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        @endif
    </div>
@endif

<form id="form-pesan" method="POST" action="{{ route('buyer.pemesanan.store') }}"
      data-harga="{{ $r->harga }}" data-stok="{{ $r->stok }}" data-satuan="{{ $r->satuan }}"
      data-kota-penjual="{{ $r->kota }}" class="mt-6 grid grid-cols-[46.5rem_minmax(0,1fr)] items-start gap-8">
    @csrf
    <input type="hidden" name="tipe" value="{{ $r->tipe }}">
    <input type="hidden" name="item" value="{{ $r->id }}">
    <input type="hidden" name="jumlah" value="{{ old('jumlah', $jumlah) }}">

    {{-- ================= Kolom kiri ================= --}}
    <div class="space-y-5">

        {{-- Ringkasan pesanan --}}
        <section class="h-44 rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <h2 class="text-[0.9375rem] font-bold leading-[1.125rem] text-[#1C2118]">Ringkasan pesanan</h2>
            <div class="mt-[0.7rem] flex">
                <x-ilustrasi :r="$r" class="h-24 w-24 shrink-0 rounded-xl" ikon="h-10 w-10" :sw="1.3" />

                <div class="ml-5 min-w-0 flex-1 pt-1">
                    <p class="truncate text-[0.90625rem] font-bold leading-5 text-[#1C2118]">{{ $r->nama }}</p>
                    <p class="mt-1 text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">{{ $r->penjual }} · {{ $r->kota }}, {{ $r->provinsi }}</p>
                    <p class="mt-[0.47rem] text-sm font-bold leading-5 text-[#2E5B2A]">{{ $rp($r->harga) }} / {{ $r->satuan }}</p>
                    <p class="mt-0.5 text-[0.71875rem] leading-4 text-[#6B7265]">{{ $individual ? 'Ternak individual, 1 ekor' : 'Stok tersedia ' . $angka($r->stok) . ' ' . $r->satuan }}</p>
                </div>

                @unless ($individual)
                    <div class="flex w-[8.125rem] shrink-0 flex-col items-center pt-1.5">
                        <span class="text-[0.71875rem] font-medium leading-4 text-[#6B7265]">Jumlah ({{ $r->satuan }})</span>
                        <div class="mt-1.5 flex h-10 w-full items-center justify-between rounded-[0.625rem] border border-[#E4E4DC] bg-white px-2.5">
                            <button type="button" data-ubah-jumlah="-1" class="text-[#4B5245] disabled:opacity-40" aria-label="Kurangi">
                                <x-ikon nama="minus" class="h-4 w-4" sw="2.4" />
                            </button>
                            <span id="tampil-jumlah" class="text-[0.9375rem] font-bold text-[#1C2118]">{{ $angka(old('jumlah', $jumlah)) }}</span>
                            <button type="button" data-ubah-jumlah="1" class="text-[#2E5B2A] disabled:opacity-40" aria-label="Tambah">
                                <x-ikon nama="plus" class="h-4 w-4" sw="2.4" />
                            </button>
                        </div>
                    </div>
                @endunless
            </div>
        </section>

        {{-- Data pembeli --}}
        <section class="min-h-[17.875rem] rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <div class="flex items-baseline justify-between">
                <h2 class="text-[0.9375rem] font-bold leading-[1.125rem] text-[#1C2118]">Data pembeli</h2>
                <button type="button" id="ubah-data" class="text-[0.78125rem] font-semibold text-[#2E5B2A] hover:underline">Ubah data</button>
            </div>

            <div class="mt-[0.4rem] grid grid-cols-2 gap-x-4">
                <div>
                    <label for="nama_penerima" class="{{ $label }}">Nama lengkap</label>
                    <input id="nama_penerima" name="nama_penerima" value="{{ old('nama_penerima', $u->name) }}" readonly class="{{ $isian }}">
                </div>
                <div>
                    <label for="no_hp_penerima" class="{{ $label }}">Nomor HP / WhatsApp</label>
                    <input id="no_hp_penerima" name="no_hp_penerima" value="{{ old('no_hp_penerima', $u->no_hp) }}" readonly class="{{ $isian }}">
                </div>
            </div>
            <div class="mt-4">
                <label for="alamat_penerima" class="{{ $label }}">Alamat</label>
                <input id="alamat_penerima" name="alamat_penerima" value="{{ old('alamat_penerima', $u->alamat) }}" readonly class="{{ $isian }}">
            </div>
            <div class="mt-4 grid grid-cols-2 gap-x-4">
                <div>
                    <label for="kota_kabupaten" class="{{ $label }}">Kota/kabupaten</label>
                    <input id="kota_kabupaten" name="kota_kabupaten" value="{{ $kotaPembeli }}" readonly class="{{ $isian }}">
                </div>
                <div>
                    <label for="provinsi" class="{{ $label }}">Provinsi</label>
                    <input id="provinsi" name="provinsi" value="{{ old('provinsi', $u->provinsi) }}" readonly class="{{ $isian }}">
                </div>
            </div>
        </section>

        {{-- Metode pembayaran --}}
        <section class="rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <div class="flex items-baseline justify-between">
                <h2 class="text-[0.9375rem] font-bold leading-[1.125rem] text-[#1C2118]">Metode pembayaran</h2>
                <span class="text-xs text-[#6B7265]">Pilih satu</span>
            </div>

            <div class="mt-[0.65rem] space-y-3">
                {{-- Transfer bank --}}
                <label class="{{ $kartu }}">
                    <input type="radio" name="metode_bayar" value="transfer_bank" class="peer sr-only" @checked($metode === 'transfer_bank')>
                    <span class="{{ $titik }}"></span>
                    <span class="{{ $ubin }}">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-5 9 5"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8"/><path d="M3 20h18"/></svg>
                    </span>
                    <span class="ml-4 min-w-0 flex-1">
                        <span class="block text-[0.9375rem] font-bold leading-5 text-[#1C2118]">Transfer Bank Manual</span>
                        <span class="mt-0.5 block text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">Transfer ke rekening SITERNAK lalu unggah bukti. Diverifikasi admin maksimal 1×24 jam.</span>
                    </span>
                    <span class="absolute right-[1.125rem] top-[1.125rem] inline-flex h-[1.375rem] items-center rounded-full bg-[#EEEEE8] px-2 text-[0.6875rem] font-semibold text-[#4B5245]">Verifikasi admin</span>
                </label>

                {{-- QRIS --}}
                <label class="{{ $kartu }}">
                    <input type="radio" name="metode_bayar" value="qris" class="peer sr-only" @checked($metode === 'qris')>
                    <span class="{{ $titik }}"></span>
                    <span class="{{ $ubin }}">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v3M17 20h4"/></svg>
                    </span>
                    <span class="ml-4 min-w-0 flex-1">
                        <span class="block text-[0.9375rem] font-bold leading-5 text-[#1C2118]">QRIS</span>
                        <span class="mt-0.5 block text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">Bayar lewat m-banking atau e-wallet. Status otomatis lunas setelah pembayaran berhasil.</span>
                    </span>
                    <span class="absolute right-[1.125rem] top-[1.125rem] inline-flex h-[1.375rem] items-center rounded-full bg-[#EDE7FB] px-2 text-[0.6875rem] font-semibold text-[#6D28D9]">Otomatis</span>
                </label>

                {{-- COD (nonaktif otomatis jika beda kota) --}}
                <label id="kartu-cod" data-nonaktif="{{ $codBisa ? 'false' : 'true' }}"
                       class="{{ $kartu }} data-[nonaktif=true]:cursor-not-allowed data-[nonaktif=true]:border-[1.25px] data-[nonaktif=true]:bg-[#F6F6F2]">
                    <input id="radio-cod" type="radio" name="metode_bayar" value="cod" class="peer sr-only" @checked($metode === 'cod' && $codBisa) @disabled(! $codBisa)>
                    <span class="{{ $titik }} group-data-[nonaktif=true]:border-[#D5D8CF] group-data-[nonaktif=true]:bg-[#EEEEE8]"></span>
                    <span class="{{ $ubin }} group-data-[nonaktif=true]:border-0 group-data-[nonaktif=true]:bg-[#EEEEE8] group-data-[nonaktif=true]:text-[#9AA093]">
                        <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 12h.01M18 12h.01"/></svg>
                    </span>
                    <span class="ml-4 min-w-0 flex-1">
                        <span id="cod-ya" class="{{ $codBisa ? '' : 'hidden' }}">
                            <span class="block text-[0.9375rem] font-bold leading-5 text-[#1C2118]">COD (Bayar di tempat)</span>
                            <span class="mt-0.5 block text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">Bayar tunai saat serah terima langsung dengan peternak. Tersedia karena Anda dan peternak sama-sama di <span data-isi-kota-pembeli>{{ $kotaPembeli }}</span>.</span>
                        </span>
                        <span id="cod-tidak" class="{{ $codBisa ? 'hidden' : '' }}">
                            <span class="block text-[0.9375rem] font-bold leading-5 text-[#6B7265]">COD tidak tersedia</span>
                            <span class="mt-0.5 block text-[0.78125rem] leading-[1.125rem] text-[#6B7265]">Kota Anda (<span data-isi-kota-pembeli>{{ $kotaPembeli }}</span>) berbeda dengan peternak ({{ $r->kota }}). COD hanya untuk transaksi dalam satu kota/kabupaten.</span>
                        </span>
                    </span>
                    <span id="pil-cod" class="{{ $codBisa ? '' : 'hidden' }} absolute right-[1.125rem] top-[1.125rem] inline-flex h-[1.375rem] items-center rounded-full bg-[#FEF3C7] px-2 text-[0.6875rem] font-semibold text-[#B45309]">Satu kota/kabupaten</span>
                </label>
            </div>
        </section>
    </div>

    {{-- ================= Kolom kanan ================= --}}
    <div class="space-y-5">

        {{-- Rincian pembayaran --}}
        <section class="rounded-[0.875rem] border border-[#E4E4DC] bg-white px-5 pb-4 pt-5">
            <h2 class="text-[0.9375rem] font-bold leading-[1.125rem] text-[#1C2118]">Rincian pembayaran</h2>

            <dl class="mt-4 space-y-3 text-[0.8125rem] leading-[1.125rem]">
                <div class="flex justify-between"><dt class="text-[#6B7265]">{{ \Illuminate\Support\Str::words($r->nama, 4, '') }}</dt><dd id="rin-jumlah" class="font-medium text-[#1C2118]">× {{ $angka(old('jumlah', $jumlah)) }} {{ $r->satuan }}</dd></div>
                <div class="flex justify-between"><dt class="text-[#6B7265]">Harga satuan</dt><dd class="font-medium text-[#1C2118]">{{ $rp($r->harga) }}</dd></div>
                <div class="flex justify-between"><dt class="text-[#6B7265]">Subtotal</dt><dd id="rin-subtotal" class="font-medium text-[#1C2118]">{{ $rp($r->harga * old('jumlah', $jumlah)) }}</dd></div>
            </dl>

            <div class="mt-[1.65rem] border-t border-[#EDEDE6] pt-[0.8rem]">
                <div class="flex items-center justify-between">
                    <span class="text-[0.84375rem] font-bold text-[#1C2118]">Total pembayaran</span>
                    <span id="rin-total" class="text-[1.375rem] font-extrabold leading-7 text-[#2E5B2A]">{{ $rp($r->harga * old('jumlah', $jumlah)) }}</span>
                </div>
                <p class="mt-1.5 text-[0.71875rem] leading-4 text-[#6B7265]">Tanpa biaya kirim: tidak ada kurir atau ekspedisi.</p>
            </div>

            <button type="submit" class="mt-[0.7rem] h-12 w-full rounded-[0.625rem] bg-[#2E5B2A] text-[0.9375rem] font-semibold text-white hover:bg-[#1F421C]">Buat Pesanan</button>
        </section>

        {{-- Setelah pesanan dibuat: isi mengikuti metode yang dipilih --}}
        @php
            $langkah = [
                'cod' => ['Peternak mengonfirmasi pesanan Anda.', 'Sepakati waktu dan lokasi serah terima langsung dengan peternak.', 'Bayar tunai saat serah terima. Peternak menandai pesanan lunas.', 'Stok diperbarui dan pesanan selesai.'],
                'qris' => ['Peternak mengonfirmasi pesanan Anda.', 'Pindai kode QRIS dan bayar sesuai nominal dalam 15 menit.', 'Status berubah otomatis menjadi lunas setelah pembayaran diterima.', 'Stok diperbarui dan pesanan selesai.'],
                'transfer_bank' => ['Peternak mengonfirmasi pesanan Anda.', 'Transfer sesuai nominal ke rekening SITERNAK, lalu unggah bukti pembayaran.', 'Admin memverifikasi bukti maksimal 1×24 jam, lalu pesanan lunas.', 'Stok diperbarui dan pesanan selesai.'],
            ];
        @endphp
        <section class="min-h-[18.125rem] rounded-[0.875rem] border border-[#E4E4DC] bg-white p-5">
            <h2 class="text-[0.9375rem] font-bold leading-[1.125rem] text-[#1C2118]">Setelah pesanan dibuat</h2>

            @foreach ($langkah as $kunci => $daftar)
                <ol data-langkah-metode="{{ $kunci }}" class="mt-[0.9rem] {{ $metode === $kunci ? '' : 'hidden' }}">
                    @foreach ($daftar as $i => $teks)
                        <li class="flex gap-3">
                            <div class="flex flex-col items-center">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#E9F1E6] text-xs font-bold text-[#2E5B2A]">{{ $i + 1 }}</span>
                                @unless ($loop->last)
                                    <span class="mb-[4px] mt-[2px] w-0 flex-1 border-l-[1.5px] border-[#E4E4DC]"></span>
                                @endunless
                            </div>
                            <p class="flex min-h-6 items-center text-[0.78125rem] leading-[1.125rem] text-[#4B5245] {{ $loop->last ? '' : 'pb-[1.375rem]' }}">{{ $teks }}</p>
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </section>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var f = document.getElementById('form-pesan');
    var harga = parseFloat(f.dataset.harga), stok = parseFloat(f.dataset.stok), satuan = f.dataset.satuan;
    var jumlah = f.querySelector('[name=jumlah]');
    var kota = f.querySelector('[name=kota_kabupaten]');
    var kotaPenjual = f.dataset.kotaPenjual;
    var radioCod = document.getElementById('radio-cod');
    var kartuCod = document.getElementById('kartu-cod');

    var uang = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
    var kunci = function (v) { return (v || '').toLowerCase().trim().replace(/^(kota|kabupaten|kab\.?)\s+/, '').trim(); };

    function hitung() {
        var n = parseFloat(jumlah.value) || 1;
        var teks = n.toLocaleString('id-ID');
        var tampil = document.getElementById('tampil-jumlah');
        if (tampil) tampil.textContent = teks;
        document.getElementById('rin-jumlah').textContent = '× ' + teks + ' ' + satuan;
        document.getElementById('rin-subtotal').textContent = uang(harga * n);
        document.getElementById('rin-total').textContent = uang(harga * n);
        f.querySelectorAll('[data-ubah-jumlah]').forEach(function (b) {
            b.disabled = b.dataset.ubahJumlah === '-1' ? n <= 1 : n >= stok;
        });
    }

    function langkah() {
        var terpilih = f.querySelector('[name=metode_bayar]:checked');
        document.querySelectorAll('[data-langkah-metode]').forEach(function (ol) {
            ol.classList.toggle('hidden', !terpilih || ol.dataset.langkahMetode !== terpilih.value);
        });
    }

    function cekCod() {
        var boleh = kunci(kota.value) === kunci(kotaPenjual);
        kartuCod.dataset.nonaktif = boleh ? 'false' : 'true';
        radioCod.disabled = !boleh;
        document.getElementById('cod-ya').classList.toggle('hidden', !boleh);
        document.getElementById('cod-tidak').classList.toggle('hidden', boleh);
        document.getElementById('pil-cod').classList.toggle('hidden', !boleh);
        document.querySelectorAll('[data-isi-kota-pembeli]').forEach(function (e) { e.textContent = kota.value; });
        if (!boleh && radioCod.checked) f.querySelector('[name=metode_bayar][value=qris]').checked = true;
        langkah();
    }

    f.querySelectorAll('[data-ubah-jumlah]').forEach(function (b) {
        b.addEventListener('click', function () {
            var n = (parseFloat(jumlah.value) || 1) + parseInt(b.dataset.ubahJumlah, 10);
            jumlah.value = Math.min(stok, Math.max(1, n));
            hitung();
        });
    });
    f.querySelectorAll('[name=metode_bayar]').forEach(function (r) { r.addEventListener('change', langkah); });
    kota.addEventListener('input', cekCod);

    document.getElementById('ubah-data').addEventListener('click', function () {
        f.querySelectorAll('.data-pembeli').forEach(function (i) { i.readOnly = false; });
        f.querySelector('[name=nama_penerima]').focus();
        this.textContent = 'Sedang mengubah';
        this.disabled = true;
    });

    hitung();
    cekCod();
})();
</script>
@endpush