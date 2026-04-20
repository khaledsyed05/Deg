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
    icon: '👥',
  },
  {
    label: tr.value.totalClubs,
    value: props.stats.total_clubs,
    color: 'text-purple-600 dark:text-purple-400',
    bg: 'bg-purple-50 dark:bg-purple-900/20',
    icon: '🏆',
  },
  {
    label: tr.value.pendingClubsCount,
    value: props.stats.pending_clubs,
    color: 'text-amber-600 dark:text-amber-400',
    bg: 'bg-amber-50 dark:bg-amber-900/20',
    icon: '⏳',
  },
  {
    label: tr.value.totalBookings,
    value: props.stats.total_bookings,
    color: 'text-indigo-600 dark:text-indigo-400',
    bg: 'bg-indigo-50 dark:bg-indigo-900/20',
    icon: '📅',
  },
  {
    label: tr.value.confirmedBookings,
    value: props.stats.confirmed_bookings,
    color: 'text-emerald-600 dark:text-emerald-400',
    bg: 'bg-emerald-50 dark:bg-emerald-900/20',
    icon: '✅',
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
            <span
              class="text-xl w-9 h-9 rounded-xl flex items-center justify-center"
              :class="card.bg"
            >{{ card.icon }}</span>
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
          <span class="text-base">📈</span>
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
          <span class="text-xl mt-0.5">⚠️</span>
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
