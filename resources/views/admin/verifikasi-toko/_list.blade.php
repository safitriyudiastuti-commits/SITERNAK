<div class="list">
    @forelse ($stores as $item)
        @php $isSelected = isset($store) && $store->is($item); @endphp
        <a href="{{ route('admin.verifikasi-toko.show', ['store' => $item, 'q' => $q ?: null]) }}"
           class="list-item {{ $isSelected ? 'is-active' : '' }}">
            <span class="avatar">{{ $item->owner_initials }}</span>
            <span class="list-main">
                <strong>{{ $item->owner?->name ?? '-' }}</strong>
                <small>{{ $item->city }}, {{ $item->province }}</small>
            </span>
            <span class="list-side">
                @if ($item->status === \App\Enums\StoreStatus::PendingVerification)
                    <small class="{{ $item->hours_left < 12 ? 'text-danger' : 'text-muted' }}">Sisa {{ $item->hours_left }} jam</small>
                @elseif ($item->status === \App\Enums\StoreStatus::Active)
                    <small class="text-muted">Disetujui {{ $item->verified_short }}</small>
                @else
                    <small class="text-muted">Ditolak {{ $item->verified_short }}</small>
                @endif
                <span class="chip">{{ $item->category_badge }}</span>
            </span>
        </a>
    @empty
        <div class="empty">
            @if ($q !== '')
                Tidak ada hasil untuk "{{ $q }}".
            @else
                Belum ada pengajuan di tab ini.
            @endif
        </div>
    @endforelse
</div>

{{ $stores->links() }}
