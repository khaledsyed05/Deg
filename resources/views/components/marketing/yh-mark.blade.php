@props(['size' => 64, 'fg' => '#fff', 'bg' => '#0BA84A', 'radius' => 0.24])

@php
    $r = $size * $radius;
    $rPercent = ($r * 100) / $size;
@endphp

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 100 100" style="display:block;flex-shrink:0;">
    <rect x="0" y="0" width="100" height="100" rx="{{ $rPercent }}" fill="{{ $bg }}"/>
    <path d="M24 26 L44 54 L44 76 L56 76 L56 54 L76 26"
          stroke="{{ $fg }}" stroke-width="11" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
    <path d="M62 50 L72 60 L88 38"
          stroke="{{ $fg }}" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
</svg>
