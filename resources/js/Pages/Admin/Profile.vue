<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

const props = defineProps<{ user: { id: number; name: string; email: string } }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)

const infoForm = useForm({ name: props.user.name, email: props.user.email })
const passForm = useForm({ current_password: '', password: '', password_confirmation: '' })

function saveInfo() { infoForm.put('/admin/profile') }
function savePassword() { passForm.put('/admin/profile/password', { onSuccess: () => passForm.reset() }) }
</script>

<template>
  <Head :title="tr.profile" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-xl space-y-5">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tr.profile }}</h1>

      <!-- Personal Info -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-base">👤</span>
          {{ tr.personalInfo }}
        </h2>
        <form @submit.prevent="saveInfo" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.name }}</label>
            <input
              v-model="infoForm.name"
              type="text"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
              :class="{ 'border-red-400 focus:ring-red-400': infoForm.errors.name }"
            />
            <p v-if="infoForm.errors.name" class="text-xs text-red-500 mt-1">{{ infoForm.errors.name }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.email }}</label>
            <input
              v-model="infoForm.email"
              type="email"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
              :class="{ 'border-red-400 focus:ring-red-400': infoForm.errors.email }"
            />
            <p v-if="infoForm.errors.email" class="text-xs text-red-500 mt-1">{{ infoForm.errors.email }}</p>
          </div>
          <button
            type="submit"
            :disabled="infoForm.processing"
            class="bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-xs font-semibold px-5 py-2.5 rounded-xl transition-colors shadow-sm"
          >
            {{ infoForm.processing ? tr.saving : tr.saveChanges }}
          </button>
        </form>
      </div>

      <!-- Change Password -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center text-base">🔒</span>
          {{ tr.changePassword }}
        </h2>
        <form @submit.prevent="savePassword" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.currentPassword }}</label>
            <input
              v-model="passForm.current_password"
              type="password"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
              :class="{ 'border-red-400 focus:ring-red-400': passForm.errors.current_password }"
            />
            <p v-if="passForm.errors.current_password" class="text-xs text-red-500 mt-1">{{ passForm.errors.current_password }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.newPassword }}</label>
            <input
              v-model="passForm.password"
              type="password"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
              :class="{ 'border-red-400 focus:ring-red-400': passForm.errors.password }"
            />
            <p v-if="passForm.errors.password" class="text-xs text-red-500 mt-1">{{ passForm.errors.password }}</p>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.confirmPassword }}</label>
            <input
              v-model="passForm.password_confirmation"
              type="password"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
            />
          </div>
          <button
            type="submit"
            :disabled="passForm.processing"
            class="bg-gray-600 dark:bg-gray-700 hover:bg-gray-700 dark:hover:bg-gray-600 disabled:opacity-50 text-white text-xs font-semibold px-5 py-2.5 rounded-xl transition-colors shadow-sm"
          >
            {{ passForm.processing ? tr.changing : tr.changePasswordBtn }}
          </button>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
