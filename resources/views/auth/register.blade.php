@extends('layouts.buyer')

@section('title', 'Daftar')

@section('content')
@php
    $input = 'w-full rounded-lg border border-[#E4E4DC] px-3 py-2 text-sm focus:border-[#2E5B2A] focus:outline-none';
@endphp
<div class="mx-auto max-w-lg">
    <h1 class="text-3xl font-extrabold">Buat akun SITERNAK</h1>
    <p class="mt-1 text-sm text-[#6B7265]">Satu akun untuk belanja, dan nanti juga untuk membuka toko.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            Periksa kembali isian Anda:
            <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="mt-5 space-y-4 rounded-2xl border border-[#E4E4DC] bg-white p-6">
        @csrf

        <div>
            <label for="name" class="mb-1 block text-sm font-semibold">Nama lengkap</label>
            <input id="name" name="name" value="{{ old('name') }}" required autofocus class="{{ $input }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-semibold">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="{{ $input }}">
            </div>
            <div>
                <label for="no_hp" class="mb-1 block text-sm font-semibold">No HP / WhatsApp</label>
                <input id="no_hp" name="no_hp" value="{{ old('no_hp') }}" placeholder="081234567890" required class="{{ $input }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="password" class="mb-1 block text-sm font-semibold">Kata sandi</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" class="{{ $input }}">
                <p class="mt-1 text-xs text-[#9AA093]">Minimal 8 karakter.</p>
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-semibold">Ulangi kata sandi</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="{{ $input }}">
            </div>
        </div>

        <div>
            <label for="alamat" class="mb-1 block text-sm font-semibold">Alamat</label>
            <input id="alamat" name="alamat" value="{{ old('alamat') }}" required class="{{ $input }}">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="kota_kabupaten" class="mb-1 block text-sm font-semibold">Kota/kabupaten</label>
                <input id="kota_kabupaten" name="kota_kabupaten" value="{{ old('kota_kabupaten') }}" required class="{{ $input }}">
            </div>
            <div>
                <label for="provinsi" class="mb-1 block text-sm font-semibold">Provinsi <span class="font-normal text-[#9AA093]">(opsional)</span></label>
                <input id="provinsi" name="provinsi" value="{{ old('provinsi') }}" class="{{ $input }}">
            </div>
        </div>

        <button class="w-full rounded-lg bg-[#2E5B2A] py-3 font-semibold text-white hover:bg-[#1F421C]">Daftar</button>
        <p class="text-center text-sm text-[#6B7265]">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-[#2E5B2A] hover:underline">Masuk</a></p>
    </form>
</div>
@endsection
