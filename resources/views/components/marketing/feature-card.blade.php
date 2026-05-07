@props(['number' => null])

<div {{ $attributes->class(['p-6 rounded-xl glass-effect']) }}>
    @if ($number)
        <div class="inline-block w-10 h-10 rounded-full bg-brand-green text-navy-900 flex items-center justify-center font-bold mb-4">
            {{ $number }}
        </div>
    @endif

    @if (isset($icon))
        <div class="mb-4 text-3xl">
            {{ $icon }}
        </div>
    @endif

    @if (isset($heading))
        <h3 class="text-lg font-bold mb-2">
            {{ $heading }}
        </h3>
    @endif

    @if (isset($body))
        <p class="text-sm text-gray-300">
            {{ $body }}
        </p>
    @endif

    {{ $slot }}
</div>
