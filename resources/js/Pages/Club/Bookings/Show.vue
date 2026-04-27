<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'

interface Player {
  name: string
  phone: string | null
  email: string | null
  stats: { total_bookings: number; last_booking: string | null }
}

interface BookingVenue {
  slug: string | null
  name: string
  category: string
  capacity: number | null
  amenities: string[]
}

interface Payment {
  provider: string | null
  transaction_id: string | null
}

interface Booking {
  id: number
  booking_code: string | null
  date: string | null
  start_time: string
  end_time: string
  duration_hours: number
  total_price: number
  deposit_amount: number
  remaining_amount: number
  currency: string
  status: string
  deposit_status: string | null
  notes: string | null
  cancellation_reason: string | null
  cancelled_at: string | null
  created_at: string | null
  player: Player
  venue: BookingVenue
  payment: Payment
  can_cancel: boolean
  can_complete: boolean
  can_mark_no_show: boolean
}

const props = defineProps<{ booking: Booking; timeline: any[] }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function statusTone(s: string) {
  if (s === 'confirmed' || s === 'completed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'scheduled') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'pending') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  if (s === 'cancelled') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

function statusLabel(s: string) {
  const map: Record<string, string> = {
    pending: locale.value === 'ar' ? 'معلق' : 'Pending',
    confirmed: locale.value === 'ar' ? 'مؤكد' : 'Confirmed',
    scheduled: locale.value === 'ar' ? 'مجدول' : 'Scheduled',
    completed: locale.value === 'ar' ? 'مكتمل' : 'Completed',
    cancelled: locale.value === 'ar' ? 'ملغى' : 'Cancelled',
  }
  return map[s] ?? s
}

function depositStatusLabel(s: string | null) {
  if (!s) return '—'
  const map: Record<string, string> = { paid: 'مدفوع', unpaid: 'غير مدفوع', partial: 'جزئي', none: 'بدون' }
  return map[s] ?? s
}

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

function cancelBooking(id: number) {
  if (confirm(locale.value === 'ar' ? 'هل أنت متأكد من إلغاء هذا الحجز؟' : 'Are you sure you want to cancel this booking?')) {
    router.post(`/club/bookings/${id}/cancel`, {}, { preserveScroll: true })
  }
}
</script>

<template>
  <Head :title="`حجز: ${booking.booking_code ?? '#' + booking.id}`" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-3xl space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white font-mono" dir="ltr">
              {{ booking.booking_code ?? '#' + booking.id }}
            </h1>
            <span :class="statusTone(booking.status)" class="text-xs px-2 py-1 rounded-lg">
              {{ statusLabel(booking.status) }}
            </span>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ booking.venue.name }}</p>
        </div>
        <div class="flex gap-2 flex-wrap">
          <button
            v-if="booking.can_cancel"
            type="button"
            @click="cancelBooking(booking.id)"
            class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
          >
            {{ locale === 'ar' ? 'إلغاء الحجز' : 'Cancel Booking' }}
          </button>
          <Link href="/club/bookings" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            {{ locale === 'ar' ? 'العودة' : 'Back' }}
          </Link>
        </div>
      </div>

      <!-- Booking details -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ locale === 'ar' ? 'تفاصيل الحجز' : 'Booking Details' }}</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'التاريخ' : 'Date' }}</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ fmtDate(booking.date) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'الوقت' : 'Time' }}</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ booking.start_time }} – {{ booking.end_time }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'الملعب' : 'Venue' }}</dt>
            <dd class="text-gray-900 dark:text-white">{{ booking.venue.name }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'الإجمالي' : 'Total' }}</dt>
            <dd class="text-gray-900 dark:text-white font-semibold" dir="ltr">{{ fmt(booking.total_price) }} ل.س</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'العربون' : 'Deposit' }}</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ fmt(booking.deposit_amount) }} ل.س</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'المتبقي' : 'Remaining' }}</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ fmt(booking.remaining_amount) }} ل.س</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'حالة العربون' : 'Deposit Status' }}</dt>
            <dd class="text-gray-900 dark:text-white">{{ depositStatusLabel(booking.deposit_status) }}</dd>
          </div>
          <div v-if="booking.notes">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'ملاحظات' : 'Notes' }}</dt>
            <dd class="text-gray-700 dark:text-gray-300">{{ booking.notes }}</dd>
          </div>
        </dl>
      </div>

      <!-- Player info -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ locale === 'ar' ? 'بيانات اللاعب' : 'Player Info' }}</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'الاسم' : 'Name' }}</dt>
            <dd class="text-gray-900 dark:text-white">{{ booking.player.name }}</dd>
          </div>
          <div v-if="booking.player.phone">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'رقم الهاتف' : 'Phone' }}</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ booking.player.phone }}</dd>
          </div>
          <div v-if="booking.player.email">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'البريد' : 'Email' }}</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ booking.player.email }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'إجمالي الحجوزات' : 'Total Bookings' }}</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ booking.player.stats.total_bookings }}</dd>
          </div>
        </dl>
      </div>

      <!-- Payment info -->
      <div v-if="booking.payment.provider || booking.payment.transaction_id" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ locale === 'ar' ? 'بيانات الدفع' : 'Payment Info' }}</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div v-if="booking.payment.provider">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'مزود الدفع' : 'Provider' }}</dt>
            <dd class="text-gray-900 dark:text-white">{{ booking.payment.provider }}</dd>
          </div>
          <div v-if="booking.payment.transaction_id">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ locale === 'ar' ? 'رقم المعاملة' : 'Transaction ID' }}</dt>
            <dd class="text-gray-900 dark:text-white font-mono text-xs" dir="ltr">{{ booking.payment.transaction_id }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </ClubLayout>
</template>
