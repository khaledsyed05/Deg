@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
])

@php
$baseClasses = 'inline-block font-bold rounded-lg transition text-center no-underline';

$variants = [
    'primary' => 'bg-brand-green text-navy-900 hover:bg-brand-green-light',
    'secondary' => 'bg-navy-800 text-white border border-brand-green hover:bg-navy-700',
    'ghost' => 'text-brand-green border border-brand-green hover:bg-brand-green hover:text-navy-900',
];

$sizes = [
    'sm' => 'px-4 py-2 text-sm',
    'md' => 'px-6 py-3 text-base',
    'lg' => 'px-8 py-4 text-lg',
];

$classes = $baseClasses . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
