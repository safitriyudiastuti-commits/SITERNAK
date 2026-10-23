<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SITERNAK')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Kalau layout lamamu memuat Tailwind dengan cara lain, samakan baris ini dengan punyamu. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F6F6F2] text-[#1C2118] antialiased" style="font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;">
<div class="grid min-h-screen sm:grid-cols-[38fr_62fr]">

    {{-- ===== Panel kiri ===== --}}
    <aside class="relative hidden overflow-hidden bg-gradient-to-br from-[#1B3F19] to-[#2E5B2A] text-white sm:flex sm:flex-col">
        <div class="relative z-10 px-12 pt-16">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/>
                        <path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>
                    </svg>
                </span>
                <span class="text-lg font-bold tracking-wide">SITERNAK</span>
            </div>

            <h2 class="mt-24 text-[clamp(1.75rem,2.9vw,2.5rem)] font-bold leading-[1.15]">
                Bergabung di pasar<br>ternak nasional
            </h2>
            <p class="mt-6 max-w-sm text-[15px] leading-relaxed text-white/80">
                Satu akun untuk jual beli ternak hidup dan hasil ternak di seluruh Indonesia.
            </p>

            <ul class="mt-10 space-y-4 text-[15px] text-white/90">
                <li class="flex items-center gap-3">
                    <span class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-white/20">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    Satu akun untuk belanja &amp; buka toko sendiri
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-white/20">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    Transfer bank, QRIS, atau COD dalam satu kota
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-[22px] w-[22px] shrink-0 items-center justify-center rounded-full bg-white/20">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="h-3 w-3"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    Pantau status pesanan sampai selesai
                </li>
            </ul>
        </div>

        {{-- Ilustrasi bukit dan rumah --}}
        <svg viewBox="0 0 400 140" class="pointer-events-none absolute bottom-0 left-0 w-full" aria-hidden="true">
            <ellipse cx="398" cy="66" rx="48" ry="9" fill="#3A7137"/>
            <path d="M0 96 C60 70 140 78 210 88 C290 100 350 78 400 72 L400 140 L0 140Z" fill="#3A7137"/>
            <path d="M0 120 C80 102 170 110 260 114 C320 117 370 107 400 102 L400 140 L0 140Z" fill="#2F6A2C"/>
            <polygon points="238,64 277,50 316,64" fill="#1A3A18"/>
            <rect x="246" y="64" width="62" height="52" fill="#1A3A18"/>
        </svg>
    </aside>

    {{-- ===== Panel kanan (form) ===== --}}
    <main class="flex items-center justify-center px-6 py-10">
        <div class="w-full @yield('lebar', 'max-w-[28rem]')">
            @yield('content')
        </div>
    </main>
</div>

<script>
    // Tombol mata: tampilkan/sembunyikan kata sandi
    document.querySelectorAll('[data-toggle-password]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var input = document.getElementById(tombol.dataset.togglePassword);
            var tampil = input.type === 'password';
            input.type = tampil ? 'text' : 'password';
            tombol.querySelector('[data-eye]').classList.toggle('hidden', tampil);
            tombol.querySelector('[data-eye-off]').classList.toggle('hidden', !tampil);
            tombol.setAttribute('aria-label', tampil ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        });
    });
</script>
</body>
</html>
