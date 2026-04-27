<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'

interface Stats {
  today_bookings: number
  total_slots: number
  occupancy_rate: number
  today_revenue: number
  upcoming_bookings: number
  unpaid_bookings: number
}

interface ScheduleItem {
  id: number
  venue_name: string
  start_time: string
  end_time: string
  price: number
  status: string | null
  deposit_status: string | null
  player_name: string
}

const props = defineProps<{
  stats: Stats
  schedule: ScheduleItem[]
  today_iso: string
  today_day_key: string
}>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const isAr = computed(() => locale.value === 'ar')

function fmt(n: number) {
  return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n)
}

function statusTone(s: string | null) {
  if (s === 'confirmed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'scheduled') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'completed') return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
  if (s === 'cancelled') return 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-500'
}

function statusLabel(s: string | null) {
  const map: Record<string, string> = {
    confirmed: isAr.value ? 'مؤكد' : 'Confirmed',
    scheduled: isAr.value ? 'مجدول' : 'Scheduled',
    completed: isAr.value ? 'مكتمل' : 'Completed',
    cancelled: isAr.value ? 'ملغي' : 'Cancelled',
  }
  return s ? (map[s] ?? s) : '—'
}

const todayLabel = computed(() => {
  try {
    return new Date(props.today_iso).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US', {
      weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    })
  } catch { return props.today_iso }
})

const statCards = computed(() => [
  {
    label: isAr.value ? 'حجوزات اليوم' : "Today's Bookings",
    value: props.stats.today_bookings,
    tone: 'text-blue-600 dark:text-blue-400',
    icon: '📅',
  },
  {
    label: isAr.value ? 'نسبة الإشغال' : 'Occupancy Rate',
    value: props.stats.occupancy_rate + '%',
    tone: props.stats.occupancy_rate >= 70 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400',
    icon: '📊',
  },
  {
    label: isAr.value ? 'إيرادات اليوم' : "Today's Revenue",
    value: fmt(props.stats.today_revenue),
    tone: 'text-emerald-600 dark:text-emerald-400',
    icon: '💰',
  },
  {
    label: isAr.value ? 'حجوزات قادمة' : 'Upcoming Bookings',
    value: props.stats.upcoming_bookings,
    tone: 'text-purple-600 dark:text-purple-400',
    icon: '⏳',
  },
  {
    label: isAr.value ? 'غير مدفوعة' : 'Unpaid Today',
    value: props.stats.unpaid_bookings,
    tone: props.stats.unpaid_bookings > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400',
    icon: '⚠️',
  },
])
</script>

<template>
  <Head :title="isAr ? 'الرئيسية' : 'Dashboard'" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ isAr ? 'لوحة التحكم' : 'Dashboard' }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ todayLabel }}</p>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <div
          v-for="(card, i) in statCards"
          :key="i"
          class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4"
        >
          <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
            <span>{{ card.icon }}</span>
            <span>{{ card.label }}</span>
          </div>
          <div :class="card.tone" class="text-2xl font-bold" dir="ltr">{{ card.value }}</div>
        </div>
      </div>

      <!-- Today's Schedule -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
          <h2 class="font-semibold text-gray-900 dark:text-white">{{ isAr ? 'جدول اليوم' : "Today's Schedule" }}</h2>
          <span class="text-xs text-gray-500 dark:text-gray-400">{{ schedule.length }} {{ isAr ? 'حجز' : 'bookings' }}</span>
        </div>

        <div v-if="!schedule.length" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400 text-sm">
          {{ isAr ? 'لا توجد حجوزات اليوم' : 'No bookings today' }}
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ isAr ? 'الوقت' : 'Time' }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ isAr ? 'الملعب' : 'Venue' }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ isAr ? 'اللاعب' : 'Player' }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ isAr ? 'السعر' : 'Price' }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ isAr ? 'الحالة' : 'Status' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in schedule"
                :key="item.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3 text-gray-900 dark:text-white font-mono text-xs" dir="ltr">
                  {{ item.start_time }}–{{ item.end_time }}
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ item.venue_name }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ item.player_name }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(item.price) }}</td>
                <td class="px-4 py-3">
                  <span :class="statusTone(item.status)" class="text-xs px-2 py-1 rounded-lg">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
