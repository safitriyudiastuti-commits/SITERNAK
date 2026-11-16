<div class="list">
    @foreach ($categories as $category)
        <p class="note" style="margin:14px 4px 6px"><strong>{{ $category->group_label }} · {{ $category->name }}</strong></p>

        @forelse ($category->types as $item)
            @php $isSelected = isset($type) && $type->is($item); @endphp
            <a href="{{ route('admin.data-induk.show', $item) }}"
               class="list-item {{ $isSelected ? 'is-active' : '' }}">
                <span class="avatar">{{ $item->initials }}</span>
                <span class="list-main">
                    <strong>{{ $item->name }}</strong>
                    <small>{{ implode(' · ', $item->mode_labels) }}</small>
                </span>
                <span class="list-side">
                    @unless ($item->is_active)
                        <span class="chip">Nonaktif</span>
                    @endunless
                </span>
            </a>
        @empty
            <div class="empty">Belum ada jenis di kategori ini.</div>
        @endforelse
    @endforeach
</div>
