@php
    $brandName = $brandName ?? 'Vows';
    $brandMeta = $brandMeta ?? 'Wedding Entrance';
@endphp

<span class="brand">
    <span class="brand__mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="5" width="18" height="14" rx="3.5" />
            <path d="m8.2 12.2 2.6 2.6 5-5.4" />
        </svg>
    </span>
    <span class="brand__text">
        <span class="brand__name">{{ $brandName }}</span>
        <span class="brand__meta">{{ $brandMeta }}</span>
    </span>
</span>
