<nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
     :class="scrolled ? 'bg-navy-900 shadow-lg' : 'bg-transparent'"
     x-data="{ open: false, scrolled: false }"
     @scroll.window="scrolled = window.scrollY > 50">
    <div class="max-w-8xl mx-auto px-4 py-4 flex items-center justify-between">
        <!-- Logo (RTL: right side) -->
        <a href="{{ route('home') }}" class="flex items-center gap-2 flex-shrink-0">
            <svg class="w-8 h-8" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="16" cy="16" r="14" stroke="#22D26A" stroke-width="2"/>
                <path d="M16 10v12m-4-4h8" stroke="#22D26A" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="text-lg font-bold">يلا حجيز</span>
        </a>

        <!-- Desktop Links (center) -->
        <div class="hidden md:flex items-center gap-8 flex-1 justify-center">
            <a href="{{ route('home') }}"
               @class(['font-medium', 'text-brand-green' => Route::is('home'), 'hover:text-brand-green transition' => !Route::is('home')])
               aria-current="{{ Route::is('home') ? 'page' : 'false' }}">
                {{ __('marketing.nav.home') }}
            </a>
            <a href="{{ route('about') }}"
               @class(['font-medium', 'text-brand-green' => Route::is('about'), 'hover:text-brand-green transition' => !Route::is('about')])
               aria-current="{{ Route::is('about') ? 'page' : 'false' }}">
                {{ __('marketing.nav.about') }}
            </a>
            <a href="{{ route('for-venues') }}"
               @class(['font-medium', 'text-brand-green' => Route::is('for-venues'), 'hover:text-brand-green transition' => !Route::is('for-venues')])
               aria-current="{{ Route::is('for-venues') ? 'page' : 'false' }}">
                {{ __('marketing.nav.for_venues') }}
            </a>
            <a href="{{ route('contact') }}"
               @class(['font-medium', 'text-brand-green' => Route::is('contact'), 'hover:text-brand-green transition' => !Route::is('contact')])
               aria-current="{{ Route::is('contact') ? 'page' : 'false' }}">
                {{ __('marketing.nav.contact') }}
            </a>
        </div>

        <!-- CTA Button (RTL: left side) -->
        <div class="hidden md:block ml-auto">
            <a href="#download" class="inline-block px-6 py-2 rounded-lg bg-brand-green text-navy-900 font-bold hover:bg-brand-green-light transition">
                {{ __('marketing.nav.download') }}
            </a>
        </div>

        <!-- Mobile Hamburger -->
        <button @click="open = !open" class="md:hidden ml-auto p-2 hover:bg-navy-800 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open" @click.outside="open = false" class="md:hidden bg-navy-800 border-t border-navy-700">
        <div class="px-4 py-4 flex flex-col gap-4">
            <a href="{{ route('home') }}" class="text-brand-green font-medium">{{ __('marketing.nav.home') }}</a>
            <a href="{{ route('about') }}" class="hover:text-brand-green">{{ __('marketing.nav.about') }}</a>
            <a href="{{ route('for-venues') }}" class="hover:text-brand-green">{{ __('marketing.nav.for_venues') }}</a>
            <a href="{{ route('contact') }}" class="hover:text-brand-green">{{ __('marketing.nav.contact') }}</a>
            <a href="#download" class="mt-4 block px-4 py-2 rounded-lg bg-brand-green text-navy-900 font-bold text-center hover:bg-brand-green-light transition">
                {{ __('marketing.nav.download') }}
            </a>
        </div>
    </div>
</nav>
