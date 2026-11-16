@props(['nama', 'class' => 'h-5 w-5', 'sw' => 2])

@php
    // Path ikon disalin dari prototype (kanvas 24x24).
    // Tebal garis (sw) mengikuti pemakaian di prototype.
    $ikon = [
        'home'     => '<path d="M3 11 12 3l9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'box'      => '<path d="M4 7l8-4 8 4v10l-8 4-8-4z"/><path d="M4 7l8 4 8-4"/><path d="M12 11v10"/>',
        'chat'     => '<path d="M4 5h16v11H9l-5 4V5z"/><path d="M8 9h8M8 12.5h5"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/>',
        'store'    => '<path d="M4 10V4h16v6"/><path d="M3 10l1.5-6M21 10l-1.5-6M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'logout'   => '<path d="M9 4H5v16h4"/><path d="M16 8l4 4-4 4"/><path d="M20 12H9"/>',
        'leaf'     => '<path d="M5 19c0-8 5-14 15-14 0 10-6 15-14 15"/><path d="M5 19c3-5 6-8 10-10"/>',
        'search'   => '<circle cx="11" cy="11" r="7"/><path d="M16 16l5 5"/>',
        'bell'     => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2 7 2 8H4c0-1 2-2 2-8"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'chevron'  => '<path d="M6 9l6 6 6-6"/>',
        'chevronr' => '<path d="M9 6l6 6-6 6"/>',
        'cart'     => '<path d="M3 4h2l2.4 11h10.2L20 8H6.2"/><circle cx="9" cy="19" r="1.4"/><circle cx="17" cy="19" r="1.4"/>',
        'check'    => '<path d="M4 12.5l5 5L20 6.5"/>',
        'card'     => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M16 14.5h2"/>',
        'card2'    => '<rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/>',
        'info'     => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/>',
        'shield'   => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'trend'    => '<path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
        'pin'      => '<path d="M12 21s7-6.5 7-12a7 7 0 0 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'bird'     => '<circle cx="16" cy="7" r="3"/><path d="M4 14c0-4 4-6 8-5h4c2 0 3 1 3 3 0 4-4 8-9 8-4 0-6-2-6-6z"/><path d="M19 6.5l3 1-3 1"/><path d="M10 21l-1 2M14 21l1 2"/>',
        'sheep'    => '<path d="M7 15a4 4 0 0 1 0-8 4 4 0 0 1 7-2 4 4 0 0 1 5 3 3.5 3.5 0 0 1-1 7z"/><path d="M8 15v5M16 15v5"/><ellipse cx="18" cy="14" rx="2.5" ry="2"/>',
        'cow'      => '<path d="M6 8c0-2 2-3 6-3s6 1 6 3v6c0 3-2 5-6 5s-6-2-6-5z"/><path d="M6 8L3 5M18 8l3-3"/><circle cx="10" cy="15" r=".8"/><circle cx="14" cy="15" r=".8"/><path d="M9 10v.01M15 10v.01"/>',
        'egg'      => '<path d="M12 3c4 0 7 6 7 11a7 7 0 0 1-14 0c0-5 3-11 7-11z"/>',
        'milk'     => '<path d="M9 3h6v3l2 3v12H7V9l2-3z"/><path d="M7 13h10"/>',
        'meat'     => '<path d="M5 13c0-4 4-8 9-8s6 4 5 8-5 7-9 6-5-2-5-6z"/><circle cx="14" cy="11" r="2.5"/>',
        'duck'     => '<circle cx="8" cy="7" r="3"/><path d="M5 7H2l3 1.5"/><path d="M10 9c6 0 11 2 11 8 0 3-3 4-6 4H9c-3 0-5-2-5-5 0-2 1-3 3-3"/>',
    ];
@endphp

<svg viewBox="0 0 24 24"
     fill="none"
     stroke="currentColor"
     stroke-width="{{ $sw }}"
     stroke-linecap="round"
     stroke-linejoin="round"
     {{ $attributes->merge(['class' => $class]) }}
     aria-hidden="true">{!! $ikon[$nama] ?? '' !!}</svg>