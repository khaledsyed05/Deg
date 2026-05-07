<footer class="bg-navy-800 border-t border-navy-700 mt-20">
    <div class="max-w-8xl mx-auto px-4 py-12">
        <!-- Main Footer Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Brand Column -->
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-6 h-6" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="16" cy="16" r="14" stroke="#22D26A" stroke-width="2"/>
                        <path d="M16 10v12m-4-4h8" stroke="#22D26A" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span class="font-bold text-lg">يلا حجيز</span>
                </div>
                <p class="text-sm text-gray-400">{{ __('marketing.footer.tagline') }}</p>
            </div>

            <!-- Links Column 1 -->
            <div>
                <h3 class="font-bold mb-4">{{ __('marketing.footer.legal') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('legal.privacy') }}" class="text-gray-400 hover:text-brand-green transition">{{ __('marketing.footer.privacy') }}</a></li>
                    <li><a href="{{ route('legal.terms') }}" class="text-gray-400 hover:text-brand-green transition">{{ __('marketing.footer.terms') }}</a></li>
                </ul>
            </div>

            <!-- Links Column 2 -->
            <div>
                <h3 class="font-bold mb-4">{{ __('marketing.footer.company') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="text-gray-400 hover:text-brand-green transition">{{ __('marketing.footer.about') }}</a></li>
                    <li><a href="{{ route('contact') }}" class="text-gray-400 hover:text-brand-green transition">{{ __('marketing.footer.contact') }}</a></li>
                    <li><a href="{{ route('for-venues') }}" class="text-gray-400 hover:text-brand-green transition">{{ __('marketing.footer.for_venues') }}</a></li>
                </ul>
            </div>

            <!-- Social Column -->
            <div>
                <h3 class="font-bold mb-4">{{ __('marketing.footer.social') }}</h3>
                <div class="flex gap-4">
                    <!-- Placeholder social icons -->
                    <a href="#" class="text-gray-400 hover:text-brand-green transition">
                        <span class="sr-only">Instagram</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/></svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-brand-green transition">
                        <span class="sr-only">Facebook</span>
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 12a11 11 0 11-10.16-10.95v3.32h2.81V9.41h-2.81v2.02h2.81v3.35h-2.81a8.68 8.68 0 011.04-4.93h-3.04V4.05C10.66 4 9.5 4 8.27 4c-3.23 0-5.27 1.93-5.27 5.27v2.5H0v3.35h3v8.83h3.35v-8.83h2.81l.42-3.35H6.35v-2.5c0-.92.24-1.54 1.54-1.54h1.68V4.05z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-navy-700 pt-8">
            <p class="text-center text-sm text-gray-500">
                © {{ date('Y') }} {{ __('marketing.footer.copyright') }}
            </p>
        </div>
    </div>
</footer>
