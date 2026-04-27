<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import { computed, reactive, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Row {
  id: number; amount: number; currency: string | null; provider: string; status: string
  provider_transaction_id: string | null; provider_reference: string | null
  failure_reason: string | null; retry_count: number; refund_amount: number | null
  completed_at: string | null; failed_at: string | null; created_at: string | null
  booking: { id: number; booking_code: string; user: { id: number; name: string; phone_number: string | null } | null; venue: { id: number; name: Translated } | null } | null
}
interface Stats { total: number; completed: number; failed: number; pending: number; refunded: number; total_revenue: number; today_revenue: number }

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
  search: props.filters.search ?? '',
  status: props.filters.status ?? '',
  provider: props.filters.provider ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  amount_min: props.filters.amount_min ?? '',
  amount_max: props.filters.amount_max ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/payments', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function t(name: Translated) {
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }

function statusTone(s: string) {
  if (s === 'completed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'failed') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  if (s === 'refunded') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'pending' || s === 'processing') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

const statCards = computed(() => [
  { label: tr.value.paymentStatTotal, value: props.stats.total },
  { label: tr.value.paymentStatCompleted, value: props.stats.completed, tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.paymentStatFailed, value: props.stats.failed, tone: 'text-red-600 dark:text-red-400' },
  { label: tr.value.paymentStatPending, value: props.stats.pending, tone: 'text-amber-600 dark:text-amber-400' },
  { label: tr.value.paymentStatRevenue, value: fmt(props.stats.total_revenue), tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.paymentStatTodayRevenue, value: fmt(props.stats.today_revenue), tone: 'text-blue-600 dark:text-blue-400' },
])
</script>

<template>
  <Head :title="tr.payments" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.payments }}</h1>
        <div class="flex items-center gap-2">
          <Link href="/admin/payments/failed" class="px-3 py-2 text-sm font-medium bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl text-red-600 dark:text-red-400 hover:bg-red-100 transition-colors">{{ tr.paymentFailedLogLink }}</Link>
          <a href="/admin/payments/export" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.paymentExport }}</a>
        </div>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div v-for="(s, i) in statCards" :key="i" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.label }}</div>
          <div :class="s.tone ?? 'text-gray-900 dark:text-white'" class="text-xl sm:text-2xl font-bold mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input v-model="form.search" type="text" :placeholder="tr.paymentSearchPlaceholder" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <select v-model="form.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.paymentStatusAll }}</option>
          <option v-for="s in options.statuses" :key="s" :value="s">{{ tr['paymentStatus_' + s] ?? s }}</option>
        </select>
        <select v-model="form.provider" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.paymentProviderAll }}</option>
          <option v-for="p in options.providers" :key="p" :value="p">{{ tr['paymentProvider_' + p] ?? p }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.paymentDateFrom }}</label><input v-model="form.date_from" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
          <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.paymentDateTo }}</label><input v-model="form.date_to" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentId }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentBookingCode }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.bookingPlayer }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentProvider }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentAmount }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentStatusHeader }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.paymentCreatedAt }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!payments.data.length">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <tr v-for="p in payments.data" :key="p.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" dir="ltr">#{{ p.id }}</td>
                <td class="px-4 py-3">
                  <Link v-if="p.booking" :href="`/admin/bookings/${p.booking.id}`" class="text-emerald-600 dark:text-emerald-400 hover:underline" dir="ltr">{{ p.booking.booking_code }}</Link>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ p.booking?.user?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ tr['paymentProvider_' + p.provider] ?? p.provider }}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmt(p.amount) }}</td>
                <td class="px-4 py-3"><span :class="statusTone(p.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">{{ tr['paymentStatus_' + p.status] ?? p.status }}</span></td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(p.created_at) }}</td>
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
