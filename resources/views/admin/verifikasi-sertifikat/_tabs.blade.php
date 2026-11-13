@php
    $tabLabels = ['menunggu' => 'Menunggu', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak'];
@endphp
<nav class="tabs">
    @foreach ($tabLabels as $key => $label)
        <a href="{{ route('admin.verifikasi-sertifikat.index', ['status' => $key, 'q' => $q ?: null]) }}"
           class="tab {{ $tab === $key ? 'is-active' : '' }}">
            {{ $label }} <span class="tab-count">{{ $counts[$key] }}</span>
        </a>
    @endforeach
</nav>
