<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Toko Saya') – SITERNAK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .seller-layout {
            --seller-green: #2d5a27;
            --seller-cream: #f7f6f1;
            min-height: 100vh;
            background: var(--seller-cream);
        }
        .seller-sidebar {
            width: 232px;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #e8e8e2;
        }
        .seller-brand {
            min-height: 58px;
            border-bottom: 1px solid #eeeee9;
        }
        .seller-main { min-width: 0; }
        .seller-topbar {
            min-height: 58px;
            border-bottom: 1px solid #e8e8e2;
            background: rgba(255, 255, 255, 0.92);
        }
        .seller-menu-group + .seller-menu-group { margin-top: 1.1rem; }
        .seller-menu-link {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            min-height: 32px;
            border-radius: 0.45rem;
            padding: 0.35rem 0.55rem;
            color: #5c625b;
            font-size: 0.75rem;
            line-height: 1rem;
            text-decoration: none;
            transition: background-color 120ms ease, color 120ms ease;
        }
        .seller-menu-link:hover { background: #f4f6f1; color: var(--seller-green); }
        .seller-menu-link.is-active {
            background: #e8f0e5;
            color: var(--seller-green);
            font-weight: 600;
        }
        .seller-menu-icon { width: 15px; height: 15px; flex: none; }
        .seller-content { padding: 1.5rem; }
        .seller-search { max-width: 420px; }
        @media (max-width: 767px) {
            .seller-sidebar {
                width: 100%;
                min-height: auto;
                border-right: 0;
                border-bottom: 1px solid #e8e8e2;
            }
            .seller-brand { min-height: 50px; }
            .seller-nav {
                display: flex;
                gap: 1.25rem;
                overflow-x: auto;
                padding: 0.25rem 1rem 0.85rem;
            }
            .seller-menu-group {
                display: flex;
                flex: none;
                align-items: center;
                gap: 0.25rem;
                margin: 0 !important;
            }
            .seller-menu-heading { display: none; }
            .seller-menu-link { white-space: nowrap; }
            .seller-topbar { padding: 0.5rem 1rem; }
            .seller-content { padding: 1rem; }
        }
        @media (max-width: 480px) {
            .seller-search { max-width: none; }
            .seller-account-name { display: none; }
        }
    </style>
</head>
<body class="text-gray-800">
@php
    $sellerName = request()->user()?->name ?? 'Penjual';
    $menuGroups = [
        [
            'title' => 'TOKO SAYA',
            'items' => [
                ['label' => 'Dashboard Toko', 'href' => route('seller.dasbor'), 'route' => 'seller.dashboard', 'icon' => 'M3 10.5 12 3l9 7.5M5 9v11h14V9M9 20v-6h6v6'],
                ['label' => 'Ternak Hidup', 'href' => route('seller.ternak.index'), 'icon' => 'M4 7h16M4 12h16M4 17h16M7 4v16'],
                ['label' => 'Hasil Ternak', 'href' => '#', 'icon' => 'M12 3v18M5 8c0 3 3 4 7 4s7-1 7-4M5 16c0-3 3-4 7-4s7 1 7 4'],
                ['label' => 'Pesanan Masuk', 'href' => '#', 'icon' => 'M4 5h16v14H4zM8 9h8M8 13h5'],
                ['label' => 'Pesanan', 'href' => '#', 'icon' => 'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5'],
            ],
        ],
        [
            'title' => 'KINERJA',
            'items' => [
                ['label' => 'Performa Toko', 'href' => '#', 'icon' => 'M4 19V5M4 19h17M8 15l4-4 3 2 5-6'],
                ['label' => 'Penilaian Toko', 'href' => '#', 'icon' => 'm12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z'],
                ['label' => 'Feedback', 'href' => '#', 'icon' => 'M4 5h16v12H9l-5 4zM8 9h8M8 13h5'],
            ],
        ],
        [
            'title' => 'KEUANGAN',
            'items' => [
                ['label' => 'Penghasilan Saya', 'href' => '#', 'icon' => 'M12 2v20M17 6.5c0-1.7-2.2-3-5-3S7 4.8 7 6.5 9.2 9 12 9s5 1.3 5 3-2.2 3-5 3-5 1.3-5 3 2.2 3 5 3 5-1.3 5-3'],
                ['label' => 'Saldo Penjual', 'href' => '#', 'icon' => 'M3 7h18v13H3zM3 7l2-4h14l2 4M16 13h5'],
                ['label' => 'Laporan Keuangan', 'href' => '#', 'icon' => 'M5 3h14v18H5zM8 8h8M8 12h8M8 16h5'],
            ],
        ],
        [
            'title' => 'PENGATURAN',
            'items' => [
                ['label' => 'Profil Toko', 'href' => route('seller.toko.profil'), 'route' => 'seller.toko.profil', 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM4 21a8 8 0 0 1 16 0'],
                ['label' => 'Mode Libur', 'href' => route('seller.toko.libur'), 'route' => 'seller.toko.libur', 'icon' => 'M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6 5.6 18.4'],
                ['label' => 'Verifikasi Toko', 'href' => '#', 'icon' => 'M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6zM9 12l2 2 4-4'],
            ],
        ],
        [
            'title' => 'AKUN',
            'items' => [
                ['label' => 'Kembali ke Pembeli', 'href' => '#', 'icon' => 'M10 5 3 12l7 7M3 12h18'],
                ['label' => 'Keluar', 'href' => route('logout'), 'icon' => 'M10 5H5v14h5M14 8l4 4-4 4M8 12h10', 'logout' => true],
            ],
        ],
    ];
@endphp
<div class="seller-layout md:flex">
    <aside class="seller-sidebar md:shrink-0">
        <div class="seller-brand flex items-center gap-2 px-4">
            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-[#2d5a27] text-white">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4">
                    <path d="M19.5 4.5C11 4.5 6 8 6 14.5c0 3.2 2.1 5 5.1 5 6.3 0 8.4-7.5 8.4-15Z" stroke="currentColor" stroke-width="1.8"/>
                    <path d="M4 20c2.8-4.8 6.2-7.6 11-10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="text-sm font-extrabold tracking-tight text-[#183a1e]">SITERNAK</span>
        </div>

        <nav class="seller-nav px-3 py-4" aria-label="Navigasi penjual">
            @foreach ($menuGroups as $group)
                <section class="seller-menu-group">
                    <h2 class="seller-menu-heading mb-1 px-2 text-[9px] font-semibold tracking-[0.12em] text-gray-400">
                        {{ $group['title'] }}
                    </h2>
                    @foreach ($group['items'] as $item)
                        @php($active = isset($item['route']) && request()->routeIs($item['route']))
                        @if (! empty($item['logout']))
                            <form method="POST" action="{{ $item['href'] }}">
                                @csrf
                                <button type="submit" class="seller-menu-link w-full text-left">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="seller-menu-icon">
                                        <path d="{{ $item['icon'] }}" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    {{ $item['label'] }}
                                </button>
                            </form>
                        @else
                            <a href="{{ $item['href'] }}"
                               @if ($active) aria-current="page" @endif
                               class="seller-menu-link {{ $active ? 'is-active' : '' }}">
                                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="seller-menu-icon">
                                    <path d="{{ $item['icon'] }}" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                {{ $item['label'] }}
                            </a>
                        @endif
                    @endforeach
                </section>
            @endforeach
        </nav>
    </aside>

    <div class="seller-main flex min-h-screen flex-1 flex-col">
        <header class="seller-topbar sticky top-0 z-10 flex items-center justify-between gap-4 px-6">
            <label class="seller-search relative flex w-full items-center">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="absolute left-3 h-4 w-4 text-gray-400">
                    <circle cx="10.8" cy="10.8" r="6.3" stroke="currentColor" stroke-width="1.6"/>
                    <path d="m15.5 15.5 4.2 4.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                <input type="search" aria-label="Cari pesanan atau ternak" placeholder="Cari pesanan atau ternak..."
                       class="w-full rounded-lg border border-gray-200 bg-[#f7f7f4] py-2 pl-9 pr-3 text-xs outline-none transition focus:border-[#2d5a27] focus:ring-2 focus:ring-[#2d5a27]/10">
            </label>

            <div class="flex shrink-0 items-center gap-4">
                <button type="button" aria-label="Notifikasi" class="relative rounded-full p-1.5 text-gray-600 hover:bg-gray-100">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-5 w-5">
                        <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="absolute right-1 top-1 h-1.5 w-1.5 rounded-full bg-red-500"></span>
                </button>
                <span class="h-6 border-l border-gray-200"></span>
                <div class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#dfe9d8] text-xs font-semibold text-[#2d5a27]">
                        {{ mb_strtoupper(mb_substr($sellerName, 0, 1)) }}
                    </span>
                    <span class="seller-account-name min-w-0">
                        <span class="block max-w-36 truncate text-xs font-semibold text-gray-900">{{ $sellerName }}</span>
                        <span class="block text-[10px] text-gray-500">Penjual</span>
                    </span>
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="seller-account-name h-3 w-3 text-gray-500">
                        <path d="m7 10 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </header>

        <main class="seller-content flex-1">
            @if (session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
