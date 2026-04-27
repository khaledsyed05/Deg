<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'

interface BookingRow {
  id: number | null
  booking_code: string
  date: string | null
  venue: { slug: string | null; name: string }
  player: string
  total_amount: number
  commission_amount: number
  commission_rate: number
  net_amount: number
}

interface Settlement {
  id: number
  settlement_number: string
  status: string
  request_date: string | null
  period_from: string | null
  period_to: string | null
  bookings_count: number
  total_amount: number
  paid_amount: number
  settled_at: string | null
  payment_method: string | null
  payment_reference: string | null
  note: string | null
}

interface Totals {
  gross_revenue: number
  commission: number
  net_payout: number
}

const props = defineProps<{ settlement: Settlement; bookings: BookingRow[]; totals: Totals }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function statusTone(s: string) {
  if (s === 'paid') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'pending') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

function statusLabel(s: string) {
  const map: Record<string, string> = {
    paid: locale.value === 'ar' ? 'مدفوع' : 'Paid',
    pending: locale.value === 'ar' ? 'معلق' : 'Pending',
    draft: locale.value === 'ar' ? 'مسودة' : 'Draft',
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
</script>

<template>
  <Head :title="`تسوية ${settlement.settlement_number}`" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-6xl space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
          <div class="flex items-center gap-3 flex-wrap">
            <Link href="/club/finances/settlements" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </Link>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white" dir="ltr">{{ settlement.settlement_number }}</h1>
            <span :class="statusTone(settlement.status)" class="text-xs px-2 py-1 rounded-lg">{{ statusLabel(settlement.status) }}</span>
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400 mt-1 me-8" dir="ltr">
            {{ fmtDate(settlement.period_from) }} — {{ fmtDate(settlement.period_to) }}
          </div>
        </div>
      </div>

      <!-- Summary cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">عدد الحجوزات</div>
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1" dir="ltr">{{ settlement.bookings_count }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الإيرادات</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ fmt(totals.gross_revenue) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">العمولات</div>
          <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1" dir="ltr">{{ fmt(totals.commission) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">الصافي المستحق</div>
          <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-300 mt-1" dir="ltr">{{ fmt(totals.net_payout) }}</div>
        </div>
      </div>

      <!-- Payment info -->
      <div v-if="settlement.status === 'paid'" class="bg-white dark:bg-gray-800/50 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-6">
        <h2 class="font-semibold text-emerald-700 dark:text-emerald-400 mb-3">معلومات الدفع</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المبلغ المدفوع</dt>
            <dd class="text-gray-900 dark:text-white font-semibold" dir="ltr">{{ fmt(settlement.paid_amount) }} ل.س</dd>
          </div>
          <div v-if="settlement.payment_method">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">طريقة الدفع</dt>
            <dd class="text-gray-900 dark:text-white">{{ settlement.payment_method }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">تاريخ الدفع</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ fmtDate(settlement.settled_at) }}</dd>
          </div>
          <div v-if="settlement.payment_reference">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">المرجع</dt>
            <dd class="text-gray-900 dark:text-white font-mono" dir="ltr">{{ settlement.payment_reference }}</dd>
          </div>
        </dl>
      </div>

      <!-- Bookings table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white">تفاصيل الحجوزات ({{ bookings.length }})</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">الكود</th>
                <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                <th class="px-4 py-3 text-start font-medium">الملعب</th>
                <th class="px-4 py-3 text-start font-medium">اللاعب</th>
                <th class="px-4 py-3 text-start font-medium">الإجمالي</th>
                <th class="px-4 py-3 text-start font-medium">العمولة</th>
                <th class="px-4 py-3 text-start font-medium">الصافي</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!bookings.length">
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد بنود</td>
              </tr>
              <tr
                v-for="(b, i) in bookings"
                :key="b.id ?? i"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-white" dir="ltr">{{ b.booking_code }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(b.date) }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ b.venue.name }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ b.player }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300" dir="ltr">{{ fmt(b.total_amount) }}</td>
                <td class="px-4 py-3 text-purple-600 dark:text-purple-400" dir="ltr">{{ fmt(b.commission_amount) }}</td>
                <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400 font-semibold" dir="ltr">{{ fmt(b.net_amount) }}</td>
              </tr>
            </tbody>
            <tfoot v-if="bookings.length" class="bg-gray-50 dark:bg-gray-800 text-sm font-semibold">
              <tr>
                <td colspan="4" class="px-4 py-3 text-gray-700 dark:text-gray-300">الإجماليات</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white" dir="ltr">{{ fmt(totals.gross_revenue) }}</td>
                <td class="px-4 py-3 text-purple-700 dark:text-purple-400" dir="ltr">{{ fmt(totals.commission) }}</td>
                <td class="px-4 py-3 text-emerald-700 dark:text-emerald-400" dir="ltr">{{ fmt(totals.net_payout) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
