<div class="list">
    @forelse ($payments as $item)
        @php $isSelected = isset($payment) && $payment->is($item); @endphp
        <a href="{{ route('admin.verifikasi-pembayaran.show', ['payment' => $item, 'q' => $q ?: null]) }}"
           class="list-item {{ $isSelected ? 'is-active' : '' }}">
            <span class="avatar">{{ $item->initials }}</span>
            <span class="list-main">
                <strong>{{ $item->order_number }}</strong>
                <small>{{ $item->buyer_name }}</small>
            </span>
            <span class="list-side">
                @if ($item->isPending())
                    <small class="{{ $item->hours_left < 12 ? 'text-danger' : 'text-muted' }}">Sisa {{ $item->hours_left }} jam</small>
                @elseif ($item->status === \App\Models\Payment::DISETUJUI)
                    <small class="text-muted">Lunas {{ $item->decided_short }}</small>
                @else
                    <small class="text-muted">Ditolak {{ $item->decided_short }}</small>
                @endif
                <span class="chip">{{ $item->amount_label }}</span>
            </span>
        </a>
    @empty
        <div class="empty">
            @if ($q !== '')
                Tidak ada hasil untuk "{{ $q }}".
            @else
                Belum ada pembayaran di tab ini.
            @endif
        </div>
    @endforelse
</div>

{{ $payments->links() }}