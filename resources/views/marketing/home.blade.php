@extends('layouts.marketing')

@section('title', __('marketing.meta.home_title'))
@section('description', __('marketing.meta.home_description'))

<!-- Hero Section -->
<x-marketing.section class="pt-32 pb-20 gradient-mesh min-h-[85vh] flex items-center">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
        <!-- Left Column: Copy (RTL = right) -->
        <div class="md:order-2 animate-fade-in-up">
            <!-- Eyebrow -->
            <div class="inline-block px-3 py-1 rounded-full bg-brand-green/20 text-brand-green text-xs font-bold mb-6">
                {{ __('marketing.home.hero.eyebrow') }}
            </div>

            <!-- Main Headline -->
            <h1 class="text-5xl md:text-6xl lg:text-7xl font-display font-bold leading-tight mb-6">
                {{ __('marketing.home.hero.title_start') }}
                <span class="text-gradient-green">{{ __('marketing.home.hero.title_highlight') }}</span>
            </h1>

            <!-- Subtitle -->
            <p class="text-lg text-gray-300 mb-8 leading-relaxed max-w-md">
                {{ __('marketing.home.hero.subtitle') }}
            </p>

            <!-- CTAs -->
            <div class="flex flex-col sm:flex-row gap-4">
                <x-marketing.button variant="primary" size="lg" href="#download">
                    {{ __('marketing.home.hero.cta_primary') }}
                </x-marketing.button>
                <x-marketing.button variant="secondary" size="lg" href="#how-it-works">
                    {{ __('marketing.home.hero.cta_secondary') }}
                </x-marketing.button>
            </div>
        </div>

        <!-- Right Column: Phone Mockup (RTL = left) -->
        <div class="md:order-1 flex justify-center animate-subtle-bounce">
            <svg class="w-full max-w-sm" viewBox="0 0 300 600" xmlns="http://www.w3.org/2000/svg">
                <!-- Phone Frame -->
                <rect x="20" y="20" width="260" height="560" rx="40" fill="none" stroke="#22D26A" stroke-width="8"/>
                <rect x="30" y="30" width="240" height="540" rx="35" fill="#050E1C"/>

                <!-- Notch -->
                <rect x="120" y="35" width="60" height="20" rx="10" fill="#050E1C" stroke="#22D26A" stroke-width="2"/>

                <!-- Screen Content -->
                <!-- Status Bar -->
                <text x="280" y="55" font-size="12" fill="#999" text-anchor="end">09:41</text>

                <!-- App Header -->
                <rect x="35" y="65" width="230" height="50" fill="#0B1A2E"/>
                <text x="150" y="95" font-family="Tajawal, sans-serif" font-size="16" font-weight="bold" fill="#22D26A" text-anchor="middle">الملاعب القريبة</text>

                <!-- Venue Cards -->
                <g>
                    <!-- Card 1 -->
                    <rect x="45" y="130" width="210" height="90" rx="8" fill="#152544" stroke="#22D26A" stroke-width="1"/>
                    <text x="250" y="155" font-family="Tajawal, sans-serif" font-size="13" fill="#fff" text-anchor="end">ملعب الشهباء</text>
                    <text x="250" y="175" font-family="Tajawal, sans-serif" font-size="11" fill="#999" text-anchor="end">1.2 كم</text>
                    <text x="250" y="210" font-family="Tajawal, sans-serif" font-size="14" font-weight="bold" fill="#22D26A" text-anchor="end">75,000 SP</text>

                    <!-- Card 2 -->
                    <rect x="45" y="240" width="210" height="90" rx="8" fill="#152544"/>
                    <text x="250" y="265" font-family="Tajawal, sans-serif" font-size="13" fill="#fff" text-anchor="end">ملعب النجمة</text>
                    <text x="250" y="285" font-family="Tajawal, sans-serif" font-size="11" fill="#999" text-anchor="end">2.4 كم</text>
                    <text x="250" y="320" font-family="Tajawal, sans-serif" font-size="14" font-weight="bold" fill="#22D26A" text-anchor="end">65,000 SP</text>

                    <!-- Card 3 -->
                    <rect x="45" y="350" width="210" height="90" rx="8" fill="#152544"/>
                    <text x="250" y="375" font-family="Tajawal, sans-serif" font-size="13" fill="#fff" text-anchor="end">ملعب الشام</text>
                    <text x="250" y="395" font-family="Tajawal, sans-serif" font-size="11" fill="#999" text-anchor="end">3.1 كم</text>
                    <text x="250" y="430" font-family="Tajawal, sans-serif" font-size="14" font-weight="bold" fill="#22D26A" text-anchor="end">80,000 SP</text>
                </g>

                <!-- Home Indicator -->
                <rect x="125" y="575" width="50" height="4" rx="2" fill="#666"/>
            </svg>
        </div>
    </div>
