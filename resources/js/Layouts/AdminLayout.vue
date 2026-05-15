<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from '@/i18n'

// PWA install
const deferredPrompt = ref<any>(null)
const isInstalled = ref(false)
const isIos = ref(false)
const showIosHint = ref(false)

const page = usePage()
const user = computed(() => (page.props.auth as any)?.user)
const flash = computed(() => page.props.flash as { success?: string; error?: string })
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const isRtl = computed(() => locale.value === 'ar')

const { t } = useI18n(locale.value)
const translations = computed(() => useI18n(locale.value).t)

const isDark = ref(false)
const sidebarOpen = ref(false)

onMounted(() => {
  isDark.value = localStorage.getItem('adminDarkMode') === 'true'
  applyDark(isDark.value)

  // PWA: detect iOS
  const ua = navigator.userAgent.toLowerCase()
  isIos.value = /iphone|ipad|ipod/.test(ua)

  // PWA: already installed (standalone mode)
  const standalone = window.matchMedia('(display-mode: standalone)').matches || (navigator as any).standalone === true
  if (standalone) { isInstalled.value = true }

  // PWA: Android/Chrome install prompt
  window.addEventListener('beforeinstallprompt', (e: Event) => {
    e.preventDefault()
    deferredPrompt.value = e
  })

  window.addEventListener('appinstalled', () => {
    isInstalled.value = true
    deferredPrompt.value = null
  })
})

async function installPwa() {
  if (deferredPrompt.value) {
    deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice
    if (outcome === 'accepted') { isInstalled.value = true }
    deferredPrompt.value = null
  } else if (isIos.value) {
    showIosHint.value = !showIosHint.value
  }
}

const showInstallBtn = computed(() =>
  !isInstalled.value && (deferredPrompt.value !== null || isIos.value)
)

function applyDark(value: boolean) {
  document.documentElement.classList.toggle('dark', value)
}

function toggleDark() {
  isDark.value = !isDark.value
  localStorage.setItem('adminDarkMode', String(isDark.value))
  applyDark(isDark.value)
}

function closeSidebar() {
  sidebarOpen.value = false
}

function switchLocale() {
  const next = locale.value === 'ar' ? 'en' : 'ar'
  router.post('/locale', { locale: next }, { preserveScroll: false })
}

const NAV_ICONS: Record<string, string> = {
  home: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
  clubs: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  settlements: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
  globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
  map: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7',
  pin: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z',
  grid: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
  calendar: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  card: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
  chartBar: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
  trendUp: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
  users: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  money: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  cog: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
  user: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
  chat: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
  profile: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z',
}

const navItems = computed(() => {
  const tr = translations.value
  return [
    { href: '/admin', label: tr.home, icon: NAV_ICONS.home },
    { href: '/admin/clubs', label: tr.clubs, icon: NAV_ICONS.clubs },
    { href: '/admin/settlements', label: tr.settlements, icon: NAV_ICONS.settlements },
    { href: '/admin/geography/countries', label: tr.geoCountries, icon: NAV_ICONS.globe },
    { href: '/admin/geography/states', label: tr.geoStates, icon: NAV_ICONS.map },
    { href: '/admin/geography/cities', label: tr.geoCities, icon: NAV_ICONS.pin },
    { href: '/admin/venues', label: tr.venues, icon: NAV_ICONS.grid },
    { href: '/admin/bookings', label: tr.bookings, icon: NAV_ICONS.calendar },
    { href: '/admin/payments', label: tr.payments, icon: NAV_ICONS.card },
    { href: '/admin/analytics/revenue', label: tr.analyticsRevenue, icon: NAV_ICONS.chartBar },
    { href: '/admin/analytics/bookings', label: tr.bookingsAnalyticsTitle, icon: NAV_ICONS.trendUp },
    { href: '/admin/analytics/players', label: tr.playersAnalyticsTitle, icon: NAV_ICONS.users },
    { href: '/admin/settings/commissions', label: tr.commissionsTitle, icon: NAV_ICONS.money },
    { href: '/admin/settings/system', label: tr.settingsTitle, icon: NAV_ICONS.cog },
    { href: '/admin/settings/app-startup', label: tr.appStartupNav, icon: NAV_ICONS.cog },
    { href: '/admin/bookings/calendar', label: tr.bookingCalendarView, icon: NAV_ICONS.calendar },
    { href: '/admin/players', label: tr.players, icon: NAV_ICONS.user },
    { href: '/admin/whatsapp/platform', label: tr.platformWhatsappNav, icon: NAV_ICONS.chat },
    { href: '/admin/profile', label: tr.profile, icon: NAV_ICONS.profile },
  ]
})

const currentPath = computed(() => {
  try { return new URL(window.location.href).pathname } catch { return '' }
})

function isActive(href: string): boolean {
  if (href === '/admin') return currentPath.value === '/admin'
  return currentPath.value.startsWith(href)
}
</script>

