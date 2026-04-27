<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, reactive } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface TrendRow { bucket: string; label_ar: string; label_en: string; confirmed: number; completed: number; cancelled: number; total: number }
interface PeakHour { hour: number; bookings: number }
interface VenueRow { id: number; slug: string; name: Translated; club_name: Translated; city_name: string | null; city_name_ar: string | null; bookings_count: number; avg_rating: number | null; occupancy_rate: number }
interface SportRow { id: number; name: Translated; bookings_count: number; percentage: number; avg_duration_hours: number; avg_price: number }
interface BucketRow { key: string; count: number }
interface CancelReason { reason: string; count: number; percentage: number }
interface Overview {
  total_bookings: number; total_bookings_prev: number; total_bookings_change: number
  confirmed_bookings: number; confirmed_bookings_change: number
  completed_bookings: number; completed_bookings_change: number
  cancelled_bookings: number; cancelled_bookings_change: number
  cancellation_rate: number; cancellation_rate_change: number
  avg_duration_hours: number; avg_duration_change: number
}

interface Props {
  filters: { date_from: string; date_to: string; granularity: string }
  overview: Overview
  booking_trends: TrendRow[]
  peak_hours: PeakHour[]
  popular_venues: VenueRow[]
  sport_distribution: SportRow[]
  duration_buckets: BucketRow[]
  cancellation_reasons: CancelReason[]
  cancellation_timing: BucketRow[]
  advance_window: BucketRow[]
  avg_advance_days: number
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const dateForm = reactive({ date_from: props.filters.date_from, date_to: props.filters.date_to })

function applyRange() {
  router.get('/admin/analytics/bookings', dateForm, { preserveState: true, preserveScroll: true, replace: true })
}
function quickRange(key: string) {
  const now = new Date()
  const pad = (n: number) => String(n).padStart(2, '0')
  const fmtD = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`
  let from = new Date(); const to = fmtD(now)
  if (key === 'today') { from = now }
  else if (key === 'week') { from.setDate(now.getDate() - 6) }
  else if (key === 'month') { from = new Date(now.getFullYear(), now.getMonth(), 1) }
  else if (key === '30d') { from.setDate(now.getDate() - 29) }
  else if (key === '3m') { from.setDate(now.getDate() - 89) }
  else if (key === 'year') { from.setDate(now.getDate() - 364) }
  dateForm.date_from = fmtD(from); dateForm.date_to = to
  applyRange()
}

function t(name: Translated) { return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—' }
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function changeTone(v: number) {
  if (v > 0) return 'text-emerald-600 dark:text-emerald-400'
  if (v < 0) return 'text-red-600 dark:text-red-400'
  return 'text-gray-400'
}

// Trend chart
const trendMax = computed(() => Math.max(...props.booking_trends.map(r => r.total), 1))
function barH(v: number, max: number) { return Math.round((v / max) * 100) }

// Peak hours chart
const peakMax = computed(() => Math.max(...props.peak_hours.map(r => r.bookings), 1))

// Duration buckets
const durMax = computed(() => Math.max(...props.duration_buckets.map(r => r.count), 1))
const timingMax = computed(() => Math.max(...props.cancellation_timing.map(r => r.count), 1))
const advMax = computed(() => Math.max(...props.advance_window.map(r => r.count), 1))

const overviewCards = computed(() => [
  { label: tr.value.bookingsOverviewTotal, value: fmt(props.overview.total_bookings), prev: fmt(props.overview.total_bookings_prev), change: props.overview.total_bookings_change },
  { label: tr.value.bookingsOverviewConfirmed, value: fmt(props.overview.confirmed_bookings), change: props.overview.confirmed_bookings_change },
  { label: tr.value.bookingsOverviewCompleted, value: fmt(props.overview.completed_bookings), change: props.overview.completed_bookings_change },
  { label: tr.value.bookingsOverviewCancelled, value: fmt(props.overview.cancelled_bookings), change: props.overview.cancelled_bookings_change },
  { label: tr.value.bookingsOverviewCancelRate, value: `${props.overview.cancellation_rate}%`, change: props.overview.cancellation_rate_change },
  { label: tr.value.bookingsOverviewAvgDuration, value: `${props.overview.avg_duration_hours}`, change: props.overview.avg_duration_change },
])
</script>

<template>
  <Head :title="tr.bookingsAnalyticsTitle" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.bookingsAnalyticsTitle }}</h1>
        <a :href="`/admin/analytics/bookings/export?date_from=${filters.date_from}&date_to=${filters.date_to}`" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.analyticsExport }}</a>
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
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
        <div v-for="(c, i) in overviewCards" :key="i" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ c.label }}</div>
          <div class="text-xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ c.value }}</div>
          <div v-if="c.change !== undefined && c.change !== 0" :class="changeTone(c.change)" class="text-xs font-medium mt-0.5">
            {{ c.change > 0 ? '↑' : '↓' }} {{ Math.abs(c.change) }}%
          </div>
        </div>
      </div>

      <!-- Booking trends chart -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 mb-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.bookingsTrendsTitle }}</h2>
          <div class="flex items-center gap-3 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-500 inline-block"></span>{{ tr.bookingsLegendConfirmed }}</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span>{{ tr.bookingsLegendCompleted }}</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-400 inline-block"></span>{{ tr.bookingsLegendCancelled }}</span>
          </div>
        </div>
        <div v-if="!booking_trends.length" class="text-sm text-gray-400 text-center py-8">{{ tr.bookingsAnalyticsEmpty }}</div>
        <div v-else class="flex items-end gap-1 h-40 overflow-x-auto pb-6">
          <div v-for="row in booking_trends" :key="row.bucket" class="flex flex-col items-center flex-shrink-0 gap-0.5"
            :style="`min-width: ${Math.max(24, Math.floor(300/booking_trends.length))}px`">
            <div class="flex items-end gap-0.5 w-full" style="height:120px">
              <div :style="`height: ${barH(row.confirmed, trendMax)}%; min-height: ${row.confirmed > 0 ? 3 : 0}px`" class="flex-1 bg-emerald-500 rounded-t-sm" :title="`${tr.bookingsLegendConfirmed}: ${row.confirmed}`"></div>
              <div :style="`height: ${barH(row.completed, trendMax)}%; min-height: ${row.completed > 0 ? 3 : 0}px`" class="flex-1 bg-blue-500 rounded-t-sm" :title="`${tr.bookingsLegendCompleted}: ${row.completed}`"></div>
              <div :style="`height: ${barH(row.cancelled, trendMax)}%; min-height: ${row.cancelled > 0 ? 3 : 0}px`" class="flex-1 bg-red-400 rounded-t-sm" :title="`${tr.bookingsLegendCancelled}: ${row.cancelled}`"></div>
            </div>
            <span class="text-xs text-gray-400 truncate w-full text-center">{{ isAr ? row.label_ar : row.label_en }}</span>
          </div>
        </div>
      </div>

      <!-- Peak Hours + Popular Venues -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        <!-- Peak hours -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.bookingsPeakHoursTitle }}</h2>
          <div class="flex items-end gap-1 h-28">
            <div v-for="h in peak_hours" :key="h.hour" class="flex flex-col items-center flex-1">
              <div :style="`height: ${barH(h.bookings, peakMax)}%; min-height: ${h.bookings > 0 ? 3 : 0}px`"
                class="w-full bg-amber-400 dark:bg-amber-500 rounded-t-sm"
                :title="`${h.hour}:00 — ${h.bookings}`"></div>
              <span class="text-xs text-gray-400 mt-0.5">{{ h.hour }}</span>
            </div>
          </div>
        </div>

        <!-- Sport distribution -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.bookingsSportDistTitle }}</h2>
          <div v-if="!sport_distribution.length" class="text-sm text-gray-400 text-center py-4">{{ tr.bookingsAnalyticsEmpty }}</div>
          <div v-else class="space-y-2">
            <div v-for="s in sport_distribution" :key="s.id">
              <div class="flex items-center justify-between text-xs mb-0.5">
                <span class="text-gray-700 dark:text-gray-300">{{ t(s.name) }}</span>
                <span class="text-gray-500" dir="ltr">{{ s.bookings_count }} ({{ s.percentage }}%)</span>
              </div>
              <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full">
                <div :style="`width: ${s.percentage}%`" class="h-full rounded-full bg-emerald-500"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Popular Venues -->
      <div v-if="popular_venues.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.bookingsPopularVenuesTitle }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">#</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueVenue }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.revenueClub }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.revenueBookingsCol }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.bookingsRating }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.bookingsOccupancyRate }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(v, i) in popular_venues" :key="v.id" class="border-t border-gray-100 dark:border-gray-800">
                <td class="px-4 py-3 text-gray-500" dir="ltr">{{ i + 1 }}</td>
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ t(v.name) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ t(v.club_name) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.bookings_count }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-amber-500" dir="ltr">{{ v.avg_rating !== null ? v.avg_rating : '—' }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.occupancy_rate }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Cancellation analysis -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        <!-- Cancel reasons -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.bookingsCancelReasonsTitle }}</h2>
          <div v-if="!cancellation_reasons.length" class="text-sm text-gray-400 text-center py-4">{{ tr.bookingsAnalyticsEmpty }}</div>
          <div v-else class="space-y-2">
            <div v-for="r in cancellation_reasons" :key="r.reason">
              <div class="flex items-center justify-between text-xs mb-0.5">
                <span class="text-gray-700 dark:text-gray-300 truncate flex-1">{{ r.reason }}</span>
                <span class="text-gray-500 ms-2" dir="ltr">{{ r.count }} ({{ r.percentage }}%)</span>
              </div>
              <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full">
                <div :style="`width: ${r.percentage}%`" class="h-full rounded-full bg-red-400"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cancel timing -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.bookingsCancelTimingTitle }}</h2>
          <div class="flex items-end gap-2 h-28">
            <div v-for="b in cancellation_timing" :key="b.key" class="flex flex-col items-center flex-1">
              <div :style="`height: ${barH(b.count, timingMax)}%; min-height: ${b.count > 0 ? 3 : 0}px`"
                class="w-full bg-red-400 dark:bg-red-500 rounded-t-sm"
                :title="`${(tr.cancelTimingBucket as any)?.[b.key] ?? b.key}: ${b.count}`"></div>
              <span class="text-xs text-gray-400 mt-0.5 text-center leading-tight">{{ (tr.cancelTimingBucket as any)?.[b.key]?.split(' ')[0] ?? b.key }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Duration + Advance window -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <!-- Duration buckets -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.bookingsDurationTitle }}</h2>
          <div class="flex items-end gap-2 h-28">
            <div v-for="b in duration_buckets" :key="b.key" class="flex flex-col items-center flex-1">
              <div :style="`height: ${barH(b.count, durMax)}%; min-height: ${b.count > 0 ? 3 : 0}px`"
                class="w-full bg-blue-500 dark:bg-blue-600 rounded-t-sm"
                :title="`${(tr.durationBucket as any)?.[b.key] ?? b.key}: ${b.count}`"></div>
              <span class="text-xs text-gray-400 mt-0.5 text-center leading-tight">{{ (tr.durationBucket as any)?.[b.key]?.replace('ساعة','س').replace('ساعات','س').replace('أقل من ','<') ?? b.key }}</span>
            </div>
          </div>
        </div>

        <!-- Advance window -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <div class="flex items-start justify-between mb-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.bookingsAdvanceWindowTitle }}</h2>
            <span class="text-xs text-gray-400">{{ (tr.bookingsAvgAdvance as any)?.(avg_advance_days) }}</span>
          </div>
          <div class="flex items-end gap-1 h-28">
            <div v-for="b in advance_window" :key="b.key" class="flex flex-col items-center flex-1">
              <div :style="`height: ${barH(b.count, advMax)}%; min-height: ${b.count > 0 ? 3 : 0}px`"
                class="w-full bg-purple-500 dark:bg-purple-600 rounded-t-sm"
                :title="`${(tr.advanceWindowBucket as any)?.[b.key] ?? b.key}: ${b.count}`"></div>
              <span class="text-xs text-gray-400 mt-0.5 text-center leading-tight text-[10px]">{{ (tr.advanceWindowBucket as any)?.[b.key]?.substring(0,6) ?? b.key }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
