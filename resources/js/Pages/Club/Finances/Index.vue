<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface VenueRow {
  venue: string
  revenue: number
  net: number
  bookings: number
}

interface TrendRow {
  date: string
  revenue: number
  net: number
}

interface Props {
  stats: {
    total_revenue: number
    net_payout: number
    commission: number
    pending_settlements: number
  }
  commissionRate: number
  revenueTrend: TrendRow[]
  revenueByVenue: VenueRow[]
  transactions: { data: any[]; links: any[]; meta?: any }
  filters: Record<string, any>
}

const props = defineProps<Props>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

</script>

<template>
  <Head title="المالية" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">نظرة مالية عامة</h1>
        <div class="flex gap-2">
          <Link href="/club/finances/settlements" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            التسويات
          </Link>
          <Link href="/club/finances/reports" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors shadow-sm">
            التقارير
          </Link>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الإيرادات</div>
          <div class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ fmt(stats.total_revenue) }}</div>
          <div class="text-xs text-gray-400 mt-0.5">ل.س</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">صافي المدفوعات</div>
          <div class="text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ fmt(stats.net_payout) }}</div>
          <div class="text-xs text-gray-400 mt-0.5">ل.س</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">في انتظار الصرف</div>
          <div class="text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1" dir="ltr">{{ fmt(stats.pending_settlements) }}</div>
          <div class="text-xs text-gray-400 mt-0.5">ل.س</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي العمولات</div>
          <div class="text-xl sm:text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1" dir="ltr">{{ fmt(stats.commission) }}</div>
          <div class="text-xs text-gray-400 mt-0.5">ل.س</div>
        </div>
      </div>

      <!-- Revenue by venue -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white">الإيرادات حسب الملعب</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">الملعب</th>
                <th class="px-4 py-3 text-start font-medium">الحجوزات</th>
                <th class="px-4 py-3 text-start font-medium">الإيرادات</th>
                <th class="px-4 py-3 text-start font-medium">الصافي</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!revenueByVenue.length">
                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد بيانات</td>
              </tr>
              <tr
                v-for="(row, i) in revenueByVenue"
                :key="i"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ row.venue }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300" dir="ltr">{{ row.bookings }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300" dir="ltr">{{ fmt(row.revenue) }}</td>
                <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400 font-semibold" dir="ltr">{{ fmt(row.net) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
