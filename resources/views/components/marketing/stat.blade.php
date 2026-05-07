@props(['number', 'label'])

<div {{ $attributes->class(['text-center']) }}>
    <div class="text-3xl md:text-4xl font-bold text-brand-green mb-2">
        {{ $number }}
    </div>
    <p class="text-sm md:text-base text-gray-400">
        {{ $label }}
    </p>
</div>
