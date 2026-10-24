<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Toko Saya') – SITERNAK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $user = auth()->user();
    $toko = $user ? \App\Models\Store::where('user_id', $user->id)->where('status', 'active')->latest('id')->first() : null;
    $inisial = collect(explode(' ', $user->name ?? '?'))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

    // '#' = halaman belum dibuat. Ganti dengan route sungguhan saat fiturnya jadi.
    $menu = [
        'TOKO SAYA' => [
            ['Dasbor Toko', '#', false],
            ['Ternak Hidup', '#', false],
            ['Hasil Ternak', '#', false],
            ['Pesanan Masuk', '#', false],
            ['Pesan', '#', false],
        ],
        'KINERJA' => [
            ['Performa Toko', '#', false],
            ['Penilaian Toko', '#', false],
            ['Feedback', '#', false],
        ],
        'KEUANGAN' => [
            ['Penghasilan Saya', '#', false],
            ['Saldo Penjual', '#', false],
            ['Laporan Keuangan', '#', false],
        ],
        'PENGATURAN' => [
            ['Profil Toko', route('seller.toko.profil'), request()->routeIs('seller.toko.profil*')],
            ['Mode Libur', route('seller.toko.libur'), request()->routeIs('seller.toko.libur*')],
            ['Verifikasi Toko', '#', false],
        ],
    ];
@endphp
<body class="bg-[#f7f6f1] text-gray-800">
<div class="min-h-screen flex">

    <aside class="hidden md:flex w-56 shrink-0 flex-col bg-white border-r border-gray-200 p-4 sticky top-0 h-screen overflow-y-auto">
        <div class="flex items-center gap-2 font-bold text-lg mb-4 text-gray-900">
            <span class="w-8 h-8 rounded-lg bg-[#2d5a27] text-white flex items-center justify-center text-sm">S</span>
            SITERNAK
        </div>

        @foreach ($menu as $judul => $items)
            <p class="text-[10px] font-semibold tracking-wide text-gray-400 mt-4 mb-1 px-1">{{ $judul }}</p>
            @foreach ($items as [$label, $url, $aktif])
                <a href="{{ $url }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ $aktif ? 'bg-[#dfe9d8] text-[#234a1f] font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        @endforeach

        <p class="text-[10px] font-semibold tracking-wide text-gray-400 mt-4 mb-1 px-1">AKUN</p>
        <a href="{{ url('/') }}" class="block rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Kembali ke Pembeli</a>
        @if (Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full text-left rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100">Keluar</button>
            </form>
        @endif

        <div class="mt-auto pt-4">
            <div class="rounded-lg bg-[#eef3ea] p-3 text-xs text-gray-600">
                <b class="text-[#234a1f]">Toko Terverifikasi</b><br>
                {{ $toko?->city }}{{ $toko ? ', ' : '' }}{{ $toko?->province }}
            </div>
        </div>
    </aside>

    <div class="flex-1 min-w-0">
        <header class="bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-4">
            <input type="search" placeholder="Cari pesanan atau ternak…"
                   class="flex-1 max-w-xl rounded-lg bg-[#f1f0ea] border-0 px-4 py-2 text-sm">
            <div class="ml-auto flex items-center gap-3">
                <span class="w-8 h-8 rounded-full bg-[#dfe9d8] text-[#234a1f] text-xs font-semibold flex items-center justify-center">{{ $inisial }}</span>
                <div class="text-xs leading-tight">
                    <p class="font-semibold">{{ $user->name ?? '' }}</p>
                    <p class="text-gray-500">Penjual</p>
                </div>
            </div>
        </header>

        <main class="px-6 py-6 max-w-6xl">
            @if (session('success'))<div class="mb-4 rounded-lg bg-[#dfe9d8] text-[#234a1f] px-4 py-2 text-sm">{{ session('success') }}</div>@endif
            @if (session('error'))<div class="mb-4 rounded-lg bg-red-100 text-red-800 px-4 py-2 text-sm">{{ session('error') }}</div>@endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-2 text-sm">
                    <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>