<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from '@/i18n'

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
})

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

const navItems = computed(() => {
  const tr = translations.value
  return [
    { href: '/admin', label: tr.home, icon: '🏠' },
    { href: '/admin/clubs', label: tr.pendingClubs, icon: '⏳' },
    { href: '/admin/settlements', label: tr.settlements, icon: '💰' },
    { href: '/admin/geography/cities', label: tr.cities, icon: '🗺️' },
    { href: '/admin/profile', label: tr.profile, icon: '👤' },
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
            ✕
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
          <span class="text-base w-6 text-center">{{ item.icon }}</span>
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

        <!-- Action buttons -->
        <div class="flex items-center gap-2">
          <!-- Dark mode toggle -->
          <button
            @click="toggleDark"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl text-xs font-medium bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors whitespace-nowrap"
            :title="isDark ? translations.lightMode : translations.darkMode"
          >
            <span>{{ isDark ? '☀️' : '🌙' }}</span>
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
              <span>{{ isDark ? '☀️' : '🌙' }}</span>
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
          <span>✅</span>
          <span>{{ flash.success }}</span>
        </div>
        <div
          v-if="flash.error"
          class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-xl px-4 py-3 text-sm"
        >
          <span>❌</span>
          <span>{{ flash.error }}</span>
        </div>
      </div>

      <slot />
    </div>
  </div>
</template>