</x-marketing.section>

<!-- Stats Strip -->
<x-marketing.section dark class="py-12 border-t border-gray-200">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
        <x-marketing.stat number="+500" label="{{ __('marketing.home.stats.venues') }}" />
        <x-marketing.stat number="30" label="{{ __('marketing.home.stats.seconds') }}" />
        <x-marketing.stat number="+1000" label="{{ __('marketing.home.stats.players') }}" />
        <x-marketing.stat number="0" label="{{ __('marketing.home.stats.hidden_fees') }}" />
    </div>
</x-marketing.section>

<!-- How It Works -->
<x-marketing.section id="how-it-works">
    <div class="mb-16">
        <h2 class="text-4xl md:text-5xl font-display font-bold text-center mb-4">
            {{ __('marketing.home.how_it_works.title') }}
        </h2>
        <p class="text-center text-gray-400 max-w-2xl mx-auto">
            {{ __('marketing.home.how_it_works.subtitle') }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-marketing.feature-card :number="1">
            <h3 class="text-lg font-bold mb-3">{{ __('marketing.home.how_it_works.step1_title') }}</h3>
            <p class="text-sm text-gray-300">{{ __('marketing.home.how_it_works.step1_body') }}</p>
        </x-marketing.feature-card>

        <x-marketing.feature-card :number="2">
            <h3 class="text-lg font-bold mb-3">{{ __('marketing.home.how_it_works.step2_title') }}</h3>
            <p class="text-sm text-gray-300">{{ __('marketing.home.how_it_works.step2_body') }}</p>
        </x-marketing.feature-card>

        <x-marketing.feature-card :number="3">
            <h3 class="text-lg font-bold mb-3">{{ __('marketing.home.how_it_works.step3_title') }}</h3>
            <p class="text-sm text-gray-300">{{ __('marketing.home.how_it_works.step3_body') }}</p>
        </x-marketing.feature-card>
    </div>
</x-marketing.section>

<!-- Features -->
<x-marketing.section class="bg-navy-800">
    <div class="mb-16">
        <h2 class="text-4xl md:text-5xl font-display font-bold text-center mb-4">
            {{ __('marketing.home.features.title') }}
        </h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div class="p-6 rounded-xl bg-gradient-to-br from-navy-700 to-navy-900 border border-navy-600 hover:border-brand-green transition">
            <h3 class="text-xl font-bold mb-3 text-brand-green">{{ __('marketing.home.features.instant_title') }}</h3>
            <p class="text-gray-300">{{ __('marketing.home.features.instant_body') }}</p>
        </div>

        <div class="p-6 rounded-xl bg-gradient-to-br from-navy-700 to-navy-900 border border-navy-600 hover:border-brand-green transition">
            <h3 class="text-xl font-bold mb-3 text-brand-green">{{ __('marketing.home.features.wallet_title') }}</h3>
            <p class="text-gray-300">{{ __('marketing.home.features.wallet_body') }}</p>
        </div>

        <div class="p-6 rounded-xl bg-gradient-to-br from-navy-700 to-navy-900 border border-navy-600 hover:border-brand-green transition">
            <h3 class="text-xl font-bold mb-3 text-brand-green">{{ __('marketing.home.features.share_title') }}</h3>
            <p class="text-gray-300">{{ __('marketing.home.features.share_body') }}</p>
        </div>

        <div class="p-6 rounded-xl bg-gradient-to-br from-navy-700 to-navy-900 border border-navy-600 hover:border-brand-green transition">
            <h3 class="text-xl font-bold mb-3 text-brand-green">{{ __('marketing.home.features.tournaments_title') }}</h3>
            <p class="text-gray-300">{{ __('marketing.home.features.tournaments_body') }}</p>
        </div>
    </div>
</x-marketing.section>

<!-- For Venues Teaser -->
<x-marketing.section class="bg-gradient-to-r from-brand-green/20 via-navy-900 to-navy-900 border-t border-brand-green/30">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-display font-bold mb-4">
            {{ __('marketing.home.venues_teaser.title') }}
        </h2>
        <p class="text-gray-300 mb-8">
            {{ __('marketing.home.venues_teaser.body') }}
        </p>
        <x-marketing.button variant="secondary" size="lg" href="{{ route('for-venues') }}">
            {{ __('marketing.home.venues_teaser.cta') }}
        </x-marketing.button>
    </div>
</x-marketing.section>

<!-- Download CTA -->
<x-marketing.section id="download" class="bg-gradient-to-b from-navy-800 to-brand-green/30 text-center py-24">
    <h2 class="text-5xl md:text-6xl font-display font-bold mb-6">
        {{ __('marketing.home.download.title') }}
    </h2>
    <p class="text-xl text-gray-300 mb-12 max-w-2xl mx-auto">
        {{ __('marketing.home.download.subtitle') }}
    </p>

    <div class="flex flex-col sm:flex-row gap-6 justify-center items-center flex-wrap">
        <!-- App Store Badge -->
        <a href="#" class="inline-block">
            <svg class="w-40" viewBox="0 0 240 80" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="236" height="76" rx="13" fill="none" stroke="#999" stroke-width="2"/>
                <text x="120" y="45" font-family="Tajawal, sans-serif" font-size="18" fill="#fff" text-anchor="middle" font-weight="bold">App Store</text>
            </svg>
        </a>
        <!-- Google Play Badge -->
        <a href="#" class="inline-block">
            <svg class="w-40" viewBox="0 0 240 80" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="2" width="236" height="76" rx="13" fill="none" stroke="#999" stroke-width="2"/>
                <text x="120" y="45" font-family="Tajawal, sans-serif" font-size="18" fill="#fff" text-anchor="middle" font-weight="bold">Google Play</text>
            </svg>
        </a>
    </div>

    <p class="text-sm text-gray-500 mt-8">
        {{ __('marketing.home.download.note') }}
    </p>
</x-marketing.section>

<!-- FAQ -->
<x-marketing.section class="bg-navy-800">
    <div class="mb-16">
        <h2 class="text-4xl md:text-5xl font-display font-bold text-center mb-4">
            {{ __('marketing.home.faq.title') }}
        </h2>
    </div>

    <div class="max-w-3xl mx-auto space-y-4">
        @foreach (range(1, 5) as $i)
            <div class="border border-navy-700 rounded-lg overflow-hidden" x-data="{ open{{ $i }}: false }">
                <button @click="open{{ $i }} = !open{{ $i }}" class="w-full px-6 py-4 text-right font-bold hover:bg-navy-700 transition flex items-center justify-between">
                    <svg x-show="open{{ $i }}" class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    <svg x-show="!open{{ $i }}" class="w-5 h-5 text-brand-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    <span>{{ __("marketing.home.faq.q{$i}") }}</span>
                </button>
                <div x-show="open{{ $i }}" class="px-6 py-4 bg-navy-900 text-gray-300 border-t border-navy-700">
                    {{ __("marketing.home.faq.a{$i}") }}
                </div>
            </div>
        @endforeach
    </div>
</x-marketing.section>
