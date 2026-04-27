<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface BookingItem {
  id: number
  booking_code: string | null
  booking_date: string
  start_time: string
  end_time: string
  status: string
  venue: { name: string | { ar?: string; en?: string } }
  user: { name: string } | null
}

interface Props {
  view: string
  currentDate: string
  startDate: string
  endDate: string
  bookingsByDate: Record<string, BookingItem[]>
  venues?: { id: number; name: string }[]
  selectedVenue?: string | null
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

const grouped = computed(() =>
  Object.entries(props.bookingsByDate).sort(([a], [b]) => a.localeCompare(b))
)

function venueName(v: BookingItem['venue']) {
  if (!v) return '—'
  if (typeof v.name === 'string') return v.name
  return (locale.value === 'ar' ? v.name.ar : v.name.en) || v.name.ar || v.name.en || '—'
}

function statusTone(s: string) {
  if (s === 'confirmed' || s === 'completed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'scheduled') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'pending') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  if (s === 'cancelled') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

function statusLabel(s: string) {
  const map: Record<string, string> = {
    pending: 'معلق', confirmed: 'مؤكد', scheduled: 'مجدول',
    completed: 'مكتمل', cancelled: 'ملغى',
  }
  return map[s] ?? s
}

function fmtDate(s: string) {
  try { return new Date(s).toLocaleDateString('ar-SY', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }
  catch { return s }
}

function navigate(direction: 'prev' | 'next') {
  router.get('/club/bookings/calendar', {
    view: props.view,
    date: direction === 'prev' ? props.startDate : props.endDate,
    direction,
  }, { preserveState: true })
}
</script>

<template>
  <Head title="تقويم الحجوزات" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header + nav -->
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">تقويم الحجوزات</h1>
        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="navigate('prev')"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
          <span class="text-sm text-gray-700 dark:text-gray-300 font-medium" dir="ltr">
            {{ startDate }} — {{ endDate }}
          </span>
          <button
            type="button"
            @click="navigate('next')"
            class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
          <Link href="/club/bookings" class="ms-2 px-4 py-2 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            قائمة
          </Link>
        </div>
      </div>

      <!-- No bookings -->
      <div v-if="!Object.keys(bookingsByDate).length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-12 text-center">
        <p class="text-gray-500 dark:text-gray-400">لا توجد حجوزات في هذه الفترة</p>
      </div>

      <!-- Grouped by date -->
      <div v-else-if="grouped.length" class="space-y-4">
        <div
          v-for="([date, items]) in grouped"
          :key="date"
          class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden"
        >
          <div class="px-6 py-3 bg-gray-50 dark:bg-gray-800 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ fmtDate(date) }}</h2>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ items.length }} حجز</span>
          </div>
          <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <div
              v-for="b in items"
              :key="b.id"
              class="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40"
            >
              <div class="text-xs font-mono text-gray-500 dark:text-gray-400 w-24 shrink-0" dir="ltr">
                {{ b.start_time }} – {{ b.end_time }}
              </div>
              <div class="flex-1 min-w-0">
                <Link :href="`/club/bookings/${b.id}`" class="text-sm font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 truncate block">
                  {{ b.booking_code ?? '#' + b.id }}
                </Link>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ venueName(b.venue) }}
                  <span v-if="b.user"> · {{ b.user.name }}</span>
                </div>
              </div>
              <span :class="statusTone(b.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap shrink-0">
                {{ statusLabel(b.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
