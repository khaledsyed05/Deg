@props(['dark' => false])

<section {{ $attributes->class(['py-20', 'bg-navy-900' => !$dark, 'bg-white text-navy-900' => $dark]) }}>
    <div class="max-w-8xl mx-auto px-4">
        {{ $slot }}
    </div>
</section>
