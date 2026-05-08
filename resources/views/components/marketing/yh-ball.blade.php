@props(['size' => 24, 'color' => '#0BA84A'])

<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none">
    <circle cx="12" cy="12" r="10" fill="{{ $color }}"/>
    <path d="M12 2 L12 22 M2 12 L22 12 M5 5 L19 19 M19 5 L5 19" stroke="#fff" stroke-width="0.8" opacity="0.5"/>
    <circle cx="12" cy="12" r="10" stroke="#000" stroke-width="0.5" opacity="0.15" fill="none"/>
</svg>
