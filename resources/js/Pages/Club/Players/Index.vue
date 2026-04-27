<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import { computed, reactive, watch } from 'vue'
import { useI18n } from '@/i18n'

interface PlayerRow {
  id: number
  name: string
  phone_number: string | null
  email: string | null
  bookings_count: number
  total_spent: number
  last_booking_at: string | null
}

interface Props {
  stats: {
    total_players: number
    active_players: number
    lifetime_revenue: number
    avg_bookings: number
  }
  players: { data: PlayerRow[]; links: any[]; meta?: any }
  filters: Record<string, any>
}

const props = defineProps<Props>()

const form = reactive({
  search: props.filters.search ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/club/players', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

const statCards = computed(() => [
  { label: 'إجمالي اللاعبين', value: props.stats.total_players, tone: 'text-gray-900 dark:text-white' },
  { label: 'اللاعبون النشطون', value: props.stats.active_players, tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: 'إجمالي الإيرادات', value: fmt(props.stats.lifetime_revenue) + ' ل.س', tone: 'text-blue-600 dark:text-blue-400' },
  { label: 'متوسط الحجوزات', value: props.stats.avg_bookings, tone: 'text-purple-600 dark:text-purple-400' },
])
</script>

<template>
  <Head title="اللاعبون" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">اللاعبون</h1>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-3 mb-4">
        <div
          v-for="(s, i) in statCards"
          :key="i"
          class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4"
        >
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.label }}</div>
          <div :class="s.tone" class="text-xl sm:text-2xl font-bold mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Search -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4">
        <input
          v-model="form.search"
          type="text"
          placeholder="بحث بالاسم أو الهاتف أو البريد..."
          class="w-full sm:max-w-sm px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
        />
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">الاسم</th>
                <th class="px-4 py-3 text-start font-medium">الهاتف</th>
                <th class="hidden sm:table-cell px-4 py-3 text-start font-medium">البريد</th>
                <th class="px-4 py-3 text-start font-medium">الحجوزات</th>
                <th class="hidden sm:table-cell px-4 py-3 text-start font-medium">الإنفاق</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">آخر حجز</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!players.data.length">
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا يوجد لاعبون</td>
              </tr>
              <tr
                v-for="p in players.data"
                :key="p.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3">
                  <Link :href="`/club/players/${p.id}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">
                    {{ p.name }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ p.phone_number ?? '—' }}</td>
                <td class="hidden sm:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ p.email ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300" dir="ltr">{{ p.bookings_count }}</td>
                <td class="hidden sm:table-cell px-4 py-3 text-emerald-600 dark:text-emerald-400 font-medium" dir="ltr">{{ fmt(p.total_spent) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-500 dark:text-gray-400" dir="ltr">{{ fmtDate(p.last_booking_at) }}</td>
                <td class="px-4 py-3 text-end">
                  <ActionsMenu
                    dir="rtl"
                    :items="[
                      { label: 'عرض', href: `/club/players/${p.id}` },
                    ]"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="players.links" :meta="players.meta ?? players" showing-label="عرض" />
      </div>
    </div>
  </ClubLayout>
</template>