<template>
  <div
    class="min-h-screen bg-gray-50 dark:bg-gray-950 transition-colors"
    :dir="isRtl ? 'rtl' : 'ltr'"
  >
    <!-- Mobile backdrop -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm md:hidden"
      @click="closeSidebar"
    />

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 z-30 w-72 bg-white dark:bg-gray-900 border-e border-gray-100 dark:border-gray-800 shadow-lg flex flex-col transition-transform duration-300 ease-in-out"
      :class="[
        isRtl ? 'right-0' : 'left-0',
        sidebarOpen
          ? 'translate-x-0'
          : isRtl ? 'translate-x-full md:translate-x-0' : '-translate-x-full md:translate-x-0'
      ]"
    >
      <!-- Logo -->
      <div class="px-5 py-5 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center justify-between mb-1">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-white font-bold text-sm shadow-sm">
              د
            </div>
            <div>
              <h1 class="text-base font-bold text-gray-900 dark:text-white leading-tight">
                {{ translations.appName }}
              </h1>
              <p class="text-xs text-gray-400 dark:text-gray-500">{{ translations.controlPanel }}</p>
            </div>
          </div>
          <button
            @click="closeSidebar"
            class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors md:hidden"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
        <Link
          v-for="item in navItems"
          :key="item.href"
          :href="item.href"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all"
          :class="isActive(item.href)
            ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 shadow-sm'
            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
          @click="closeSidebar"
        >
          <svg class="w-4.5 h-4.5 flex-shrink-0 w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" :d="item.icon" />
          </svg>
          <span>{{ item.label }}</span>
          <span
            v-if="isActive(item.href)"
            class="ms-auto w-1.5 h-1.5 rounded-full bg-emerald-500"
          />
        </Link>
      </nav>

      <!-- Bottom Controls -->
      <div class="p-4 border-t border-gray-100 dark:border-gray-800 space-y-3">
        <!-- User info -->
        <div class="flex items-center gap-3 px-1">
          <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center text-emerald-700 dark:text-emerald-400 text-sm font-bold flex-shrink-0">
            {{ user?.name?.charAt(0) ?? '?' }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ user?.name }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500">{{ translations.systemAdmin }}</p>
          </div>
        </div>

        <!-- PWA Install -->
        <div v-if="showInstallBtn" class="relative">
          <button
            @click="installPwa"
            class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-800 transition-colors"
          >
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            <span>{{ locale === 'ar' ? 'تثبيت التطبيق' : 'Install App' }}</span>
          </button>

          <!-- iOS hint -->
          <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div
              v-if="showIosHint"
              class="absolute bottom-full mb-2 inset-x-0 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-xl p-3 shadow-xl z-50"
            >
              <button @click="showIosHint = false" class="absolute top-2 end-2 text-gray-400 hover:text-white">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <p class="font-semibold mb-1">{{ locale === 'ar' ? 'إضافة للشاشة الرئيسية' : 'Add to Home Screen' }}</p>
              <p class="text-gray-300 leading-relaxed">
                {{ locale === 'ar'
                  ? 'اضغط على زر المشاركة ثم اختر "إضافة إلى الشاشة الرئيسية"'
                  : 'Tap the Share button then select "Add to Home Screen"' }}
                <span class="inline-block ms-1">⎋</span>
              </p>
              <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-gray-900 dark:bg-gray-700 rotate-45 rounded-sm"></div>
            </div>
          </Transition>
        </div>

        <!-- Action buttons -->
        <div class="flex items-center gap-2">
          <!-- Dark mode toggle -->
          <button
            @click="toggleDark"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors whitespace-nowrap"
            :title="isDark ? translations.lightMode : translations.darkMode"
          >
            <svg v-if="isDark" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg v-else class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
            <span>{{ isDark ? translations.lightMode : translations.darkMode }}</span>
          </button>

          <!-- Locale switcher -->
          <button
            @click="switchLocale"
            class="px-3 py-2 rounded-xl text-xs font-bold bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-700"
            :class="locale === 'ar' ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400'"
            :title="locale === 'ar' ? 'Switch to English' : 'التبديل للعربية'"
          >
            {{ locale === 'ar' ? 'EN' : 'ع' }}
          </button>

          <!-- Logout -->
          <button
            @click="router.post('/admin/logout')"
            class="px-3 py-2 rounded-xl text-xs font-medium bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors whitespace-nowrap"
          >
            {{ translations.logout }}
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <div
      class="min-h-screen flex flex-col"
      :class="isRtl ? 'md:mr-72' : 'md:ml-72'"
    >
      <!-- Mobile Top Bar -->
      <header class="md:hidden sticky top-0 z-10 bg-white/95 dark:bg-gray-900/95 backdrop-blur border-b border-gray-200 dark:border-gray-800 px-4 py-3">
        <div class="flex items-center justify-between">
          <button
            @click="sidebarOpen = true"
            class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-md bg-emerald-500 flex items-center justify-center text-white font-bold text-xs">
              د
            </div>
            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ translations.appName }}</span>
          </div>

          <div class="flex items-center gap-1.5">
            <button
              @click="switchLocale"
              class="px-2.5 py-1.5 rounded-lg text-xs font-bold bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 transition-colors"
              :class="locale === 'ar' ? 'text-blue-600' : 'text-orange-600'"
            >
              {{ locale === 'ar' ? 'EN' : 'ع' }}
            </button>
            <button
              @click="toggleDark"
              class="w-9 h-9 flex items-center justify-center rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 transition-colors"
            >
              <svg v-if="isDark" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>
          </div>
        </div>
      </header>

      <!-- Flash Messages -->
      <div v-if="flash.success || flash.error" class="px-4 sm:px-6 pt-4">
        <div
          v-if="flash.success"
          class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl px-4 py-3 text-sm"
        >
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ flash.success }}</span>
        </div>
        <div
          v-if="flash.error"
          class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-xl px-4 py-3 text-sm"
        >
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ flash.error }}</span>
        </div>
      </div>

      <slot />
    </div>
  </div>
</template>
