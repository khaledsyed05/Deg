@extends('layouts.marketing-variations')

@push('styles')
<style>
    .v-selector {
        width: 100%;
        min-height: 100vh;
        background: var(--yh-cream);
        direction: rtl;
        font-family: var(--yh-font-ar);
        color: var(--yh-ink);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 64px 48px;
    }

    .v-selector-container {
        max-width: 1200px;
        width: 100%;
    }

    .v-selector-header {
        text-align: center;
        margin-bottom: 64px;
    }

    .v-selector-header h1 {
        font-size: 56px;
        font-weight: 900;
        margin: 0 0 16px;
        letter-spacing: -2px;
    }

    .v-selector-header p {
        font-size: 18px;
        opacity: 0.7;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .v-selector-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 32px;
    }

    .v-selector-card {
        display: flex;
        flex-direction: column;
        gap: 16px;
        padding: 24px;
        border: 2px solid var(--yh-ink);
        border-radius: 20px;
        background: var(--yh-white);
        box-shadow: 4px 4px 0 var(--yh-ink);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .v-selector-card:hover {
        transform: translateY(-4px);
        box-shadow: 8px 8px 0 var(--yh-ink);
    }

    .v-selector-card-preview {
        width: 100%;
        height: 180px;
        background: var(--yh-cream);
        border: 1px dashed var(--yh-ink);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        opacity: 0.5;
        font-family: var(--yh-font-mono);
    }

    .v-selector-card-title {
        font-size: 24px;
        font-weight: 800;
        margin: 0;
    }

    .v-selector-card-desc {
        font-size: 14px;
        opacity: 0.6;
        margin: 0;
        flex: 1;
    }

    .v-selector-card-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--yh-green);
        font-weight: 700;
        text-decoration: none;
        font-size: 14px;
    }

    .v-selector-card-link:hover {
        color: var(--yh-ink);
    }
</style>
@endpush

<div class="v-selector">
    <div class="v-selector-container">
        <div class="v-selector-header">
            <h1>Design Variations</h1>
            <p>يلا حجيز — 4 different visual approaches to showcase the app's features and personality</p>
        </div>

        <div class="v-selector-grid">
            {{-- Bold Variation --}}
            <a href="{{ route('v.bold') }}" class="v-selector-card">
                <div class="v-selector-card-preview">Bold Preview</div>
                <h2 class="v-selector-card-title">V1 — Bold</h2>
                <p class="v-selector-card-desc">Big neon-green hero, oversized type, ticker, bold blocks, playful energy</p>
                <span class="v-selector-card-link">View Design →</span>
            </a>

            {{-- Editorial Variation --}}
            <a href="{{ route('v.editorial') }}" class="v-selector-card">
                <div class="v-selector-card-preview">Editorial Preview</div>
                <h2 class="v-selector-card-title">V2 — Editorial</h2>
                <p class="v-selector-card-desc">Minimal sans-serif, left-aligned text layouts, editorial vibes, refined aesthetic</p>
                <span class="v-selector-card-link">View Design →</span>
            </a>

            {{-- Minimal Variation --}}
            <a href="{{ route('v.minimal') }}" class="v-selector-card">
                <div class="v-selector-card-preview">Minimal Preview</div>
                <h2 class="v-selector-card-title">V3 — Minimal</h2>
                <p class="v-selector-card-desc">Clean whitespace, subtle shadows, understated colors, zen simplicity</p>
                <span class="v-selector-card-link">View Design →</span>
            </a>

            {{-- Stadium Variation --}}
            <a href="{{ route('v.stadium') }}" class="v-selector-card">
                <div class="v-selector-card-preview">Stadium Preview</div>
                <h2 class="v-selector-card-title">V4 — Stadium</h2>
                <p class="v-selector-card-desc">Vibrant gradient backgrounds, dynamic layout, sports energy, fast-paced feel</p>
                <span class="v-selector-card-link">View Design →</span>
            </a>
        </div>
    </div>
</div>
