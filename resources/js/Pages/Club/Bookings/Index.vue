<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import { computed, reactive, watch } from 'vue'
import { useI18n } from '@/i18n'

interface BookingRow {
  id: number
  booking_code: string | null
  booking_date: string
  start_time: string
  end_time: string
  total_price: number
  status: string
  deposit_status: string | null
  venue: { name: string | { ar?: string; en?: string } }
  user: { name: string } | null
}

interface Props {
  stats: {
    today_bookings: number
    upcoming_bookings: number
    month_revenue: number
    pending_payments: number
  }
  bookings: { data: BookingRow[]; links: any[]; meta?: any }
  filters: Record<string, any>
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

const form = reactive({
  search: props.filters.search ?? '',
  status: props.filters.status ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/club/bookings', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function venueName(v: BookingRow['venue']) {
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

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

const statCards = computed(() => [
  { label: 'حجوزات اليوم', value: props.stats.today_bookings, tone: 'text-gray-900 dark:text-white' },
  { label: 'الحجوزات القادمة', value: props.stats.upcoming_bookings, tone: 'text-blue-600 dark:text-blue-400' },
  { label: 'إيرادات الشهر', value: fmt(props.stats.month_revenue) + ' ل.س', tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: 'بانتظار الدفع', value: props.stats.pending_payments, tone: 'text-amber-600 dark:text-amber-400' },
])
</script>

<template>
  <Head title="الحجوزات" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">الحجوزات</h1>
        <div class="flex gap-2">
          <Link href="/club/bookings/calendar" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            تقويم
          </Link>
          <Link href="/club/bookings/create" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors shadow-sm">
            حجز يدوي
          </Link>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div
          v-for="(s, i) in statCards"
          :key="i"
          class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4"
        >
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.label }}</div>
          <div :class="s.tone" class="text-xl sm:text-2xl font-bold mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input
          v-model="form.search"
          type="text"
          placeholder="بحث بكود الحجز أو اسم اللاعب..."
          class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
        <select
          v-model="form.status"
          class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
        >
          <option value="">جميع الحالات</option>
          <option value="pending">معلق</option>
          <option value="confirmed">مؤكد</option>
          <option value="scheduled">مجدول</option>
          <option value="completed">مكتمل</option>
          <option value="cancelled">ملغى</option>
        </select>
        <input v-model="form.date_from" type="date" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <input v-model="form.date_to" type="date" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">الكود</th>
                <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                <th class="px-4 py-3 text-start font-medium">الوقت</th>
                <th class="px-4 py-3 text-start font-medium">الملعب</th>
                <th class="px-4 py-3 text-start font-medium">اللاعب</th>
                <th class="px-4 py-3 text-start font-medium">الإجمالي</th>
                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!bookings.data.length">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد حجوزات</td>
              </tr>
              <tr
                v-for="b in bookings.data"
                :key="b.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3">
                  <Link :href="`/club/bookings/${b.id}`" class="font-mono text-xs text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">
                    {{ b.booking_code ?? '#' + b.id }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(b.booking_date) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ b.start_time }}–{{ b.end_time }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ venueName(b.venue) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ b.user?.name ?? 'ضيف' }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium" dir="ltr">{{ fmt(b.total_price) }}</td>
                <td class="px-4 py-3">
                  <span :class="statusTone(b.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">
                    {{ statusLabel(b.status) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-end">
                  <ActionsMenu
                    dir="rtl"
                    :items="[
                      { label: 'عرض', href: `/club/bookings/${b.id}` },
                    ]"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="bookings.links" :meta="bookings.meta ?? bookings" showing-label="عرض" />
      </div>
    </div>
  </ClubLayout>
</template>
