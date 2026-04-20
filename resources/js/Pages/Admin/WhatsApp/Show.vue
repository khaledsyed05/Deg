<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Props {
  club: { id: number; name: string }
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
  router.post(`/admin/whatsapp/clubs/${props.club.id}/init`, {}, {
    onFinish: () => { connecting.value = false },
  })
}

function disconnect() {
  if (confirm(tr.value.disconnectConfirm)) {
    router.delete(`/admin/whatsapp/clubs/${props.club.id}`)
  }
}

function refreshStatus() {
  router.reload({ only: ['status'] })
}
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
