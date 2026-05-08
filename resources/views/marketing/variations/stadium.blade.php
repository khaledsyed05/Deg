@extends('layouts.marketing-variations')

<div style="width: 100%; min-height: 100vh; background: linear-gradient(135deg, #FF6B5B 0%, #FFB800 100%); display: flex; align-items: center; justify-content: center; padding: 64px 48px; font-family: var(--yh-font-ar); text-align: center; direction: rtl;">
    <div style="max-width: 800px;">
        <h1 style="font-size: 56px; font-weight: 900; margin: 0 0 16px; color: white; letter-spacing: -2px;">V4 — Stadium</h1>
        <p style="font-size: 18px; color: rgba(255,255,255,0.9); line-height: 1.6; margin-bottom: 32px;">
            Vibrant gradient backgrounds, dynamic layout, sports energy, fast-paced feel
        </p>
        <a href="{{ route('v.selector') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 16px 32px; background: white; color: #FF6B5B; text-decoration: none; border-radius: 999px; font-weight: 700; border: none; cursor: pointer;">
            ← Back to Variations
        </a>
        <p style="margin-top: 48px; color: rgba(255,255,255,0.7); font-size: 14px;">Coming soon...</p>
    </div>
</div>
