@props(['size' => 1, 'fg' => '#0F1A14', 'accent' => '#0BA84A', 'lang' => 'ar'])

@if ($lang === 'ar')
    <div style="
        font-family: var(--yh-font-ar-display);
        font-weight: 900;
        font-size: {{ 32 * $size }}px;
        color: {{ $fg }};
        letter-spacing: -0.5px;
        direction: rtl;
        line-height: 1;
    ">
        يلا <span style="color: {{ $accent }};">حجيز</span>
    </div>
@else
    <div style="
        font-family: var(--yh-font-en-display);
        font-weight: 800;
        font-size: {{ 28 * $size }}px;
        color: {{ $fg }};
        letter-spacing: -0.02em;
        line-height: 1;
    ">
        yalla <span style="color: {{ $accent }};">hjeez</span>
    </div>
@endif
