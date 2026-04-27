<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, reactive } from 'vue'
import { useI18n } from '@/i18n'

interface FunnelRow { step: string; count: number }
interface DropoffRow { stage: string; count: number }
interface SegRow { segment: string; player_count: number; percentage: number; total_bookings: number; total_revenue: number; avg_booking_value: number }
interface TierRow { tier: string; player_count: number; percentage: number; total_revenue: number; revenue_percentage: number; avg_spending: number }
interface CohortRow { month: string; size: number; [key: string]: any }
interface EventRow { id: number; player_id: number | null; player_name: string | null; event_name: string; properties: any; occurred_at: string; session_id: string | null; anonymous_id: string | null }
interface Overview {
  total_players: number; total_players_change: number
  active_players: number; active_players_change: number
  new_players: number; new_players_change: number
  churned_players: number; retention_rate: number
  avg_bookings_per_player: number
}

interface Props {
  filters: { date_from: string; date_to: string; event_name: string }
  overview: Overview
  funnel: FunnelRow[]
  dropoff: DropoffRow[]
  segmentation: SegRow[]
  spending_tiers: TierRow[]
  cohorts: CohortRow[]
  events: EventRow[]
  event_name_options: string[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const dateForm = reactive({
  date_from: props.filters.date_from,
  date_to: props.filters.date_to,
  event_name: props.filters.event_name,
})

function applyRange() {
  router.get('/admin/analytics/players', dateForm, { preserveState: true, preserveScroll: true, replace: true })
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

function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }
function changeTone(v: number) {
  if (v > 0) return 'text-emerald-600 dark:text-emerald-400'
  if (v < 0) return 'text-red-600 dark:text-red-400'
  return 'text-gray-400'
}

// Funnel
const funnelMax = computed(() => props.funnel[0]?.count ?? 1)

const overviewCards = computed(() => [
  { label: tr.value.playersOverviewTotal, value: fmt(props.overview.total_players), change: props.overview.total_players_change },
  { label: tr.value.playersOverviewActive, value: fmt(props.overview.active_players), change: props.overview.active_players_change },
  { label: tr.value.playersOverviewNew, value: fmt(props.overview.new_players), change: props.overview.new_players_change },
  { label: tr.value.playersOverviewChurned, value: fmt(props.overview.churned_players), change: 0 },
  { label: tr.value.playersOverviewRetention, value: `${props.overview.retention_rate}%`, change: 0 },
  { label: tr.value.playersOverviewAvgBookings, value: props.overview.avg_bookings_per_player.toString(), change: 0 },
])

const segColors = ['bg-emerald-500', 'bg-blue-500', 'bg-amber-400', 'bg-purple-500', 'bg-red-400']
const tierColors = ['bg-gray-400', 'bg-blue-500', 'bg-purple-500', 'bg-amber-500']

const cohortMonths = [0, 1, 2, 3, 4, 5, 6]
function cohortCell(row: CohortRow, m: number): string {
  const v = row[`month_${m}`]
  if (v === null || v === undefined) return ''
  return `${v}%`
}
function cohortCellBg(row: CohortRow, m: number): string {
  const v = row[`month_${m}`]
  if (v === null || v === undefined) return 'bg-gray-50 dark:bg-gray-900/50'
  const pct = Math.min(100, Math.max(0, v))
  if (pct >= 80) return 'bg-emerald-600 text-white'
  if (pct >= 60) return 'bg-emerald-400 text-white'
  if (pct >= 40) return 'bg-emerald-200 dark:bg-emerald-900/60 text-emerald-900 dark:text-emerald-200'
  if (pct >= 20) return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-500'
}
</script>

<template>
  <Head :title="tr.playersAnalyticsTitle" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.playersAnalyticsTitle }}</h1>
        <a :href="`/admin/analytics/players/export?date_from=${filters.date_from}&date_to=${filters.date_to}`" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.analyticsExport }}</a>
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
          <div v-if="c.change !== 0" :class="changeTone(c.change)" class="text-xs font-medium mt-0.5">
            {{ c.change > 0 ? '↑' : '↓' }} {{ Math.abs(c.change) }}%
          </div>
        </div>
      </div>

      <!-- Funnel + Dropoff -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        <!-- Funnel -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm">{{ tr.playersFunnelTitle }}</h2>
          <div class="space-y-2">
            <div v-for="(step, i) in funnel" :key="step.step">
              <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-gray-700 dark:text-gray-300">{{ (tr.funnelStep as any)?.[step.step] ?? step.step }}</span>
                <div class="flex items-center gap-2">
                  <span class="font-medium text-gray-900 dark:text-white" dir="ltr">{{ fmt(step.count) }}</span>
                  <span class="text-gray-400" dir="ltr">
                    {{ funnelMax > 0 ? Math.round((step.count / funnelMax) * 100) : 0 }}%
                  </span>
                </div>
              </div>
              <div class="h-6 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                <div :style="`width: ${funnelMax > 0 ? (step.count / funnelMax) * 100 : 0}%`"
                  class="h-full bg-emerald-500 dark:bg-emerald-600 rounded-lg transition-all flex items-center justify-end pe-2">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Dropoff -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm">{{ tr.playersDropoffTitle }}</h2>
          <div class="space-y-3">
            <div v-for="d in dropoff" :key="d.stage" class="flex items-center justify-between text-sm">
              <span class="text-gray-600 dark:text-gray-400 text-xs">{{ (tr.dropoffStage as any)?.[d.stage] ?? d.stage }}</span>
              <span class="font-semibold text-red-600 dark:text-red-400" dir="ltr">{{ fmt(d.count) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Segmentation + Spending tiers -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
        <!-- Segmentation -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm">{{ tr.playersSegmentationTitle }}</h2>
          <div class="space-y-3">
            <div v-for="(seg, i) in segmentation" :key="seg.segment" class="flex items-center gap-3">
              <span :class="segColors[i % segColors.length]" class="w-2.5 h-2.5 rounded-full flex-shrink-0"></span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between text-xs mb-0.5">
                  <span class="text-gray-700 dark:text-gray-300 truncate">{{ (tr.segmentLabel as any)?.[seg.segment] ?? seg.segment }}</span>
                  <span class="text-gray-500 ms-2" dir="ltr">{{ seg.player_count }} ({{ seg.percentage }}%)</span>
                </div>
                <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full">
                  <div :style="`width: ${seg.percentage}%`" :class="segColors[i % segColors.length]" class="h-full rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Spending tiers -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm">{{ tr.playersSpendingTiersTitle }}</h2>
          <div class="space-y-3">
            <div v-for="(tier, i) in spending_tiers" :key="tier.tier" class="flex items-center gap-3">
              <span :class="tierColors[i % tierColors.length]" class="w-2.5 h-2.5 rounded-full flex-shrink-0"></span>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between text-xs mb-0.5">
                  <span class="text-gray-700 dark:text-gray-300 truncate">{{ (tr.tierLabel as any)?.[tier.tier] ?? tier.tier }}</span>
                  <span class="text-gray-500 ms-2" dir="ltr">{{ tier.player_count }} ({{ tier.percentage }}%)</span>
                </div>
                <div class="h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full">
                  <div :style="`width: ${tier.percentage}%`" :class="tierColors[i % tierColors.length]" class="h-full rounded-full"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Cohort table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.playersCohortTitle }}</h2>
        </div>
        <div v-if="!cohorts.length" class="px-5 py-6 text-sm text-gray-400 text-center">{{ tr.playersCohortEmpty }}</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead class="bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-3 py-2 text-start font-medium">{{ tr.playersCohortMonthCol }}</th>
                <th class="px-3 py-2 text-start font-medium">{{ tr.playersCohortSizeCol }}</th>
                <th v-for="m in cohortMonths" :key="m" class="px-3 py-2 text-center font-medium">M{{ m }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in cohorts" :key="row.month" class="border-t border-gray-100 dark:border-gray-800">
                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 font-medium" dir="ltr">{{ row.month }}</td>
                <td class="px-3 py-2 text-gray-600 dark:text-gray-400" dir="ltr">{{ row.size }}</td>
                <td v-for="m in cohortMonths" :key="m" class="px-1 py-1 text-center">
                  <span :class="cohortCellBg(row, m)" class="inline-block px-2 py-0.5 rounded text-[11px] min-w-10">
                    {{ cohortCell(row, m) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Events log -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 flex-wrap">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.playersEventsTitle }}</h2>
          <select v-model="dateForm.event_name" @change="applyRange" class="px-3 py-1.5 text-xs bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none">
            <option value="">{{ tr.playersEventAll }}</option>
            <option v-for="e in event_name_options" :key="e" :value="e">{{ e }}</option>
          </select>
        </div>
        <div v-if="!events.length" class="px-5 py-6 text-sm text-gray-400 text-center">{{ tr.playersEventsEmpty }}</div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.playersEventName }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playersEventPlayer }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.playersEventTime }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playersEventSession }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="e in events" :key="e.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" dir="ltr">{{ e.event_name }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ e.player_name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs" dir="ltr">{{ fmtDate(e.occurred_at) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-400 text-xs truncate max-w-32" dir="ltr">{{ e.session_id ?? e.anonymous_id ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
