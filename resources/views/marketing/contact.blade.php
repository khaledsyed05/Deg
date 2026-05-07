@extends('layouts.marketing')

@section('title', __('marketing.meta.contact_title'))
@section('description', __('marketing.meta.contact_description'))

<!-- Hero -->
<x-marketing.section class="pt-32 pb-8 gradient-mesh min-h-[30vh] flex items-center">
    <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-5xl md:text-6xl font-display font-bold mb-4">
            {{ __('marketing.contact.title') }}
        </h1>
        <p class="text-gray-300 text-lg">
            {{ __('marketing.contact.subtitle') }}
        </p>
    </div>
</x-marketing.section>

<!-- Contact Methods & Form -->
<x-marketing.section dark>
    @if (session('success'))
        <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 max-w-4xl mx-auto">
        <!-- Left: Contact Methods -->
        <div>
            <h2 class="text-2xl font-bold text-navy-900 mb-8">{{ __('marketing.contact.methods_title') }}</h2>

            <div class="space-y-6">
                <!-- WhatsApp -->
                <a href="https://wa.me/1234567890" class="flex items-start gap-4 p-4 rounded-lg bg-navy-800 hover:border-brand-green border border-transparent transition">
                    <div class="text-brand-green text-2xl flex-shrink-0">💬</div>
                    <div>
                        <h3 class="font-bold text-navy-900">WhatsApp</h3>
                        <p class="text-sm text-gray-600">{{ __('marketing.contact.whatsapp_desc') }}</p>
                    </div>
                </a>

                <!-- Email -->
                <a href="mailto:support@yallaehjez.com" class="flex items-start gap-4 p-4 rounded-lg bg-navy-800 hover:border-brand-green border border-transparent transition">
                    <div class="text-brand-green text-2xl flex-shrink-0">✉️</div>
                    <div>
                        <h3 class="font-bold text-navy-900">البريد الإلكتروني</h3>
                        <p class="text-sm text-gray-600">support@yallaehjez.com</p>
                    </div>
                </a>

                <!-- Phone -->
                <div class="flex items-start gap-4 p-4 rounded-lg bg-navy-800">
                    <div class="text-brand-green text-2xl flex-shrink-0">📞</div>
                    <div>
                        <h3 class="font-bold text-navy-900">{{ __('marketing.contact.phone_title') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('marketing.contact.phone_note') }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-12 pt-8 border-t border-navy-700">
                <a href="/#faq" class="inline-flex items-center gap-2 text-brand-green hover:text-brand-green-light transition">
                    <span>{{ __('marketing.contact.faq_link') }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Right: Contact Form -->
        <div>
            <h2 class="text-2xl font-bold text-navy-900 mb-8">{{ __('marketing.contact.form_title') }}</h2>

            <form action="{{ route('contact.submit') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-bold text-navy-900 mb-2">{{ __('marketing.contact.form_name') }}</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 rounded-lg bg-navy-800 border border-navy-700 text-navy-900 focus:outline-none focus:border-brand-green placeholder-gray-500">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-bold text-navy-900 mb-2">{{ __('marketing.contact.form_phone') }}</label>
                    <input type="tel" id="phone" name="phone" required
                           class="w-full px-4 py-2 rounded-lg bg-navy-800 border border-navy-700 text-navy-900 focus:outline-none focus:border-brand-green placeholder-gray-500">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-bold text-navy-900 mb-2">{{ __('marketing.contact.form_message') }}</label>
                    <textarea id="message" name="message" rows="5" required
                              class="w-full px-4 py-2 rounded-lg bg-navy-800 border border-navy-700 text-navy-900 focus:outline-none focus:border-brand-green placeholder-gray-500 resize-none"></textarea>
                    @error('message')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="w-full px-6 py-3 rounded-lg bg-brand-green text-navy-900 font-bold hover:bg-brand-green-light transition">
                    {{ __('marketing.contact.form_submit') }}
                </button>
            </form>
        </div>
    </div>
</x-marketing.section>
