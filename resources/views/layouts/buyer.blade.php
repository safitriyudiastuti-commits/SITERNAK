<!DOCTYPE html>
{{-- Ukuran mengikuti prototype (kanvas 1440 px): 1rem = 16 px pada layar 1440, dan membesar/mengecil mengikuti lebar layar. --}}
<html lang="id" style="font-size: clamp(13px, calc(100vw / 90), 24px);">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SITERNAK') · SITERNAK</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F6F6F2] text-[#1C2118] antialiased" style="font-family: 'Inter', Arial, sans-serif;">
@php
    // Link aman: kalau route-nya belum ada (fitur belum dipasang), jadi '#', bukan error.
    $ke = fn (string $nama, array $param = []) => Route::has($nama) ? route($nama, $param) : '#';

    $user = auth()->user();
    $inisial = $user
        ? collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('')
        : '';

    $adaNotif = false;
    try {
        $adaNotif = $user && \Illuminate\Support\Facades\Schema::hasTable('notifications') && $user->unreadNotifications()->exists();
    } catch (\Throwable $e) {
        // notifikasi tidak boleh merusak halaman
    }

    $jumlahKeranjang = 0;
    try {
        if ($user && \Illuminate\Support\Facades\Schema::hasTable('cart_items')) {
            $jumlahKeranjang = \Illuminate\Support\Facades\DB::table('cart_items')->where('user_id', $user->id)->count();
        }
    } catch (\Throwable $e) {
        // keranjang tidak boleh merusak halaman
    }

    // Route "Toko Saya" milik tim peternak: sesuaikan daftar ini dengan php artisan route:list
    $tokoRoute = collect(['peternak.dashboard', 'seller.dashboard', 'seller.toko.pengajuan', 'peternak.buka-toko'])
        ->first(fn ($n) => Route::has($n));

   $menuUtama = [
    ['Beranda', 'home', $ke('buyer.beranda'), request()->routeIs('buyer.beranda', 'buyer.cara-memesan')],
    ['Katalog Ternak', 'grid', $ke('buyer.katalog.index'), request()->routeIs('buyer.katalog.*', 'buyer.pemesanan.*')],
    ['Pesanan Saya', 'box', $ke('buyer.pesanan.index'), request()->routeIs('buyer.pesanan.*', 'buyer.pembayaran.*', 'buyer.ulasan.*')],
    ['Pesan', 'chat', $ke('buyer.pesan.index'), request()->routeIs('buyer.pesan.*')],
];

    $itemAktif = 'bg-[#E9F1E6] font-semibold text-[#2E5B2A]';
    $itemBiasa = 'font-medium text-[#4B5245] hover:bg-[#F1F3EE]';
    $labelMenu = 'px-6 text-[0.65625rem] font-bold uppercase leading-4 tracking-[0.08em] text-[#9AA093]';
    $itemKelas = 'flex h-10 w-full items-center gap-1.5 rounded-[0.625rem] pl-4 text-left text-[0.84375rem]';
@endphp

{{-- ===== Bar atas (tinggi 64) ===== --}}
<header class="sticky top-0 z-30 flex h-16 items-center border-b border-[#E4E4DC] bg-white px-6">
    <a href="{{ $ke('buyer.beranda') }}" class="flex w-[14.25rem] shrink-0 items-center gap-[0.625rem]">
        <span class="flex h-9 w-9 items-center justify-center rounded-[0.625rem] bg-[#2E5B2A] text-white"><x-ikon nama="leaf" class="h-[1.375rem] w-[1.375rem]" sw="1.8" /></span>
        <span class="text-[1.0625rem] font-extrabold text-[#1F421C]">SITERNAK</span>
    </a>

    <form method="GET" action="{{ $ke('buyer.katalog.index') }}" class="relative w-[35rem]">
        <x-ikon nama="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#3F372E]" />
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kebutuhan ternak Anda..."
               class="h-10 w-full rounded-[0.625rem] bg-[#BFB0A1] pl-[2.875rem] pr-4 text-[0.84375rem] text-[#3F372E] outline-none placeholder:text-[#3F372E] focus:ring-2 focus:ring-[#2E5B2A]/40">
    </form>

    <div class="ml-auto flex items-center">
        @auth
            <a href="{{ $ke('buyer.keranjang.index') }}" class="relative mr-5 text-[#4B5245]" aria-label="Keranjang">
                <x-ikon nama="cart" class="h-5 w-5" />
                @if ($jumlahKeranjang > 0)
                    <span class="absolute -right-2 -top-2 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[#2E5B2A] px-1 text-[0.625rem] font-bold text-white">{{ $jumlahKeranjang }}</span>
                @endif
            </a>

            <button type="button" class="relative text-[#4B5245]" aria-label="Notifikasi">
                <x-ikon nama="bell" class="h-5 w-5" />
                @if ($adaNotif)<span class="absolute -top-1 left-3 h-2 w-2 rounded-full bg-[#B42318]"></span>@endif
            </button>
            <span class="ml-5 h-7 w-px bg-[#E4E4DC]"></span>

            <details class="relative ml-4" id="menu-pengguna">
                <summary class="flex cursor-pointer list-none items-center [&::-webkit-details-marker]:hidden">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#D5E4CF] text-[0.76rem] font-bold text-[#2E5B2A]">{{ $inisial }}</span>
                    <span class="ml-[0.625rem] leading-tight">
                        <span class="block text-[0.8125rem] font-semibold text-[#1C2118]">{{ $user->name }}</span>
                        <span class="mt-0.5 block text-[0.71875rem] text-[#6B7265]">{{ ucfirst($user->role ?? 'pembeli') }}</span>
                    </span>
                    <x-ikon nama="chevron" class="ml-5 h-4 w-4 text-[#6B7265]" />
                </summary>
                <div class="absolute right-0 mt-3 w-48 rounded-xl border border-[#E4E4DC] bg-white p-1.5 text-sm shadow-lg">
                    <a href="{{ $ke('profil.show') }}" class="block rounded-lg px-3 py-2 hover:bg-[#F1F3EE]">Profil Saya</a>
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="block w-full rounded-lg px-3 py-2 text-left hover:bg-[#F1F3EE]">Keluar</button>
                        </form>
                    @endif
                </div>
            </details>
        @else
            <a href="{{ $ke('login') }}" class="rounded-lg border border-[#2E5B2A] px-4 py-2 text-sm font-semibold text-[#2E5B2A] hover:bg-[#E9F1E6]">Masuk</a>
            <a href="{{ $ke('register') }}" class="ml-3 rounded-lg bg-[#2E5B2A] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1F421C]">Daftar</a>
        @endauth
    </div>
