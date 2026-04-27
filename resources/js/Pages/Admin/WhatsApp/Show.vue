<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Props {
  club: { id: number; slug: string; name: string }
  status: { status: string; qr_code?: string }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)

const qrCode = computed(() => {
  const flash = page.props.flash as Record<string, string | null>
  return (flash?.qr_code as string | null) ?? props.status.qr_code ?? null
})

const isConnected = computed(() => props.status.status === 'connected')
const isPending = computed(() => props.status.status === 'pending' || !!qrCode.value)
const connecting = ref(false)

function initiateConnection() {
  connecting.value = true
  router.post(`/admin/whatsapp/clubs/${props.club.slug}/init`, {}, {
    onFinish: () => { connecting.value = false },
  })
}

function disconnect() {
  if (confirm(tr.value.disconnectConfirm)) {
    router.delete(`/admin/whatsapp/clubs/${props.club.slug}`)
  }
}

function refreshStatus() {
  router.reload({ only: ['status'] })
}

const testForm = useForm({ phone_number: '', message: '' })

function sendTest() {
  testForm.post(`/admin/whatsapp/clubs/${props.club.slug}/test-send`, {
    preserveScroll: true,
    onSuccess: () => { testForm.reset('message') },
  })
}

const flash = computed(() => {
  const raw = (page.props.flash as Record<string, string | null> | undefined) ?? undefined
  if (!raw?.flash_key) return undefined
  const label = (tr.value as Record<string, string>)[raw.flash_key] ?? raw.flash_key
  return { type: raw.flash_type ?? 'success', message: label }
})
</script>

<template>
  <Head :title="tr.whatsappTitle(club.name)" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-lg">
      <div class="flex items-center gap-2 mb-6 text-sm text-gray-500 dark:text-gray-400">
        <a href="/admin/clubs" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
          {{ tr.backToClubs }}
        </a>
        <span class="text-gray-300 dark:text-gray-700">/</span>
        <span class="text-gray-700 dark:text-gray-300">{{ tr.whatsappSettings }}</span>
      </div>

      <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
        {{ tr.whatsappTitle(club.name) }}
      </h1>

      <!-- Connected -->
      <div v-if="isConnected" class="bg-emerald-50 dark:bg-emerald-900/15 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5">
        <div class="flex items-start gap-3 mb-4">
          <span class="text-2xl">✅</span>
          <div>
            <p class="font-semibold text-emerald-800 dark:text-emerald-300">{{ tr.connected }}</p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400 mt-0.5">{{ tr.connectedDesc }}</p>
          </div>
        </div>
        <button
          @click="disconnect"
          class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors"
        >
          {{ tr.disconnect }}
        </button>
      </div>

      <div v-if="isConnected" class="mt-6 bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-1">{{ tr.testSendTitle }}</h2>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ tr.testSendSubtitleClub }}</p>

        <div v-if="flash?.type === 'success'" class="mb-3 text-xs px-3 py-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
          {{ flash.message }}
        </div>
        <div v-else-if="flash?.type === 'error'" class="mb-3 text-xs px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800">
          {{ flash.message }}
        </div>

        <form @submit.prevent="sendTest" class="space-y-3">
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.testSendPhone }}</label>
            <input
              v-model="testForm.phone_number"
              type="tel"
              placeholder="+963944123456"
              dir="ltr"
              required
              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
            <p v-if="testForm.errors.phone_number" class="text-xs text-red-500 mt-1">{{ testForm.errors.phone_number }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.testSendMessage }}</label>
            <textarea
              v-model="testForm.message"
              rows="4"
              :placeholder="tr.testSendMessagePlaceholder"
              required
              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
            ></textarea>
            <p v-if="testForm.errors.message" class="text-xs text-red-500 mt-1">{{ testForm.errors.message }}</p>
          </div>
          <button
            type="submit"
            :disabled="testForm.processing"
            class="w-full bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors"
          >
            {{ testForm.processing ? tr.testSendSending : tr.testSendSubmit }}
          </button>
        </form>
      </div>

      <!-- Pending / QR -->
      <div v-else-if="isPending" class="bg-amber-50 dark:bg-amber-900/15 border border-amber-200 dark:border-amber-800 rounded-2xl p-5">
        <div class="flex items-start gap-3 mb-4">
          <span class="text-2xl">📱</span>
          <div>
            <p class="font-semibold text-amber-800 dark:text-amber-300">{{ tr.pendingQr }}</p>
            <p class="text-sm text-amber-700 dark:text-amber-400 mt-0.5">{{ tr.pendingQrDesc }}</p>
          </div>
        </div>
        <img v-if="qrCode" :src="qrCode" alt="WhatsApp QR Code" class="w-52 h-52 border-4 border-white dark:border-gray-800 rounded-xl shadow-md mb-4" />
        <button
          @click="refreshStatus"
          class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors"
        >
          {{ tr.refreshStatus }}
        </button>
      </div>

      <!-- Disconnected -->
      <div v-else class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
        <div class="flex items-start gap-3 mb-4">
          <span class="text-2xl">📵</span>
          <div>
            <p class="font-semibold text-gray-700 dark:text-gray-300">{{ tr.disconnected }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ tr.disconnectedDesc }}</p>
          </div>
        </div>
        <button
          @click="initiateConnection"
          :disabled="connecting"
          class="bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors"
        >
          {{ connecting ? tr.connecting : tr.connectWhatsapp }}
        </button>
      </div>
    </div>
  </AdminLayout>
</template>
