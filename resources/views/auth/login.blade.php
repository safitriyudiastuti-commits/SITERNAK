@extends('layouts.buyer')

@section('title', 'Masuk')

@section('content')
@php
    $input = 'w-full rounded-lg border border-[#E4E4DC] px-3 py-2 text-sm focus:border-[#2E5B2A] focus:outline-none';
@endphp
<div class="mx-auto max-w-md">
    <h1 class="text-3xl font-extrabold">Masuk</h1>
    <p class="mt-1 text-sm text-[#6B7265]">Gunakan email atau no HP yang Anda daftarkan.</p>

    @if (session('success'))
        <div class="mt-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4 rounded-2xl border border-[#E4E4DC] bg-white p-6">
        @csrf

        <div>
            <label for="login" class="mb-1 block text-sm font-semibold">Email atau no HP</label>
            <input id="login" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" class="{{ $input }}">
        </div>

        <div>
            <label for="password" class="mb-1 block text-sm font-semibold">Kata sandi</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="{{ $input }}">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="remember" class="rounded border-[#E4E4DC] text-[#2E5B2A]"> Ingat saya
        </label>

        <button class="w-full rounded-lg bg-[#2E5B2A] py-3 font-semibold text-white hover:bg-[#1F421C]">Masuk</button>
        <p class="text-center text-sm text-[#6B7265]">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-[#2E5B2A] hover:underline">Daftar</a></p>
    </form>
</div>
@endsection
