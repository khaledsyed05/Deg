@props(['size' => 1, 'fg' => '#0F1A14', 'accent' => '#0BA84A', 'markBg' => null, 'markFg' => '#fff', 'stacked' => false])

@php
    $actualMarkBg = $markBg ?? $accent;
@endphp

<div style="
    display: inline-flex;
    align-items: center;
    gap: {{ 14 * $size }}px;
    flex-direction: {{ $stacked ? 'column' : 'row' }};
">
    <x-marketing.yh-mark :size="48 * $size" bg="{{ $actualMarkBg }}" fg="{{ $markFg }}" />
    <div style="
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: {{ 2 * $size }}px;
    ">
        <x-marketing.yh-wordmark :size="$size * 0.85" :fg="$fg" :accent="$accent" lang="ar" />
        <div style="
            font-family: var(--yh-font-en);
            font-weight: 600;
            font-size: {{ 9 * $size }}px;
            color: {{ $fg }};
            opacity: 0.55;
            letter-spacing: 2.5px;
            text-transform: uppercase;
        ">yalla hjeez</div>
    </div>
</div>
