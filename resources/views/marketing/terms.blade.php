@extends('layouts.marketing')

@section('title', __('marketing.meta.terms_title'))
@section('description', __('marketing.meta.terms_description'))

<!-- Hero -->
<x-marketing.section class="pt-32 pb-8 gradient-mesh min-h-[30vh] flex items-center">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-5xl md:text-6xl font-display font-bold mb-4">
            {{ __('marketing.terms.title') }}
        </h1>
        <p class="text-gray-400 text-sm">
            {{ __('marketing.terms.last_updated') }} {{ date('Y-m-d') }}
        </p>
    </div>
</x-marketing.section>

<!-- Content -->
<x-marketing.section dark class="prose prose-invert max-w-4xl mx-auto">
    <div class="space-y-8 text-gray-700">
        @foreach (['agreement', 'usage', 'booking', 'payments', 'refunds', 'account', 'liability', 'termination'] as $section)
            <section>
                <h2 class="text-2xl font-bold text-navy-900 mb-3">
                    {{ __("marketing.terms.section_{$section}_title") }}
                </h2>
                <p class="leading-relaxed">
                    {{ __("marketing.terms.section_{$section}_body") }}
                </p>
            </section>
        @endforeach
    </div>
</x-marketing.section>
