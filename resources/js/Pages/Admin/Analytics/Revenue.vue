<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, reactive } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface TrendRow { bucket: string; label_ar: string; label_en: string; revenue: number; bookings: number }
interface VenueRow { id: number; slug: string; name: Translated; club_name: Translated; city_name: string | null; city_name_ar: string | null; bookings_count: number; total_revenue: number; avg_booking_value: number; percentage: number }
interface ClubRow { id: number; slug: string; name: Translated; venues_count: number; bookings_count: number; total_revenue: number; commission: number; net_amount: number; percentage: number }
interface CityRow { id: number; name: string; name_ar: string | null; venues_count: number; bookings_count: number; total_revenue: number; percentage: number }
interface SportRow { id: number; name: Translated; bookings_count: number; total_revenue: number; avg_booking_value: number; percentage: number }
interface PaymentRow { provider: string; bookings_count: number; total_amount: number; percentage: number }
interface HeatRow { day: number; hour: number; revenue: number; bookings: number }
interface Overview {
  total_revenue: number; total_revenue_prev: number; revenue_change: number
  total_commission: number; total_commission_prev: number; commission_change: number
  avg_booking_value: number; avg_booking_value_prev: number; avg_booking_change: number
  revenue_per_day: number; revenue_per_day_prev: number; revenue_per_day_change: number
  total_bookings: number; total_bookings_prev: number
}

