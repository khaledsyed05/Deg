<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)
const isRtl = computed(() => locale.value === 'ar')

const form = useForm({ email: '', password: '', remember: false })

function submit() {
  form.post('/admin/login', { onFinish: () => form.reset('password') })
}
</script>

<template>
  <Head title="دق احجزلي — Admin" />

  <div
    class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-950 dark:to-gray-900 flex items-center justify-center p-4"
    :dir="isRtl ? 'rtl' : 'ltr'"
  >
    <div class="w-full max-w-sm">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="w-14 h-14 rounded-2xl bg-emerald-500 flex items-center justify-center text-white text-2xl font-bold shadow-lg mx-auto mb-4">
          د
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tr.appName }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ tr.adminPanel }}</p>
      </div>

      <form
        @submit.prevent="submit"
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 space-y-4"
      >
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
            {{ tr.emailLabel }}
          </label>
          <input
            v-model="form.email"
            type="email"
            autocomplete="email"
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400"
            :class="{ 'border-red-400 focus:ring-red-400': form.errors.email }"
          />
          <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
            {{ tr.passwordLabel }}
          </label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
          />
        </div>

        <div class="flex items-center gap-2">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="w-4 h-4 rounded border-gray-300 text-emerald-500 focus:ring-emerald-400"
          />
          <label for="remember" class="text-sm text-gray-600 dark:text-gray-400 select-none cursor-pointer">
            {{ tr.rememberMe }}
          </label>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors shadow-sm"
        >
          {{ form.processing ? tr.loggingIn : tr.loginBtn }}
        </button>
      </form>

      <!-- Locale switcher on login page -->
      <div class="text-center mt-4">
        <button
          type="button"
          @click="router.post('/locale', { locale: locale === 'ar' ? 'en' : 'ar' })"
          class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        >
          {{ locale === 'ar' ? 'Switch to English' : 'التبديل للعربية' }}
        </button>
      </div>
    </div>
  </div>
</template>
