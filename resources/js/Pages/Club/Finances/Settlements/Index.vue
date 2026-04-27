<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface SettlementRow {
  id: number
  status: string
  period_from: string | null
  period_to: string | null
  net_payable: number
  paid_amount: number
  settled_at: string | null
}

interface Props {
  summary: {
    pending_amount: number
    pending_bookings_count: number
    next_settlement_date: string | null
    last_settlement_date: string | null
    total_settlements: number
  }
  settlements: { data: SettlementRow[]; links: any[]; meta?: any }
  filters: Record<string, any>
  canRequestSettlement: boolean
  minThreshold: number
}

defineProps<Props>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function statusTone(s: string) {
  if (s === 'paid') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'pending') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

function statusLabel(s: string) {
  const map: Record<string, string> = { paid: 'مدفوع', pending: 'معلق', draft: 'مسودة' }
  return map[s] ?? s
}

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}
</script>

<template>
  <Head title="التسويات" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center gap-3 mb-6 flex-wrap">
        <Link href="/club/finances" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </Link>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">التسويات</h1>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">معلق للصرف</div>
          <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1" dir="ltr">{{ fmt(summary.pending_amount) }}</div>
          <div class="text-xs text-gray-400 mt-0.5">ل.س</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">حجوزات معلقة</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ summary.pending_bookings_count }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي التسويات</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ summary.total_settlements }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">موعد التسوية القادمة</div>
          <div class="text-sm font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ summary.next_settlement_date ?? '—' }}</div>
        </div>
      </div>

      <!-- Settlements table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">رقم التسوية</th>
                <th class="px-4 py-3 text-start font-medium">الفترة</th>
                <th class="px-4 py-3 text-start font-medium">المستحق</th>
                <th class="px-4 py-3 text-start font-medium">المدفوع</th>
                <th class="px-4 py-3 text-start font-medium">تاريخ الدفع</th>
                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!settlements.data.length">
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد تسويات</td>
              </tr>
              <tr
                v-for="s in settlements.data"
                :key="s.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3">
                  <Link :href="`/club/finances/settlements/${s.id}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400" dir="ltr">
                    #{{ s.id }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs" dir="ltr">
                  {{ fmtDate(s.period_from) }} — {{ fmtDate(s.period_to) }}
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-semibold" dir="ltr">{{ fmt(s.net_payable) }}</td>
                <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400" dir="ltr">{{ s.paid_amount ? fmt(s.paid_amount) : '—' }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400" dir="ltr">{{ fmtDate(s.settled_at) }}</td>
                <td class="px-4 py-3">
                  <span :class="statusTone(s.status)" class="text-xs px-2 py-1 rounded-lg">{{ statusLabel(s.status) }}</span>
                </td>
                <td class="px-4 py-3 text-end">
                  <Link :href="`/club/finances/settlements/${s.id}`" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
                    عرض
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="settlements.links" :meta="settlements.meta ?? settlements" showing-label="عرض" />
      </div>
    </div>
  </ClubLayout>
</template>
