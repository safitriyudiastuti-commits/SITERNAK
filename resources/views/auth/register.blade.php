@extends('layouts.auth')

@section('title', 'Daftar · SITERNAK')
@section('lebar', 'max-w-xl')

@section('content')
@php
    $input = 'h-11 w-full rounded-lg border border-[#E4E4DC] bg-white px-3.5 text-sm text-[#1C2118] placeholder:text-[#9AA093] outline-none focus:border-[#2E5B2A] focus:ring-2 focus:ring-[#D5E4CF]';
    $label = 'mb-1.5 block text-[13px] font-semibold text-[#1C2118]';
    $bintang = '<span class="ml-1 text-[#D92D20]">*</span>';
@endphp

    <h1 class="text-[28px] font-bold leading-tight text-[#1C2118]">Buat akun SITERNAK</h1>
    <p class="mt-1 text-[15px] text-[#8A9082]">Satu akun untuk belanja dan nanti membuka toko.</p>

    @if ($errors->any())
        <div class="mt-5 rounded-lg border border-[#FDA29B] bg-[#FEF3F2] px-4 py-3 text-sm text-[#B42318]">
            <p class="font-semibold">Pendaftaran belum berhasil. Periksa isian berikut:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-4">
        @csrf

        <div>
            <label for="name" class="{{ $label }}">Nama lengkap {!! $bintang !!}</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="{{ $input }}">
            @error('name') <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="{{ $label }}">Email {!! $bintang !!}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="{{ $input }}">
                @error('email') <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="no_hp" class="{{ $label }}">No HP / WhatsApp {!! $bintang !!}</label>
                <input id="no_hp" name="no_hp" type="text" inputmode="tel" value="{{ old('no_hp') }}" placeholder="081234567890" required class="{{ $input }}">
                @error('no_hp') <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="{{ $label }}">Kata sandi {!! $bintang !!}</label>
                <x-password-input id="password" name="password" autocomplete="new-password" />
                <p class="mt-1.5 text-xs text-[#9AA093]">Minimal 8 karakter.</p>
                @error('password') <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="{{ $label }}">Ulangi kata sandi {!! $bintang !!}</label>
                <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
            </div>
        </div>

        <div>
            <label for="alamat" class="{{ $label }}">Alamat {!! $bintang !!}</label>
            <input id="alamat" name="alamat" type="text" value="{{ old('alamat') }}" required class="{{ $input }}">
            @error('alamat') <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="kota_kabupaten" class="{{ $label }}">Kota/kabupaten {!! $bintang !!}</label>
                <input id="kota_kabupaten" name="kota_kabupaten" type="text" value="{{ old('kota_kabupaten') }}" required class="{{ $input }}">
                @error('kota_kabupaten') <p class="mt-1.5 text-sm text-[#B42318]">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="provinsi" class="{{ $label }}">Provinsi <span class="font-normal text-[#9AA093]">(opsional)</span></label>
                <input id="provinsi" name="provinsi" type="text" value="{{ old('provinsi') }}" class="{{ $input }}">
            </div>
        </div>

        <button type="submit" class="mt-2 h-12 w-full rounded-lg bg-[#2E5B2A] text-sm font-semibold text-white transition hover:bg-[#1F421C]">Daftar</button>

        <p class="text-center">
            <a href="{{ route('login') }}" class="text-[13px] font-semibold text-[#2E5B2A] hover:underline">Sudah punya akun? Masuk</a>
        </p>
    </form>
@endsection
