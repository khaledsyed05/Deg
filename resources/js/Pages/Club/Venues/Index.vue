<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed, reactive, watch } from 'vue'

interface VenueRow {
  id: number
  slug: string
  name: string
  sport_type: string
  capacity: number | null
  status: string | null
  cover_photo: string | null
  today_bookings: number
  week_revenue: number
  occupancy_rate: number
}

interface SportType { id: number; name: string }

const props = defineProps<{
  stats: { total_venues: number; active_venues: number; today_bookings: number; month_revenue: number }
  venues: VenueRow[]
  filters: Record<string, any>
  sportTypes: SportType[]
}>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  search: props.filters.search ?? '',
  sport_type: props.filters.sport_type ?? '',
  status: props.filters.status ?? '',
  sort: props.filters.sort ?? 'name',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/club/venues', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function fmt(n: number) {
  return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n)
}

function statusTone(s: string | null) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'inactive') return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
  if (s === 'suspended') return 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-500'
}

function statusLabel(s: string | null) {
  const map: Record<string, string> = {
    active: isAr.value ? 'نشط' : 'Active',
    inactive: isAr.value ? 'معطّل' : 'Inactive',
    suspended: isAr.value ? 'موقوف' : 'Suspended',
  }
  return s ? (map[s] ?? s) : '—'
}

const statCards = computed(() => [
  { label: isAr.value ? 'إجمالي الملاعب' : 'Total Venues', value: props.stats.total_venues, tone: 'text-gray-900 dark:text-white', icon: '🏟️' },
  { label: isAr.value ? 'ملاعب نشطة' : 'Active', value: props.stats.active_venues, tone: 'text-emerald-600 dark:text-emerald-400', icon: '✅' },
  { label: isAr.value ? 'حجوزات اليوم' : "Today's Bookings", value: props.stats.today_bookings, tone: 'text-blue-600 dark:text-blue-400', icon: '📅' },
  { label: isAr.value ? 'إيرادات الشهر' : 'Month Revenue', value: fmt(props.stats.month_revenue), tone: 'text-emerald-600 dark:text-emerald-400', icon: '💰' },
])
</script>

<template>
  <Head :title="isAr ? 'الملاعب' : 'Venues'" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ isAr ? 'الملاعب' : 'Venues' }}</h1>
        <Link href="/club/venues/create" class="px-4 py-2 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
          {{ isAr ? '+ إضافة ملعب' : '+ Add Venue' }}
        </Link>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div v-for="(c, i) in statCards" :key="i" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
            <span>{{ c.icon }}</span><span>{{ c.label }}</span>
          </div>
          <div :class="c.tone" class="text-2xl font-bold" dir="ltr">{{ c.value }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 flex flex-wrap gap-3">
        <input
          v-model="form.search"
          type="text"
          :placeholder="isAr ? 'ابحث باسم الملعب...' : 'Search venues...'"
          class="flex-1 min-w-[160px] px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
        <select v-model="form.sport_type" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ isAr ? 'كل الأنواع' : 'All Types' }}</option>
          <option v-for="s in sportTypes" :key="s.id" :value="s.id">{{ s.name }}</option>
        </select>
        <select v-model="form.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ isAr ? 'كل الحالات' : 'All Statuses' }}</option>
          <option value="active">{{ isAr ? 'نشط' : 'Active' }}</option>
          <option value="inactive">{{ isAr ? 'معطّل' : 'Inactive' }}</option>
          <option value="suspended">{{ isAr ? 'موقوف' : 'Suspended' }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ isAr ? 'الملعب' : 'Venue' }}</th>
                <th class="hidden sm:table-cell px-4 py-3 text-start font-medium">{{ isAr ? 'النوع' : 'Type' }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ isAr ? 'الحالة' : 'Status' }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ isAr ? 'حجوزات اليوم' : "Today's Bookings" }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ isAr ? 'إيرادات الأسبوع' : 'Week Revenue' }}</th>
                <th class="hidden lg:table-cell px-4 py-3 text-start font-medium">{{ isAr ? 'إشغال الأسبوع' : 'Week Occupancy' }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!venues.length">
                <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                  {{ isAr ? 'لا توجد ملاعب' : 'No venues found' }}
                </td>
              </tr>
              <tr v-for="v in venues" :key="v.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3">
                  <div class="flex items-center gap-3">
                    <img v-if="v.cover_photo" :src="v.cover_photo" class="w-9 h-9 rounded-lg object-cover flex-shrink-0" />
                    <div v-else class="w-9 h-9 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 text-xs flex-shrink-0">🏟️</div>
                    <Link :href="`/club/venues/${v.slug}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">
                      {{ v.name }}
                    </Link>
                  </div>
                </td>
                <td class="hidden sm:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ v.sport_type }}</td>
                <td class="px-4 py-3">
                  <span :class="statusTone(v.status)" class="text-xs px-2 py-1 rounded-lg">{{ statusLabel(v.status) }}</span>
                </td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.today_bookings }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(v.week_revenue) }}</td>
                <td class="hidden lg:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.occupancy_rate }}%</td>
                <td class="px-4 py-3 text-end">
                  <div class="relative inline-block">
                    <div class="flex items-center gap-1">
                      <Link :href="`/club/venues/${v.slug}`" class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        {{ isAr ? 'عرض' : 'View' }}
                      </Link>
                      <Link :href="`/club/venues/${v.slug}/edit`" class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        {{ isAr ? 'تعديل' : 'Edit' }}
                      </Link>
                    </div>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
