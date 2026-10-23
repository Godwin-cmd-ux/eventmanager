@php
    $brandName = $brandName ?? 'Vows';
    $brandMeta = $brandMeta ?? 'Wedding Entrance';
@endphp

<span class="brand">
    <span class="brand__mark" aria-hidden="true">V</span>
    <span class="brand__text">
        <span class="brand__name">{{ $brandName }}</span>
        <span class="brand__meta">{{ $brandMeta }}</span>
    </span>
</span>
