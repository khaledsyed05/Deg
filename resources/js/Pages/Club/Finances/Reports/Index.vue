<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed, reactive } from 'vue'
import { useI18n } from '@/i18n'

interface Props {
  template: string
  filters: Record<string, any>
  data: Record<string, any>
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

const form = reactive({
  template: props.template ?? 'revenue',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  venue_id: props.filters.venue_id ?? '',
})

function applyFilters() {
  router.get('/club/finances/reports', form, { preserveState: true, preserveScroll: true })
}

function fmt(n: number | null | undefined) {
  if (n === null || n === undefined) return '—'
  return new Intl.NumberFormat('ar-SY').format(n)
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

const templateOptions = [
  { value: 'revenue', label: 'تقرير الإيرادات' },
  { value: 'bookings', label: 'تقرير الحجوزات' },
  { value: 'commissions', label: 'تقرير العمولات' },
  { value: 'venues', label: 'تقرير الملاعب' },
]

// Try to extract rows array from data for display
const dataRows = computed(() => {
  if (!props.data) return []
  if (Array.isArray(props.data)) return props.data
  // Find first array value in data
  for (const key of Object.keys(props.data)) {
    if (Array.isArray(props.data[key])) return props.data[key]
  }
  return []
})

// Summary stats from data
const dataStats = computed(() => {
  if (!props.data || typeof props.data !== 'object') return []
  return Object.entries(props.data)
    .filter(([, v]) => typeof v === 'number')
    .map(([k, v]) => ({ key: k, value: fmt(v as number) }))
})
</script>

<template>
  <Head title="التقارير المالية" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center gap-3 mb-6 flex-wrap">
        <a href="/club/finances" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">التقارير المالية</h1>
      </div>

      <!-- Filter form -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 mb-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">خيارات التقرير</h2>
        <form @submit.prevent="applyFilters" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">نوع التقرير</label>
            <select
              v-model="form.template"
              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
            >
              <option v-for="t in templateOptions" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">من تاريخ</label>
            <input
              v-model="form.date_from"
              type="date"
              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">إلى تاريخ</label>
            <input
              v-model="form.date_to"
              type="date"
              class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
            />
          </div>
          <div class="flex items-end">
            <button
              type="submit"
              class="w-full px-4 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors shadow-sm"
            >
              عرض التقرير
            </button>
          </div>
        </form>
      </div>

      <!-- Summary numbers -->
      <div v-if="dataStats.length" class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div
          v-for="(s, i) in dataStats"
          :key="i"
          class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4"
        >
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.key }}</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Data table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
          <h2 class="font-semibold text-gray-900 dark:text-white">
            {{ templateOptions.find(t => t.value === template)?.label ?? 'نتائج التقرير' }}
          </h2>
          <span v-if="dataRows.length" class="text-xs text-gray-500 dark:text-gray-400">{{ dataRows.length }} سجل</span>
        </div>

        <div v-if="!dataRows.length" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
          لا توجد بيانات للفترة المحددة
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th
                  v-for="key in Object.keys(dataRows[0])"
                  :key="key"
                  class="px-4 py-3 text-start font-medium"
                >
                  {{ key }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in dataRows"
                :key="idx"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td
                  v-for="key in Object.keys(row)"
                  :key="key"
                  class="px-4 py-3 text-gray-700 dark:text-gray-300"
                  :dir="typeof row[key] === 'number' ? 'ltr' : undefined"
                >
                  {{ typeof row[key] === 'number' ? fmt(row[key]) : row[key] ?? '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
