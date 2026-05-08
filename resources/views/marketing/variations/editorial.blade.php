@extends('layouts.marketing-variations')

<div style="width: 100%; min-height: 100vh; background: #f5f5f5; display: flex; align-items: center; justify-content: center; padding: 64px 48px; font-family: var(--yh-font-ar); text-align: center; direction: rtl;">
    <div style="max-width: 800px;">
        <h1 style="font-size: 56px; font-weight: 900; margin: 0 0 16px; color: #0F1A14; letter-spacing: -2px;">V2 — Editorial</h1>
        <p style="font-size: 18px; color: #666; line-height: 1.6; margin-bottom: 32px;">
            Minimal sans-serif, left-aligned text layouts, editorial vibes, refined aesthetic
        </p>
        <a href="{{ route('v.selector') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 16px 32px; background: #0BA84A; color: white; text-decoration: none; border-radius: 999px; font-weight: 700; border: none; cursor: pointer;">
            ← Back to Variations
        </a>
        <p style="margin-top: 48px; color: #999; font-size: 14px;">Coming soon...</p>
    </div>
</div>
