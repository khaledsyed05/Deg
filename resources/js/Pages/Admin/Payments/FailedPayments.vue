<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Row {
  id: number; amount: number; currency: string | null; provider: string; status: string
  provider_transaction_id: string | null; failure_reason: string | null; retry_count: number
  created_at: string | null
  booking: { id: number; booking_code: string; user: { id: number; name: string; phone_number: string | null } | null; venue: { id: number; name: Translated } | null } | null
}
interface Stats { total_failed: number; never_retried: number; multiple_retries: number; lost_revenue: number }

interface Props {
  payments: { data: Row[]; links: any[]; meta?: any }
  filters: Record<string, any>
  stats: Stats
  options: { statuses: string[]; providers: string[] }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  provider: props.filters.provider ?? '',
  retry_bucket: props.filters.retry_bucket ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/payments/failed', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function t(name: Translated) { return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—' }
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }

const selectedIds = ref<Set<number>>(new Set())
function toggleSelect(id: number) {
  const s = new Set(selectedIds.value)
  if (s.has(id)) s.delete(id); else s.add(id)
  selectedIds.value = s
}
function toggleAll() {
  if (selectedIds.value.size === props.payments.data.length) {
    selectedIds.value = new Set()
  } else {
    selectedIds.value = new Set(props.payments.data.map(p => p.id))
  }
}

const bulkProcessing = ref(false)
function bulkRetry() {
  if (!selectedIds.value.size) return
  bulkProcessing.value = true
  router.post('/admin/payments/bulk-retry', { payment_ids: [...selectedIds.value] }, {
    preserveScroll: true,
    onFinish: () => { bulkProcessing.value = false; selectedIds.value = new Set() },
  })
}

const statCards = computed(() => [
  { label: tr.value.failedStatTotal, value: props.stats.total_failed, tone: 'text-red-600 dark:text-red-400' },
  { label: tr.value.failedStatNeverRetried, value: props.stats.never_retried, tone: 'text-amber-600 dark:text-amber-400' },
  { label: tr.value.failedStatMultiple, value: props.stats.multiple_retries, tone: 'text-orange-600 dark:text-orange-400' },
  { label: tr.value.failedStatLostRevenue, value: fmt(props.stats.lost_revenue), tone: 'text-red-700 dark:text-red-300' },
])
</script>

<template>
  <Head :title="tr.failedPaymentsTitle" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <Link href="/admin/payments" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          </Link>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.failedPaymentsTitle }}</h1>
        </div>
        <button v-if="selectedIds.size > 0" @click="bulkRetry" :disabled="bulkProcessing" class="px-4 py-2 text-sm font-semibold bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-xl transition-colors">
          {{ tr.failedBulkRetry }} ({{ selectedIds.size }})
        </button>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div v-for="(s, i) in statCards" :key="i" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.label }}</div>
          <div :class="s.tone" class="text-xl sm:text-2xl font-bold mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <select v-model="form.provider" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.paymentProviderAll }}</option>
          <option v-for="p in options.providers" :key="p" :value="p">{{ tr['paymentProvider_' + p] ?? p }}</option>
        </select>
        <select v-model="form.retry_bucket" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.failedRetryAll }}</option>
          <option value="never">{{ tr.failedRetryNever }}</option>
          <option value="multiple">{{ tr.failedRetryMultiple }}</option>
        </select>
        <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.paymentDateFrom }}</label><input v-model="form.date_from" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
        <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.paymentDateTo }}</label><input v-model="form.date_to" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 w-10">
                  <input type="checkbox" :checked="selectedIds.size === payments.data.length && payments.data.length > 0" @change="toggleAll" class="rounded text-emerald-600 border-gray-300 dark:border-gray-700" />
                </th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentId }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentBookingCode }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.bookingPlayer }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentProvider }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentAmount }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.paymentRetryCount }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.paymentFailureReason }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!payments.data.length">
                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <tr v-for="p in payments.data" :key="p.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3"><input type="checkbox" :checked="selectedIds.has(p.id)" @change="toggleSelect(p.id)" class="rounded text-emerald-600 border-gray-300 dark:border-gray-700" /></td>
                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" dir="ltr">#{{ p.id }}</td>
                <td class="px-4 py-3">
                  <Link v-if="p.booking" :href="`/admin/bookings/${p.booking.id}`" class="text-emerald-600 dark:text-emerald-400 hover:underline" dir="ltr">{{ p.booking.booking_code }}</Link>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ p.booking?.user?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ tr['paymentProvider_' + p.provider] ?? p.provider }}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmt(p.amount) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ p.retry_count }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-red-600 dark:text-red-400 text-xs max-w-[160px] truncate">{{ p.failure_reason ?? '—' }}</td>
                <td class="px-4 py-3">
                  <ActionsMenu :dir="isAr ? 'rtl' : 'ltr'" :items="[
                    { label: tr.paymentView, href: `/admin/payments/${p.id}` },
                  ]" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="payments.links" :meta="payments.meta ?? payments" :showing-label="tr.geoPageShowing" />
      </div>
    </div>
  </AdminLayout>
</template>
