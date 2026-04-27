<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Payment {
  id: number; amount: number; currency: string | null; provider: string; flow_type: string | null
  status: string; provider_transaction_id: string | null; provider_reference: string | null
  provider_payload: any; failure_reason: string | null; retry_count: number
  refund_amount: number | null; refund_reason: string | null; refunded_at: string | null
  refunded_by: { id: number; name: string } | null; initiated_at: string | null
  completed_at: string | null; failed_at: string | null; created_at: string | null
  booking: {
    id: number; booking_code: string; booking_date: string; start_time: string; end_time: string; status: string
    user: { id: number; name: string; phone_number: string | null; email: string | null } | null
    venue: { id: number; slug: string; name: Translated; club: { id: number; slug: string; name: Translated } | null } | null
  } | null
}
interface Activity { id: number; description: string; properties: any; causer: { id: number; name: string } | null; created_at: string | null }

interface Props {
  payment: Payment
  timeline: Activity[]
  options: { statuses: string[]; providers: string[] }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

function t(name: Translated) { return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—' }
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }
function fmtDateTime(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleString(isAr.value ? 'ar-SY' : 'en-US') }

function statusTone(s: string) {
  if (s === 'completed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'failed') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  if (s === 'refunded') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'pending' || s === 'processing') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

// Retry modal
const showRetryModal = ref(false)
const retryForm = reactive({ provider: props.payment.provider, phone_number: '', send_notification: false })
const retryProcessing = ref(false)

function submitRetry() {
  retryProcessing.value = true
  router.post(`/admin/payments/${props.payment.id}/retry`, retryForm as any, {
    preserveScroll: true,
    onFinish: () => { retryProcessing.value = false; showRetryModal.value = false },
  })
}

// Refund modal
const showRefundModal = ref(false)
const refundForm = reactive({ refund_amount: props.payment.amount, refund_reason: '', send_notification: false })
const refundProcessing = ref(false)

function submitRefund() {
  refundProcessing.value = true
  router.post(`/admin/payments/${props.payment.id}/refund`, refundForm as any, {
    preserveScroll: true,
    onFinish: () => { refundProcessing.value = false; showRefundModal.value = false },
  })
}
</script>

<template>
  <Head :title="`${tr.paymentSingular} #${payment.id}`" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <Link href="/admin/payments" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          </Link>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">#{{ payment.id }}</h1>
          <span :class="statusTone(payment.status)" class="text-xs px-2 py-1 rounded-lg">{{ tr['paymentStatus_' + payment.status] ?? payment.status }}</span>
        </div>
        <div class="flex items-center gap-2">
          <button v-if="payment.status === 'failed'" @click="showRetryModal = true" class="px-3 py-2 text-sm font-medium bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition-colors">{{ tr.paymentActionRetry }}</button>
          <button v-if="payment.status === 'completed' && !payment.refunded_at" @click="showRefundModal = true" class="px-3 py-2 text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-xl transition-colors">{{ tr.paymentActionRefund }}</button>
        </div>
      </div>

      <FlashBanner />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <!-- Payment Details -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.paymentDetailsCard }}</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentProvider }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5">{{ tr['paymentProvider_' + payment.provider] ?? payment.provider }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentAmount }}</dt><dd class="text-gray-900 dark:text-white font-bold mt-0.5" dir="ltr">{{ fmt(payment.amount) }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentTransactionId }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5 truncate" dir="ltr">{{ payment.provider_transaction_id ?? '—' }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentProviderReference }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5 truncate" dir="ltr">{{ payment.provider_reference ?? '—' }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentRetryCount }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ payment.retry_count }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentCreatedAt }}</dt><dd class="text-gray-600 dark:text-gray-400 mt-0.5" dir="ltr">{{ fmtDate(payment.created_at) }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentCompletedAt }}</dt><dd class="text-gray-600 dark:text-gray-400 mt-0.5" dir="ltr">{{ fmtDate(payment.completed_at) }}</dd></div>
              <div v-if="payment.failed_at"><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.paymentFailedAt }}</dt><dd class="text-red-600 dark:text-red-400 mt-0.5" dir="ltr">{{ fmtDate(payment.failed_at) }}</dd></div>
            </dl>
            <div v-if="payment.failure_reason" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl text-sm text-red-700 dark:text-red-300">
              <span class="font-medium">{{ tr.paymentFailureReason }}: </span>{{ payment.failure_reason }}
            </div>
          </div>

          <!-- Refund Info -->
          <div v-if="payment.refunded_at" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.paymentRefundCard }}</h2>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.paymentRefundAmount }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ payment.refund_amount !== null ? fmt(payment.refund_amount) : '—' }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.paymentRefundReason }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ payment.refund_reason ?? '—' }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.paymentRefundDate }}</dt><dd class="text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(payment.refunded_at) }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.paymentRefundBy }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ payment.refunded_by?.name ?? '—' }}</dd></div>
            </dl>
          </div>

          <!-- Timeline -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.paymentTimeline }}</h2>
            <div v-if="!timeline.length" class="text-sm text-gray-500 dark:text-gray-400">{{ tr.bookingActivityEmpty }}</div>
            <div v-else class="space-y-3">
              <div v-for="a in timeline" :key="a.id" class="flex items-start gap-3 text-sm">
                <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></div>
                <div>
                  <div class="text-gray-900 dark:text-white">{{ a.description }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" dir="ltr">{{ fmtDateTime(a.created_at) }} — {{ a.causer?.name ?? '—' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right sidebar -->
        <div class="space-y-6">
          <!-- Booking Info -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.paymentBookingInfo }}</h2>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.bookingCode }}</dt>
                <dd>
                  <Link v-if="payment.booking" :href="`/admin/bookings/${payment.booking.id}`" class="text-emerald-600 dark:text-emerald-400 hover:underline font-medium" dir="ltr">{{ payment.booking.booking_code }}</Link>
                  <span v-else class="text-gray-400">—</span>
                </dd>
              </div>
              <div v-if="payment.booking" class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.bookingDate }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ payment.booking.booking_date }}</dd></div>
              <div v-if="payment.booking" class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.bookingTime }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ payment.booking.start_time }} – {{ payment.booking.end_time }}</dd></div>
              <div v-if="payment.booking" class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerName }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ payment.booking.user?.name ?? '—' }}</dd></div>
              <div v-if="payment.booking?.venue" class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.venueName }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ t(payment.booking.venue.name) }}</dd></div>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Retry Modal -->
    <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="showRetryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ tr.paymentRetryTitle }}</h3>
          <p class="text-sm text-amber-600 dark:text-amber-400">{{ tr.paymentRetryWarning }}</p>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.paymentProvider }}</label>
            <select v-model="retryForm.provider" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option v-for="p in options.providers" :key="p" :value="p">{{ tr['paymentProvider_' + p] ?? p }}</option>
            </select>
          </div>
          <div class="flex gap-3">
            <button @click="submitRetry" :disabled="retryProcessing" class="flex-1 px-4 py-2 text-sm font-semibold bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-xl transition-colors">{{ tr.paymentRetryConfirm }}</button>
            <button @click="showRetryModal = false" class="flex-1 px-4 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.clubCancel }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Refund Modal -->
    <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="showRefundModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ tr.paymentRefundTitle }}</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ tr.paymentRefundNoticeGateway }}</p>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.paymentRefundAmountLabel }}</label>
            <input v-model.number="refundForm.refund_amount" type="number" :max="payment.amount" min="1" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            <p class="text-xs text-gray-400 mt-1">{{ tr.paymentRefundHint }}</p>
          </div>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.paymentRefundReasonLabel }}</label>
            <textarea v-model="refundForm.refund_reason" rows="3" :placeholder="tr.paymentRefundReasonPlaceholder" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>
          <div class="flex gap-3">
            <button @click="submitRefund" :disabled="refundProcessing" class="flex-1 px-4 py-2 text-sm font-semibold bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl transition-colors">{{ tr.paymentRefundConfirm }}</button>
            <button @click="showRefundModal = false" class="flex-1 px-4 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.clubCancel }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </AdminLayout>
</template>
