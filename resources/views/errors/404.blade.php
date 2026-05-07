@extends('layouts.marketing')

@section('title', __('marketing.errors.404_title'))

<x-marketing.section class="pt-32 pb-32 gradient-mesh min-h-[70vh] flex items-center justify-center">
    <div class="max-w-2xl mx-auto text-center">
        <div class="text-9xl font-display font-bold text-brand-green mb-6">404</div>

        <h1 class="text-4xl md:text-5xl font-display font-bold mb-4">
            {{ __('marketing.errors.404_title') }}
        </h1>

        <p class="text-xl text-gray-300 mb-8">
            {{ __('marketing.errors.404_body') }}
        </p>

        <a href="{{ route('home') }}" class="inline-block px-8 py-4 rounded-lg bg-brand-green text-navy-900 font-bold hover:bg-brand-green-light transition">
            {{ __('marketing.errors.404_cta') }}
        </a>
    </div>
</x-marketing.section>