</header>

<div class="flex">
    {{-- ===== Sidebar (lebar 220) ===== --}}
    <aside class="sticky top-16 hidden h-[calc(100vh-4rem)] w-[13.75rem] shrink-0 flex-col overflow-y-auto border-r border-[#E4E4DC] bg-white pt-6 md:flex">
        <p class="{{ $labelMenu }}">Menu Utama</p>
        <nav class="mx-3 mt-2 space-y-1">
            @foreach ($menuUtama as [$label, $ikon, $href, $sedangAktif])
                <a href="{{ $href }}" class="{{ $itemKelas }} {{ $sedangAktif ? $itemAktif : $itemBiasa }}">
                    <x-ikon :nama="$ikon" class="h-[1.125rem] w-[1.125rem] shrink-0 {{ $sedangAktif ? '' : 'text-[#6B7265]' }}" />
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        @auth
            <p class="mt-5 {{ $labelMenu }}">Profil</p>
            <nav class="mx-3 mt-2 space-y-1">
                @php $profilAktif = request()->routeIs('profil.*'); @endphp
                <a href="{{ $ke('profil.show') }}" class="{{ $itemKelas }} {{ $profilAktif ? $itemAktif : $itemBiasa }}">
                    <x-ikon nama="user" class="h-[1.125rem] w-[1.125rem] shrink-0 {{ $profilAktif ? '' : 'text-[#6B7265]' }}" /> Profil Saya
                </a>
                <a href="{{ $tokoRoute ? route($tokoRoute) : '#' }}" class="{{ $itemKelas }} {{ $itemBiasa }}">
                    <x-ikon nama="store" class="h-[1.125rem] w-[1.125rem] shrink-0 text-[#6B7265]" /> Toko Saya
                </a>
                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">@csrf
                        <button class="{{ $itemKelas }} {{ $itemBiasa }}">
                            <x-ikon nama="logout" class="h-[1.125rem] w-[1.125rem] shrink-0 text-[#6B7265]" /> Keluar
                        </button>
                    </form>
                @endif
            </nav>
        @endauth

        <div class="mx-4 mb-8 mt-auto flex h-16 flex-col justify-center rounded-xl border border-[#E4E4DC] bg-[#F6F6F2] px-3.5">
            <p class="text-[0.71875rem] leading-[1.125rem] text-[#6B7265]">Butuh bantuan?</p>
            <a href="#" class="text-[0.78125rem] font-bold leading-5 text-[#1C2118]">Hubungi Pusat Bantuan</a>
        </div>
    </aside>

    {{-- ===== Konten ===== --}}
    <div class="flex min-h-[calc(100vh-4rem)] min-w-0 flex-1 flex-col px-8 pt-6">
        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="mt-7 flex items-center justify-between border-t border-[#E4E4DC] pb-6 pt-5 text-xs text-[#6B7265]">
            <span>© {{ date('Y') }} SITERNAK – Sistem Informasi Jual Beli Ternak</span>
            <span class="flex gap-6 font-medium"><a href="#" class="hover:text-[#2E5B2A]">Bantuan</a><a href="#" class="hover:text-[#2E5B2A]">Kebijakan Privasi</a><a href="#" class="hover:text-[#2E5B2A]">Syarat &amp; Ketentuan</a></span>
        </footer>
    </div>
</div>

<script>
    // Menu pengguna menutup saat klik di luar
    document.addEventListener('click', function (e) {
        var menu = document.getElementById('menu-pengguna');
        if (menu && !menu.contains(e.target)) menu.removeAttribute('open');
    });
</script>
@stack('scripts')
</body>
</html>