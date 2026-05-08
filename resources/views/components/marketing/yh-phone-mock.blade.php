@props(['width' => 280, 'theme' => 'light'])

@php
    $height = $width * 2.05;
    $isDark = $theme === 'dark';
    $borderRadius = $width * 0.13;
    $innerRadius = $width * 0.105;
    $islandWidth = $width * 0.32;
    $islandHeight = $width * 0.08;
@endphp

<div style="
    width: {{ $width }}px;
    height: {{ $height }}px;
    position: relative;
    border-radius: {{ $borderRadius }}px;
    background: {{ $isDark ? '#1C1917' : '#0F1A14' }};
    padding: {{ $width * 0.025 }}px;
    box-shadow: 0 30px 80px rgba(11,168,74,0.25), 0 12px 32px rgba(15,26,20,0.18);
">
    <div style="
        width: 100%;
        height: 100%;
        border-radius: {{ $innerRadius }}px;
        background: {{ $isDark ? '#0C0A09' : '#FAFAF9' }};
        overflow: hidden;
        position: relative;
    ">
        <!-- Dynamic island -->
        <div style="
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: {{ $islandWidth }}px;
            height: {{ $islandHeight }}px;
            background: #000;
            border-radius: 100px;
            z-index: 2;
        "></div>
        {{ $slot }}
    </div>
</div>
