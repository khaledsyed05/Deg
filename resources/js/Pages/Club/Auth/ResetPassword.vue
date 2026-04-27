<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

const props = defineProps<{ phone: string }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const isRtl = computed(() => locale.value === 'ar')

const form = useForm({
  phone_number: props.phone,
  otp: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post('/club/reset-password', { onFinish: () => form.reset('password', 'password_confirmation') })
}
</script>

<template>
  <Head title="إعادة تعيين كلمة المرور — النادي" />

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
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">دق احجزلي</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">بوابة الأندية</p>
      </div>

      <form
        @submit.prevent="submit"
        class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 p-6 space-y-4"
      >
        <div>
          <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-1">إعادة تعيين كلمة المرور</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400">أدخل الرمز الذي أُرسل إلى <span dir="ltr" class="font-mono">{{ phone }}</span></p>
        </div>

        <!-- Phone: readonly -->
        <input type="hidden" v-model="form.phone_number" />

        <!-- OTP -->
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
            رمز التحقق (OTP)
          </label>
          <input
            v-model="form.otp"
            type="text"
            inputmode="numeric"
            maxlength="6"
            dir="ltr"
            placeholder="123456"
            autocomplete="one-time-code"
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm text-center tracking-widest font-mono focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400"
            :class="{ 'border-red-400 focus:ring-red-400': form.errors.otp }"
          />
          <p v-if="form.errors.otp" class="text-xs text-red-500 mt-1">{{ form.errors.otp }}</p>
        </div>

        <!-- New password -->
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
            كلمة المرور الجديدة
          </label>
          <input
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
            :class="{ 'border-red-400 focus:ring-red-400': form.errors.password }"
          />
          <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
        </div>

        <!-- Confirm password -->
        <div>
          <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
            تأكيد كلمة المرور
          </label>
          <input
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
            :class="{ 'border-red-400 focus:ring-red-400': form.errors.password_confirmation }"
          />
          <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ form.errors.password_confirmation }}</p>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors shadow-sm"
        >
          {{ form.processing ? 'جارٍ الحفظ...' : 'تعيين كلمة المرور' }}
        </button>
      </form>

      <div class="text-center mt-4">
        <a href="/club/login" class="text-xs text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
          العودة إلى تسجيل الدخول
        </a>
      </div>
    </div>
  </div>
</template>
