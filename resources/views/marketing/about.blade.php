@extends('layouts.marketing')

@section('title', __('marketing.meta.about_title'))
@section('description', __('marketing.meta.about_description'))

<!-- Hero -->
<x-marketing.section class="pt-32 pb-16 gradient-mesh min-h-[50vh] flex items-center">
    <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-5xl md:text-6xl font-display font-bold mb-6">
            {{ __('marketing.about.hero.title') }}
        </h1>
        <p class="text-xl text-gray-300">
            {{ __('marketing.about.hero.subtitle') }}
        </p>
    </div>
</x-marketing.section>

<!-- Story -->
<x-marketing.section dark>
    <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-display font-bold mb-6">{{ __('marketing.about.story.title') }}</h2>
        <div class="prose prose-invert max-w-none">
            <p class="mb-6 text-gray-700 leading-relaxed">
                {{ __('marketing.about.story.body_1') }}
            </p>
            <p class="text-gray-700 leading-relaxed">
                {{ __('marketing.about.story.body_2') }}
            </p>
        </div>
    </div>
</x-marketing.section>

<!-- Vision -->
<x-marketing.section>
    <div class="max-w-3xl mx-auto">
        <h2 class="text-3xl font-display font-bold mb-6 text-center">{{ __('marketing.about.vision.title') }}</h2>
        <p class="text-center text-gray-300 text-lg leading-relaxed">
            {{ __('marketing.about.vision.body') }}
        </p>
    </div>
</x-marketing.section>

<!-- Team (Placeholder) -->
<x-marketing.section class="bg-navy-800">
    <div class="mb-16 text-center">
        <h2 class="text-3xl font-display font-bold mb-4">{{ __('marketing.about.team.title') }}</h2>
        <p class="text-gray-400">{{ __('marketing.about.team.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        @foreach (range(1, 3) as $i)
            <div class="text-center">
                <div class="w-24 h-24 rounded-full bg-navy-700 border-2 border-brand-green mx-auto mb-4"></div>
                <h3 class="font-bold text-lg">{{ __("marketing.about.team.member{$i}_name") }}</h3>
                <p class="text-sm text-brand-green">{{ __("marketing.about.team.member{$i}_role") }}</p>
            </div>
        @endforeach
    </div>

    <p class="text-center text-sm text-gray-500 mt-12">
        {{ __('marketing.about.team.note') }}
    </p>
</x-marketing.section>
