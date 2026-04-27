<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface RevenueMonth {
  month: string
  revenue: number
}

interface Props {
  stats: {
    total_users: number
    total_clubs: number
    pending_clubs: number
    total_bookings: number
    confirmed_bookings: number
  }
  revenue_by_month: RevenueMonth[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const { t } = useI18n(locale.value)
const tr = computed(() => useI18n(locale.value).t)

const statCards = computed(() => [
  {
    label: tr.value.totalUsers,
    value: props.stats.total_users,
    color: 'text-blue-600 dark:text-blue-400',
    bg: 'bg-blue-50 dark:bg-blue-900/20',
    iconStroke: 'text-blue-500 dark:text-blue-400',
    iconPath: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
  },
  {
    label: tr.value.totalClubs,
    value: props.stats.total_clubs,
    color: 'text-purple-600 dark:text-purple-400',
    bg: 'bg-purple-50 dark:bg-purple-900/20',
    iconStroke: 'text-purple-500 dark:text-purple-400',
    iconPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
  },
  {
    label: tr.value.pendingClubsCount,
    value: props.stats.pending_clubs,
    color: 'text-amber-600 dark:text-amber-400',
    bg: 'bg-amber-50 dark:bg-amber-900/20',
    iconStroke: 'text-amber-500 dark:text-amber-400',
    iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  {
    label: tr.value.totalBookings,
    value: props.stats.total_bookings,
    color: 'text-indigo-600 dark:text-indigo-400',
    bg: 'bg-indigo-50 dark:bg-indigo-900/20',
    iconStroke: 'text-indigo-500 dark:text-indigo-400',
    iconPath: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
  },
  {
    label: tr.value.confirmedBookings,
    value: props.stats.confirmed_bookings,
    color: 'text-emerald-600 dark:text-emerald-400',
    bg: 'bg-emerald-50 dark:bg-emerald-900/20',
    iconStroke: 'text-emerald-500 dark:text-emerald-400',
    iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
])

const maxRevenue = computed(() => Math.max(...props.revenue_by_month.map((r) => r.revenue), 1))
</script>

<template>
  <Head :title="tr.controlPanel" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-6xl">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        {{ tr.controlPanel }}
      </h1>

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <div
          v-for="card in statCards"
          :key="card.label"
          class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="flex items-start justify-between mb-3">
            <span class="w-9 h-9 rounded-xl flex items-center justify-center" :class="[card.bg, card.iconStroke]">
              <svg class="w-4.5 h-4.5 w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" :d="card.iconPath" />
              </svg>
            </span>
          </div>
          <div class="text-2xl font-bold mb-0.5" :class="card.color">
            {{ card.value.toLocaleString() }}
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400 font-medium leading-snug">
            {{ card.label }}
          </div>
        </div>
      </div>

      <!-- Revenue Chart -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 shadow-sm mb-4">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-5 flex items-center gap-2">
          <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          {{ tr.revenue6Months }}
        </h2>
        <div v-if="revenue_by_month.length === 0" class="h-40 flex items-center justify-center text-gray-400 dark:text-gray-600 text-sm">
          {{ tr.noRevenueData }}
        </div>
        <div v-else class="h-40 flex items-end gap-1.5 sm:gap-3">
          <div
            v-for="item in revenue_by_month"
            :key="item.month"
            class="flex-1 flex flex-col items-center gap-1 group"
          >
            <div class="text-xs text-gray-400 dark:text-gray-500 font-medium opacity-0 group-hover:opacity-100 transition-opacity truncate max-w-full">
              {{ item.revenue.toLocaleString() }}
            </div>
            <div
              class="w-full bg-emerald-500 dark:bg-emerald-600 hover:bg-emerald-400 rounded-t-lg transition-all cursor-default"
              :style="{ height: `${(item.revenue / maxRevenue) * 130}px`, minHeight: '6px' }"
            />
            <div class="text-xs text-gray-400 dark:text-gray-500 truncate max-w-full">{{ item.month }}</div>
          </div>
        </div>
      </div>

      <!-- Pending clubs alert -->
      <div
        v-if="stats.pending_clubs > 0"
        class="bg-amber-50 dark:bg-amber-900/15 border border-amber-200 dark:border-amber-800 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
      >
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div>
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-300">
              {{ tr.pendingApproval(stats.pending_clubs) }}
            </p>
            <p class="text-xs text-amber-700 dark:text-amber-400 mt-0.5">
              {{ tr.reviewClubsAlert }}
            </p>
          </div>
        </div>
        <a
          href="/admin/clubs"
          class="shrink-0 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm"
        >
          {{ tr.reviewClubs }}
        </a>
      </div>
    </div>
  </AdminLayout>
</template>
