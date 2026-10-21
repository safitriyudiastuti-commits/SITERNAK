@extends('layouts.auth')

@section('title', 'Masuk · SITERNAK')

@section('content')
@php
    $input = 'h-11 w-full rounded-lg border border-[#E4E4DC] bg-white px-3.5 text-sm text-[#1C2118] placeholder:text-[#9AA093] outline-none focus:border-[#2E5B2A] focus:ring-2 focus:ring-[#D5E4CF]';
@endphp

    <h1 class="text-[28px] font-bold leading-tight text-[#1C2118]">Masuk ke akun Anda</h1>
    <p class="mt-1 text-[15px] text-[#8A9082]">Selamat datang kembali di SITERNAK.</p>

    @if (session('success'))
        <div class="mt-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-8">
        @csrf

        <div class="space-y-4">
            <div>
                <label for="login" class="mb-1.5 block text-[13px] font-semibold text-[#1C2118]">
                    Email atau nomor HP <span class="ml-1 text-[#D92D20]">*</span>
                </label>
                <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus
                       placeholder="budi.santoso@email.com" class="{{ $input }}">
                @error('login')
                    <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="block text-[13px] font-semibold text-[#1C2118]">
                        Kata sandi <span class="ml-1 text-[#D92D20]">*</span>
                    </label>
                    <a href="#" class="text-[13px] font-semibold text-[#2E5B2A] hover:underline">Lupa sandi?</a>
                </div>
                <x-password-input id="password" name="password" autocomplete="current-password" />
                @error('password')
                    <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <label class="mt-2.5 flex items-center gap-2 text-[13px] text-[#1C2118]">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded accent-[#2E5B2A]">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" class="mt-6 h-12 w-full rounded-lg bg-[#2E5B2A] text-sm font-semibold text-white transition hover:bg-[#1F421C]">
            Masuk
        </button>

        <div class="mt-5 flex items-center gap-3 text-[11px] text-[#A8AEA0]">
            <span class="h-px flex-1 bg-[#E4E4DC]"></span> atau <span class="h-px flex-1 bg-[#E4E4DC]"></span>
        </div>

        <p class="mt-5 text-center">
            <a href="{{ route('register') }}" class="text-[13px] font-semibold text-[#2E5B2A] hover:underline">Belum punya akun? Daftar</a>
        </p>
    </form>
@endsection
