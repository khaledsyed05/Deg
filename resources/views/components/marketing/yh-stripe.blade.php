@props(['w' => '100%', 'h' => '100%', 'label' => 'photo', 'color' => '#0BA84A', 'dark' => false])

@php
    if ($dark) {
        $background = 'repeating-linear-gradient(135deg, rgba(34,210,106,0.18) 0 8px, rgba(34,210,106,0.06) 8px 16px)';
        $borderColor = 'rgba(34,210,106,0.4)';
        $textColor = 'rgba(217,245,227,0.7)';
    } else {
        $background = "repeating-linear-gradient(135deg, {$color}1a 0 8px, {$color}08 8px 16px)";
        $borderColor = $color . '55';
        $textColor = $color;
    }
@endphp

<div style="
    width: {{ $w }};
    height: {{ $h }};
    position: relative;
    overflow: hidden;
    background: {{ $background }};
    border: 1px dashed {{ $borderColor }};
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--yh-font-mono);
    font-size: 10px;
    color: {{ $textColor }};
    letter-spacing: 0.1em;
    text-transform: uppercase;
    border-radius: inherit;
">
    {{ $label }}
</div>
