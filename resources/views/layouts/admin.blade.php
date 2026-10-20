<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · SITERNAK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('head')
</head>
<body>
@php
    // Menu sidebar sesuai prototipe. Menu yang route-nya belum dibuat tampil dengan link "#".
    $nav = [
        'VERIFIKASI' => [
            ['Peternak', 'admin.verifikasi-toko.index', 'admin/verifikasi-toko*'],
            ['Pembayaran', 'admin.verifikasi-pembayaran.index', 'admin/verifikasi-pembayaran*'],
            ['Sertifikat Kontes', 'admin.verifikasi-sertifikat.index', 'admin/verifikasi-sertifikat*'],
        ],
        'DATA & LAPORAN' => [
            ['Data Induk', 'admin.data-induk.index', 'admin/data-induk*'],
            ['Peringkat Keuangan', 'admin.laporan.index', 'admin/laporan*'],
        ],
    ];
    $authUser = auth()->user();
    $authInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($authUser->name ?? 'A', 0, 2));
@endphp
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-logo">S</span>
            <span class="brand-name">SITERNAK</span>
            <span class="brand-tag">Admin</span>
        </div>

        @foreach ($nav as $section => $items)
            <div class="nav-title">{{ $section }}</div>
            @foreach ($items as [$label, $routeName, $pattern])
                <a href="{{ Route::has($routeName) ? route($routeName) : '#' }}"
                   class="nav-link {{ request()->is($pattern) ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        @endforeach

        <div class="nav-title">AKUN</div>
        @if (Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link">Keluar</button>
            </form>
        @endif
    </aside>

    <div class="main">
        <header class="topbar">
            <span class="chip-region">Wilayah: Seluruh Indonesia</span>
            <form class="search" method="GET" action="{{ route('admin.verifikasi-toko.index') }}">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari peternak atau pesanan...">
                @if (request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
            </form>
            <div class="user-chip">
                <span class="avatar">{{ $authInitials }}</span>
                <div>
                    <strong>{{ $authUser->name ?? 'Admin' }}</strong>
                    <small>Admin</small>
                </div>
            </div>
        </header>

        <main class="content">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error">
                    Keputusan belum tersimpan. Periksa lagi:
                    <ul>
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