interface Props {
  filters: { date_from: string; date_to: string; granularity: string }
  overview: Overview
  trend: TrendRow[]
  revenue_by_venue: VenueRow[]
  revenue_by_club: ClubRow[]
  revenue_by_city: CityRow[]
  revenue_by_sport: SportRow[]
  payment_methods: PaymentRow[]
  heatmap: HeatRow[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const dateForm = reactive({ date_from: props.filters.date_from, date_to: props.filters.date_to })

function applyRange() {
  router.get('/admin/analytics/revenue', dateForm, { preserveState: true, preserveScroll: true, replace: true })
}
function quickRange(key: string) {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  const fmt = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`
  let from = new Date(), to = fmt(now)
  if (key === 'today') { from = now }
  else if (key === 'week') { from = new Date(now); from.setDate(now.getDate() - 6) }
  else if (key === 'month') { from = new Date(now.getFullYear(), now.getMonth(), 1) }
  else if (key === '30d') { from = new Date(now); from.setDate(now.getDate() - 29) }
  else if (key === '3m') { from = new Date(now); from.setDate(now.getDate() - 89) }
  else if (key === 'year') { from = new Date(now); from.setDate(now.getDate() - 364) }
  dateForm.date_from = fmt(from)
  dateForm.date_to = to
  applyRange()
}

function t(name: Translated) { return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—' }
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }

function changeTone(v: number) {
  if (v > 0) return 'text-emerald-600 dark:text-emerald-400'
  if (v < 0) return 'text-red-600 dark:text-red-400'
  return 'text-gray-500 dark:text-gray-400'
}
function changeLabel(v: number) {
  if (v > 0) return `↑ ${v}%`
  if (v < 0) return `↓ ${Math.abs(v)}%`
  return '—'
}

// Trend chart
const trendMax = computed(() => Math.max(...props.trend.map(r => r.revenue), 1))
function barH(v: number) { return Math.round((v / trendMax.value) * 100) }

// Heatmap
const heatMax = computed(() => Math.max(...props.heatmap.map(r => r.revenue), 1))
const heatGrid = computed(() => {
  const grid: Record<string, number> = {}
  for (const row of props.heatmap) {
    grid[`${row.day}_${row.hour}`] = row.revenue
  }
  return grid
})
const days = [0, 1, 2, 3, 4, 5, 6]
const hours = Array.from({ length: 16 }, (_, i) => i + 8)

const overviewCards = computed(() => [
  { label: tr.value.revenueOverviewTotal, value: fmt(props.overview.total_revenue), prev: fmt(props.overview.total_revenue_prev), change: props.overview.revenue_change, tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.revenueOverviewCommission, value: fmt(props.overview.total_commission), prev: fmt(props.overview.total_commission_prev), change: props.overview.commission_change, tone: 'text-blue-600 dark:text-blue-400' },
  { label: tr.value.revenueOverviewAvgValue, value: fmt(props.overview.avg_booking_value), prev: fmt(props.overview.avg_booking_value_prev), change: props.overview.avg_booking_change, tone: 'text-purple-600 dark:text-purple-400' },
  { label: tr.value.revenueOverviewPerDay, value: fmt(props.overview.revenue_per_day), prev: fmt(props.overview.revenue_per_day_prev), change: props.overview.revenue_per_day_change, tone: 'text-amber-600 dark:text-amber-400' },
  { label: tr.value.revenueOverviewBookings, value: props.overview.total_bookings, prev: props.overview.total_bookings_prev, change: 0, tone: 'text-gray-900 dark:text-white' },
])
</script>

<template>
  <Head :title="tr.analyticsRevenue" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header + filters -->
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.analyticsRevenue }}</h1>
        <a :href="`/admin/analytics/revenue/export?date_from=${filters.date_from}&date_to=${filters.date_to}`" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.analyticsExport }}</a>
      </div>

      <!-- Date range -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 flex flex-wrap items-end gap-3">
        <div class="flex flex-wrap gap-2">
          <button v-for="k in ['today','week','month','30d','3m','year']" :key="k" type="button" @click="quickRange(k)" class="text-xs px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:text-emerald-600 rounded-lg transition-colors">
            {{ tr['analyticsFilter' + k.charAt(0).toUpperCase() + k.slice(1)] ?? k }}
          </button>
        </div>
        <div class="flex items-end gap-2 ms-auto">
          <div><label class="block text-xs text-gray-500 mb-1">{{ tr.analyticsDateFrom }}</label><input v-model="dateForm.date_from" type="date" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
          <div><label class="block text-xs text-gray-500 mb-1">{{ tr.analyticsDateTo }}</label><input v-model="dateForm.date_to" type="date" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
          <button @click="applyRange" class="px-4 py-2 text-sm font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700">{{ tr.analyticsApply }}</button>
        </div>
      </div>

      <!-- Overview cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-5">
        <div v-for="(c, i) in overviewCards" :key="i" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ c.label }}</div>
          <div :class="c.tone" class="text-xl font-bold mt-1" dir="ltr">{{ c.value }}</div>
          <div class="flex items-center gap-1 mt-1">
            <span class="text-xs text-gray-400">{{ c.prev }}</span>
            <span v-if="c.change !== 0" :class="changeTone(c.change)" class="text-xs font-medium">{{ changeLabel(c.change) }}</span>
          </div>
        </div>
      </div>

      <!-- Revenue trend chart -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 mb-5">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm">{{ tr.revenueTrendTitle }}</h2>
        <div v-if="!trend.length" class="text-sm text-gray-400 text-center py-8">{{ tr.revenueEmptyData }}</div>
        <div v-else class="flex items-end gap-1 h-40 overflow-x-auto pb-6">
          <div v-for="row in trend" :key="row.bucket" class="flex flex-col items-center flex-shrink-0" :style="`min-width: ${Math.max(24, Math.floor(300/trend.length))}px`">
            <div :style="`height: ${barH(row.revenue)}%; min-height: ${row.revenue > 0 ? 4 : 0}px`"
              class="w-full bg-emerald-500 dark:bg-emerald-600 rounded-t-sm transition-all hover:bg-emerald-400"
              :title="`${isAr ? row.label_ar : row.label_en}: ${fmt(row.revenue)}`"></div>
            <span class="text-xs text-gray-400 mt-1 truncate w-full text-center" :title="isAr ? row.label_ar : row.label_en">{{ isAr ? row.label_ar : row.label_en }}</span>
          </div>
        </div>
      </div>

      <!-- By Venue table -->
      <div v-if="revenue_by_venue.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.revenueByVenueTitle }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueRank }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueVenue }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueClub }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueBookingsCol }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueTotalCol }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueAvgCol }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenuePctCol }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(v, i) in revenue_by_venue" :key="v.id" class="border-t border-gray-100 dark:border-gray-800">
                <td class="px-4 py-3 text-gray-500" dir="ltr">{{ i + 1 }}</td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ t(v.name) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ t(v.club_name) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.bookings_count }}</td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" dir="ltr">{{ fmt(v.total_revenue) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(v.avg_booking_value) }}</td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <div class="h-1.5 rounded-full bg-emerald-200 dark:bg-emerald-900/40 flex-1 max-w-16">
                      <div :style="`width: ${v.percentage}%`" class="h-full rounded-full bg-emerald-500"></div>
                    </div>
                    <span class="text-xs text-gray-500" dir="ltr">{{ v.percentage }}%</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- By Club table -->
      <div v-if="revenue_by_club.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.revenueByClubTitle }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueRank }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueClub }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueVenuesCol }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueBookingsCol }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueTotalCol }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueCommissionCol }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueNetCol }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenuePctCol }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(c, i) in revenue_by_club" :key="c.id" class="border-t border-gray-100 dark:border-gray-800">
                <td class="px-4 py-3 text-gray-500" dir="ltr">{{ i + 1 }}</td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ t(c.name) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ c.venues_count }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ c.bookings_count }}</td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" dir="ltr">{{ fmt(c.total_revenue) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(c.commission) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(c.net_amount) }}</td>
                <td class="px-4 py-3 text-xs text-gray-500" dir="ltr">{{ c.percentage }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Bottom row: city + sport + payment methods -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
        <!-- By City -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.revenueByCityTitle }}</h2>
          <div v-if="!revenue_by_city.length" class="text-sm text-gray-400 text-center py-4">{{ tr.revenueEmptyData }}</div>
          <div v-else class="space-y-2">
            <div v-for="c in revenue_by_city" :key="c.id" class="flex items-center justify-between text-sm">
              <span class="text-gray-700 dark:text-gray-300 truncate flex-1">{{ isAr ? c.name_ar || c.name : c.name }}</span>
              <div class="flex items-center gap-2 ms-2">
                <span class="text-gray-900 dark:text-white font-medium text-xs" dir="ltr">{{ fmt(c.total_revenue) }}</span>
                <span class="text-gray-400 text-xs" dir="ltr">{{ c.percentage }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- By Sport -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.revenueBySportTitle }}</h2>
          <div v-if="!revenue_by_sport.length" class="text-sm text-gray-400 text-center py-4">{{ tr.revenueEmptyData }}</div>
          <div v-else class="space-y-2">
            <div v-for="s in revenue_by_sport" :key="s.id" class="flex items-center justify-between text-sm">
              <span class="text-gray-700 dark:text-gray-300 truncate flex-1">{{ t(s.name) }}</span>
              <div class="flex items-center gap-2 ms-2">
                <span class="text-gray-900 dark:text-white font-medium text-xs" dir="ltr">{{ fmt(s.total_revenue) }}</span>
                <span class="text-gray-400 text-xs" dir="ltr">{{ s.percentage }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.revenuePaymentMethodsTitle }}</h2>
          <div v-if="!payment_methods.length" class="text-sm text-gray-400 text-center py-4">{{ tr.revenueEmptyData }}</div>
          <div v-else class="space-y-2">
            <div v-for="p in payment_methods" :key="p.provider" class="flex items-center justify-between text-sm">
              <span class="text-gray-700 dark:text-gray-300">{{ tr['paymentProvider_' + p.provider] ?? p.provider }}</span>
              <div class="flex items-center gap-2 ms-2">
                <span class="text-gray-900 dark:text-white font-medium text-xs" dir="ltr">{{ fmt(p.total_amount) }}</span>
                <span class="text-gray-400 text-xs" dir="ltr">{{ p.percentage }}%</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Heatmap -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm">{{ tr.revenueHeatmapTitle }}</h2>
        <div v-if="!heatmap.length" class="text-sm text-gray-400 text-center py-4">{{ tr.heatmapNoData }}</div>
        <div v-else class="overflow-x-auto">
          <table class="text-xs">
            <thead>
              <tr>
                <th class="px-2 py-1 text-gray-400 font-normal w-10"></th>
                <th v-for="h in hours" :key="h" class="px-1 py-1 text-gray-400 font-normal text-center w-8">{{ h }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in days" :key="d">
                <td class="px-2 py-0.5 text-gray-500 whitespace-nowrap">{{ (tr.heatmapDayNames as any)?.[d] ?? d }}</td>
                <td v-for="h in hours" :key="h" class="px-0.5 py-0.5">
                  <div :title="`${fmt(heatGrid[d+'_'+h] ?? 0)}`"
                    :style="`opacity: ${heatGrid[d+'_'+h] ? Math.max(0.1, (heatGrid[d+'_'+h] / heatMax) * 0.9 + 0.1) : 0}`"
                    class="w-6 h-6 rounded-sm bg-emerald-500"></div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
