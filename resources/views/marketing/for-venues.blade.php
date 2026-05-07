@extends('layouts.marketing')

@section('title', __('marketing.meta.venues_title'))
@section('description', __('marketing.meta.venues_description'))

<!-- Hero -->
<x-marketing.section class="pt-32 pb-16 gradient-mesh min-h-[60vh] flex items-center">
    <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-5xl md:text-6xl font-display font-bold mb-6">
            {{ __('marketing.venues.hero.title') }}
        </h1>
        <p class="text-xl text-gray-300 mb-8">
            {{ __('marketing.venues.hero.subtitle') }}
        </p>
        <x-marketing.button variant="primary" size="lg" href="#cta">
            {{ __('marketing.venues.hero.cta') }}
        </x-marketing.button>
    </div>
</x-marketing.section>

<!-- Benefits -->
<x-marketing.section>
    <div class="mb-16 text-center">
        <h2 class="text-4xl font-display font-bold mb-4">{{ __('marketing.venues.benefits.title') }}</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
        @foreach (range(1, 4) as $i)
            <div class="p-6 rounded-xl bg-navy-800 border border-navy-700 hover:border-brand-green transition">
                <h3 class="text-lg font-bold text-brand-green mb-3">
                    {{ __("marketing.venues.benefits.benefit{$i}_title") }}
                </h3>
                <p class="text-gray-300">
                    {{ __("marketing.venues.benefits.benefit{$i}_body") }}
                </p>
            </div>
        @endforeach
    </div>
</x-marketing.section>

<!-- How Partnership Works -->
<x-marketing.section class="bg-navy-800">
    <div class="mb-16 text-center">
        <h2 class="text-4xl font-display font-bold mb-4">{{ __('marketing.venues.partnership.title') }}</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
        @foreach (range(1, 3) as $i)
            <x-marketing.feature-card :number="$i">
                <h3 class="text-lg font-bold mb-3">{{ __("marketing.venues.partnership.step{$i}_title") }}</h3>
                <p class="text-sm text-gray-300">{{ __("marketing.venues.partnership.step{$i}_body") }}</p>
            </x-marketing.feature-card>
        @endforeach
    </div>
</x-marketing.section>

<!-- CTA -->
<x-marketing.section id="cta" class="bg-gradient-to-r from-brand-green/20 via-navy-900 to-navy-900 text-center py-24">
    <h2 class="text-4xl md:text-5xl font-display font-bold mb-6">
        {{ __('marketing.venues.cta.title') }}
    </h2>
    <p class="text-lg text-gray-300 mb-8 max-w-2xl mx-auto">
        {{ __('marketing.venues.cta.body') }}
    </p>

    <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a href="https://wa.me/1234567890" class="inline-block px-8 py-4 rounded-lg bg-brand-green text-navy-900 font-bold hover:bg-brand-green-light transition">
            {{ __('marketing.venues.cta.whatsapp') }}
        </a>
        <a href="mailto:venues@yallaehjez.com" class="inline-block px-8 py-4 rounded-lg bg-navy-800 text-white border border-brand-green font-bold hover:bg-navy-700 transition">
            {{ __('marketing.venues.cta.email') }}
        </a>
    </div>
</x-marketing.section>
