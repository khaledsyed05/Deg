<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Booking {
  id: number; booking_code: string; date: string; start_time: string; end_time: string
  status: string; player: string | null; venue: Translated; club: Translated; total_price: number
}

interface Props {
  bookings: Booking[]
  month: string
  filters: { venue_id: number | null; club_id: number | null }
  options: { clubs: any[]; venues: any[] }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({ venue_id: props.filters.venue_id ?? '', club_id: props.filters.club_id ?? '' })

function t(name: Translated) {
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }

const [yearStr, monthStr] = props.month.split('-')
const year = parseInt(yearStr)
const month = parseInt(monthStr)

const prevMonth = computed(() => {
  const d = new Date(year, month - 2, 1)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
})
const nextMonth = computed(() => {
  const d = new Date(year, month, 1)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
})

const monthLabel = computed(() => {
  const d = new Date(year, month - 1, 1)
  return d.toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US', { month: 'long', year: 'numeric' })
})

const daysInMonth = computed(() => new Date(year, month, 0).getDate())
const firstDayOfWeek = computed(() => {
  const dow = new Date(year, month - 1, 1).getDay() // 0=Sun
  return dow // We'll use Sun=0 ordering for simplicity
})

const calendarDays = computed(() => {
  const days: Array<{ day: number; bookings: Booking[] }> = []
  for (let d = 1; d <= daysInMonth.value; d++) {
    const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`
    const dayBookings = props.bookings.filter(b => b.date === dateStr)
    days.push({ day: d, bookings: dayBookings })
  }
  return days
})

const dayNames = computed(() => isAr.value
  ? ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت']
  : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
)

const selectedDay = ref<{ day: number; bookings: Booking[] } | null>(null)

function navigate(m: string) {
  router.get('/admin/bookings/calendar', { month: m, ...form }, { preserveState: true })
}

watch(form, () => {
  router.get('/admin/bookings/calendar', { month: props.month, ...form }, { preserveState: true, replace: true })
})

function statusTone(s: string) {
  if (s === 'confirmed') return 'bg-emerald-500'
  if (s === 'completed') return 'bg-blue-500'
  if (s === 'cancelled') return 'bg-red-500'
  if (s === 'scheduled') return 'bg-purple-500'
  return 'bg-gray-400'
}
</script>

<template>
  <Head :title="tr.bookingCalendarView" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <Link href="/admin/bookings" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          </Link>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.bookingCalendarView }}</h1>
        </div>
        <div class="flex items-center gap-2">
          <button @click="navigate(prevMonth)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">{{ tr.bookingCalendarPrev }}</button>
          <span class="text-sm font-semibold text-gray-900 dark:text-white min-w-[120px] text-center">{{ monthLabel }}</span>
          <button @click="navigate(nextMonth)" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">{{ tr.bookingCalendarNext }}</button>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
        <select v-model="form.club_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.bookingFilterClub }}</option>
          <option v-for="c in options.clubs" :key="c.id" :value="c.id">{{ isAr ? c.name.ar || c.name.en : c.name.en || c.name.ar }}</option>
        </select>
        <select v-model="form.venue_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.bookingFilterVenue }}</option>
          <option v-for="v in options.venues" :key="v.id" :value="v.id">{{ isAr ? v.name.ar || v.name.en : v.name.en || v.name.ar }}</option>
        </select>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Calendar Grid -->
        <div class="lg:col-span-3 bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
          <!-- Day names header -->
          <div class="grid grid-cols-7 border-b border-gray-200 dark:border-gray-700">
            <div v-for="d in dayNames" :key="d" class="px-2 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">{{ d }}</div>
          </div>
          <!-- Days grid -->
          <div class="grid grid-cols-7">
            <!-- Empty cells before start -->
            <div v-for="_ in firstDayOfWeek" class="h-24 border-b border-e border-gray-100 dark:border-gray-800"></div>
            <!-- Days -->
            <div
              v-for="d in calendarDays" :key="d.day"
              @click="selectedDay = d"
              class="h-24 border-b border-e border-gray-100 dark:border-gray-800 p-1.5 cursor-pointer hover:bg-emerald-50 dark:hover:bg-emerald-900/10 transition-colors"
              :class="selectedDay?.day === d.day ? 'bg-emerald-50 dark:bg-emerald-900/10' : ''"
            >
              <div class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1" :class="d.bookings.length > 0 ? 'text-gray-900 dark:text-white' : ''">{{ d.day }}</div>
              <div class="space-y-0.5 overflow-hidden">
                <div v-for="b in d.bookings.slice(0, 3)" :key="b.id" class="flex items-center gap-1">
                  <div :class="statusTone(b.status)" class="w-1.5 h-1.5 rounded-full shrink-0"></div>
                  <span class="text-xs text-gray-600 dark:text-gray-400 truncate" dir="ltr">{{ b.start_time }}</span>
                </div>
                <div v-if="d.bookings.length > 3" class="text-xs text-emerald-600 dark:text-emerald-400">+{{ d.bookings.length - 3 }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Day Detail -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">
            {{ selectedDay ? tr.calendarDayDetails + ' ' + selectedDay.day : tr.bookingCalendarView }}
          </h2>
          <div v-if="!selectedDay || !selectedDay.bookings.length" class="text-sm text-gray-500 dark:text-gray-400">
            {{ tr.calendarNoBookings }}
          </div>
          <div v-else class="space-y-3">
            <div v-for="b in selectedDay.bookings" :key="b.id" class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl text-xs space-y-1.5">
              <div class="flex items-center justify-between gap-2">
                <Link :href="`/admin/bookings/${b.id}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400" dir="ltr">{{ b.booking_code }}</Link>
                <div :class="statusTone(b.status)" class="w-2 h-2 rounded-full shrink-0"></div>
              </div>
              <div class="text-gray-600 dark:text-gray-400" dir="ltr">{{ b.start_time }} – {{ b.end_time }}</div>
              <div class="text-gray-700 dark:text-gray-300">{{ b.player ?? '—' }}</div>
              <div class="text-gray-500 dark:text-gray-400 truncate">{{ t(b.venue) }}</div>
              <div class="text-emerald-600 dark:text-emerald-400 font-medium" dir="ltr">{{ fmt(b.total_price) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
