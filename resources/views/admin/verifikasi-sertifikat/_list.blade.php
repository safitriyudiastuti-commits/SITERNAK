<div class="list">
    @forelse ($certificates as $item)
        @php $isSelected = isset($certificate) && $certificate->is($item); @endphp
        <a href="{{ route('admin.verifikasi-sertifikat.show', ['certificate' => $item, 'q' => $q ?: null]) }}"
           class="list-item {{ $isSelected ? 'is-active' : '' }}">
            <span class="avatar">{{ $item->initials }}</span>
            <span class="list-main">
                <strong>{{ $item->animal_label }}</strong>
                <small>{{ $item->store_name }} · {{ $item->city }}</small>
            </span>
            <span class="list-side">
                <span class="chip">{{ $item->rank_label }}</span>
            </span>
        </a>
    @empty
        <div class="empty">
            @if ($q !== '')
                Tidak ada hasil untuk "{{ $q }}".
            @else
                Belum ada sertifikat di tab ini.
            @endif
        </div>
    @endforelse
</div>

{{ $certificates->links() }}
