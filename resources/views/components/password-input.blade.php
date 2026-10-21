@props(['id', 'name', 'autocomplete' => 'off', 'required' => true])

{{-- Kolom kata sandi dengan tombol mata (tampilkan/sembunyikan). Skripnya ada di layouts/auth.blade.php. --}}
<div class="relative">
    <input id="{{ $id }}" name="{{ $name }}" type="password" autocomplete="{{ $autocomplete }}" @required($required)
           {{ $attributes->merge(['class' => 'h-11 w-full rounded-lg border border-[#E4E4DC] bg-white pl-3.5 pr-11 text-sm text-[#1C2118] placeholder:text-[#9AA093] outline-none focus:border-[#2E5B2A] focus:ring-2 focus:ring-[#D5E4CF]']) }}>

    <button type="button" data-toggle-password="{{ $id }}" aria-label="Tampilkan kata sandi"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-[#8A9082] hover:text-[#2E5B2A]">
        <svg data-eye viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
        <svg data-eye-off viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hidden h-5 w-5">
            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
            <path d="m2 2 20 20"/>
        </svg>
    </button>
</div>
