<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import { computed, reactive, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface CityOpt { id: number; name: string; name_ar: string | null }
interface ClubOpt { id: number; name: Translated; city_id: number | null }
interface CategoryOpt { id: number; name: Translated }
interface Row {
  id: number; slug: string; name: Translated; size: string | null; capacity: number | null
  price_from: number; status: string; bookings_count: number
  club: { id: number; name: Translated; city: CityOpt | null } | null
  category: { id: number; name: Translated } | null
}
interface Stats { total: number; active: number; inactive: number; suspended: number }

interface Props {
  venues: { data: Row[]; links: any[]; meta?: any }
  filters: Record<string, any>
  stats: Stats
  options: { clubs: ClubOpt[]; cities: CityOpt[]; categories: CategoryOpt[]; statuses: string[] }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  search: props.filters.search ?? '',
  category_id: props.filters.category_id ?? '',
  club_id: props.filters.club_id ?? '',
  city_id: props.filters.city_id ?? '',
  status: props.filters.status ?? '',
  price_min: props.filters.price_min ?? '',
  price_max: props.filters.price_max ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/venues', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function t(name: Translated) {
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }

function statusTone(s: string) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'suspended') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

const statCards = computed(() => [
  { label: tr.value.venueStatTotal, value: props.stats.total },
  { label: tr.value.venueStatActive, value: props.stats.active, tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.venueStatInactive, value: props.stats.inactive, tone: 'text-gray-600 dark:text-gray-400' },
  { label: tr.value.venueStatSuspended, value: props.stats.suspended, tone: 'text-red-600 dark:text-red-400' },
])
</script>

<template>
  <Head :title="tr.venues" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.venues }}</h1>
        <Link href="/admin/venues/create" class="px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">
          {{ tr.venueAdd ?? '+ إضافة ملعب' }}
        </Link>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div v-for="(s, i) in statCards" :key="i" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.label }}</div>
          <div :class="s.tone ?? 'text-gray-900 dark:text-white'" class="text-xl sm:text-2xl font-bold mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input v-model="form.search" type="text" :placeholder="tr.venueSearchPlaceholder" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <select v-model="form.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.venueStatusAll }}</option>
          <option v-for="s in options.statuses" :key="s" :value="s">{{ tr['venueStatus' + s.charAt(0).toUpperCase() + s.slice(1)] ?? s }}</option>
        </select>
        <select v-model="form.club_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.venueFilterClub }}</option>
          <option v-for="c in options.clubs" :key="c.id" :value="c.id">{{ t(c.name) }}</option>
        </select>
        <select v-model="form.city_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.venueFilterCity }}</option>
          <option v-for="c in options.cities" :key="c.id" :value="c.id">{{ isAr ? c.name_ar || c.name : c.name }}</option>
        </select>
        <select v-model="form.category_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.venueFilterCategory }}</option>
          <option v-for="c in options.categories" :key="c.id" :value="c.id">{{ t(c.name) }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="form.price_min" type="number" min="0" :placeholder="tr.venuePriceMin" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          <input v-model="form.price_max" type="number" min="0" :placeholder="tr.venuePriceMax" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueName }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.venueClub }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.venueCity }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.venueCategory }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueStatus }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.venuePriceFrom }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.venueBookings }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!venues.data.length">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <tr v-for="v in venues.data" :key="v.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3">
                  <Link :href="`/admin/venues/${v.slug}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">{{ t(v.name) }}</Link>
                  <div class="text-xs text-gray-500 dark:text-gray-400 md:hidden">{{ v.club ? t(v.club.name) : '—' }}</div>
                </td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ v.club ? t(v.club.name) : '—' }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ v.club?.city ? (isAr ? v.club.city.name_ar || v.club.city.name : v.club.city.name) : '—' }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ v.category ? t(v.category.name) : '—' }}</td>
                <td class="px-4 py-3"><span :class="statusTone(v.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">{{ tr['venueStatus' + v.status.charAt(0).toUpperCase() + v.status.slice(1)] ?? v.status }}</span></td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(v.price_from) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.bookings_count }}</td>
                <td class="px-4 py-3">
                  <ActionsMenu :dir="isAr ? 'rtl' : 'ltr'" :items="[
                    { label: tr.venueView ?? 'عرض', href: `/admin/venues/${v.slug}` },
                    { label: tr.venueEdit ?? 'تعديل', href: `/admin/venues/${v.slug}/edit` },
                  ]" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="venues.links" :meta="venues.meta ?? venues" :showing-label="tr.geoPageShowing" />
      </div>
    </div>
  </AdminLayout>
</template>
