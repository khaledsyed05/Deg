<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Row {
  id: number; booking_code: string; booking_date: string; start_time: string; end_time: string
  duration_minutes: number; status: string; deposit_status: string; total_price: number
  deposit_amount: number; remaining_amount: number
  user: { id: number; name: string; phone_number: string | null } | null
  venue: { id: number; slug: string; name: Translated; club: { id: number; slug: string; name: Translated; city: { id: number; name: string; name_ar: string | null } | null } | null } | null
}
interface Stats { total: number; confirmed: number; scheduled: number; completed: number; cancelled: number; revenue: number; pending_payments: number; today: number }
interface Props {
  bookings: { data: Row[]; links: any[]; meta?: any }
  filters: Record<string, any>
  stats: Stats
  options: { clubs: any[]; cities: any[]; statuses: string[]; deposit_statuses: string[] }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  search: props.filters.search ?? '',
  status: props.filters.status ?? '',
  deposit_status: props.filters.deposit_status ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  venue_id: props.filters.venue_id ?? '',
  club_id: props.filters.club_id ?? '',
  city_id: props.filters.city_id ?? '',
  category_id: props.filters.category_id ?? '',
  amount_min: props.filters.amount_min ?? '',
  amount_max: props.filters.amount_max ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/bookings', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function t(name: Translated) {
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function fmt(n: number) {
  return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n)
}
function statusTone(s: string) {
  if (s === 'confirmed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'completed') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'cancelled') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  if (s === 'scheduled') return 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

const statCards = computed(() => [
  { label: tr.value.bookingStatTotal, value: props.stats.total },
  { label: tr.value.bookingStatToday, value: props.stats.today, tone: 'text-blue-600 dark:text-blue-400' },
  { label: tr.value.bookingStatConfirmed, value: props.stats.confirmed, tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.bookingStatCompleted, value: props.stats.completed, tone: 'text-blue-600 dark:text-blue-400' },
  { label: tr.value.bookingStatCancelled, value: props.stats.cancelled, tone: 'text-red-600 dark:text-red-400' },
  { label: tr.value.bookingStatRevenue, value: fmt(props.stats.revenue), tone: 'text-emerald-600 dark:text-emerald-400' },
])
</script>

<template>
  <Head :title="tr.bookings" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.bookings }}</h1>
        <div class="flex items-center gap-2">
          <Link href="/admin/bookings/calendar" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            {{ tr.bookingCalendarView }}
          </Link>
          <a href="/admin/bookings/export" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            {{ tr.bookingExport }}
          </a>
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
        <input v-model="form.search" type="text" :placeholder="tr.bookingSearchPlaceholder" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <select v-model="form.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.bookingStatusAll }}</option>
          <option v-for="s in options.statuses" :key="s" :value="s">{{ tr['bookingStatus_' + s] ?? s }}</option>
        </select>
        <select v-model="form.club_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.bookingFilterClub }}</option>
          <option v-for="c in options.clubs" :key="c.id" :value="c.id">{{ isAr ? c.name.ar || c.name.en : c.name.en || c.name.ar }}</option>
        </select>
        <select v-model="form.city_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.bookingFilterCity }}</option>
          <option v-for="c in options.cities" :key="c.id" :value="c.id">{{ isAr ? c.name_ar || c.name : c.name }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.bookingDateFrom }}</label><input v-model="form.date_from" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
          <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.bookingDateTo }}</label><input v-model="form.date_to" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" /></div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="form.amount_min" type="number" min="0" :placeholder="tr.bookingAmountMin" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          <input v-model="form.amount_max" type="number" min="0" :placeholder="tr.bookingAmountMax" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingCode }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingPlayer }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.bookingVenue }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingDate }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.bookingTime }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingStatus }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.bookingTotal }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!bookings.data.length">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <tr v-for="b in bookings.data" :key="b.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white" dir="ltr">
                  <Link :href="`/admin/bookings/${b.id}`" class="hover:text-emerald-600 dark:hover:text-emerald-400">{{ b.booking_code }}</Link>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ b.user?.name ?? '—' }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[140px] truncate">{{ b.venue ? t(b.venue.name) : '—' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ b.booking_date }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ b.start_time }} – {{ b.end_time }}</td>
                <td class="px-4 py-3"><span :class="statusTone(b.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">{{ tr['bookingStatus_' + b.status] ?? b.status }}</span></td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(b.total_price) }}</td>
                <td class="px-4 py-3">
                  <ActionsMenu :dir="isAr ? 'rtl' : 'ltr'" :items="[
                    { label: tr.bookingView, href: `/admin/bookings/${b.id}` },
                  ]" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="bookings.links" :meta="bookings.meta ?? bookings" :showing-label="tr.geoPageShowing" />
      </div>
    </div>
  </AdminLayout>
</template>
