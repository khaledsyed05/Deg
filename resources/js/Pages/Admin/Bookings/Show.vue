<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Payment { id: number; provider: string; amount: number; status: string; provider_transaction_id: string | null; completed_at: string | null; created_at: string | null }
interface Booking {
  id: number; booking_code: string; source: string | null; status: string; deposit_status: string
  remaining_status: string | null; booking_date: string; start_time: string; end_time: string
  duration_minutes: number; venue_price: number; total_price: number; deposit_amount: number
  remaining_amount: number; commission_amount: number; club_payout_amount: number
  currency: string | null; notes: string | null; cancellation_reason: string | null
  cancelled_at: string | null; created_at: string | null
  cancelled_by: { id: number; name: string } | null
  user: { id: number; name: string; phone_number: string | null; email: string | null } | null
  venue: {
    id: number; slug: string; name: Translated; latitude: number | null; longitude: number | null; cover_url: string | null
    category: { id: number; name: Translated } | null
    club: { id: number; slug: string; name: Translated; phone_number: string | null; address: string | null; city: { id: number; name: string; name_ar: string | null } | null } | null
  } | null
  payments: Payment[]
}
interface Activity { id: number; description: string; properties: any; causer: { id: number; name: string } | null; created_at: string | null }

interface Props { booking: Booking; activity: Activity[]; player_bookings_count: number }

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

function t(name: Translated) {
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }
function fmtDateTime(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleString(isAr.value ? 'ar-SY' : 'en-US') }

function statusTone(s: string) {
  if (s === 'confirmed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'completed') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'cancelled') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  if (s === 'scheduled') return 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}
function payStatusTone(s: string) {
  if (s === 'completed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'failed') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  if (s === 'refunded') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

// Cancel modal
const showCancelModal = ref(false)
const cancelForm = reactive({ reason_type: 'player_request', custom_reason: '', notify_whatsapp: false })
const cancelProcessing = ref(false)

function submitCancel() {
  cancelProcessing.value = true
  router.post(`/admin/bookings/${props.booking.id}/cancel`, cancelForm as any, {
    preserveScroll: true,
    onFinish: () => { cancelProcessing.value = false; showCancelModal.value = false },
  })
}

// Modify modal
const showModifyModal = ref(false)
const modifyForm = reactive({ booking_date: props.booking.booking_date, start_time: props.booking.start_time, end_time: props.booking.end_time, notify_whatsapp: false })
const modifyProcessing = ref(false)

function submitModify() {
  modifyProcessing.value = true
  router.post(`/admin/bookings/${props.booking.id}/modify`, modifyForm as any, {
    preserveScroll: true,
    onFinish: () => { modifyProcessing.value = false; showModifyModal.value = false },
  })
}

const canCancel = computed(() => ['confirmed', 'scheduled'].includes(props.booking.status))
const canModify = computed(() => ['confirmed', 'scheduled'].includes(props.booking.status))
</script>

<template>
  <Head :title="booking.booking_code" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <Link href="/admin/bookings" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          </Link>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white" dir="ltr">{{ booking.booking_code }}</h1>
          <span :class="statusTone(booking.status)" class="text-xs px-2 py-1 rounded-lg">{{ tr['bookingStatus_' + booking.status] ?? booking.status }}</span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <button v-if="canModify" @click="showModifyModal = true" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.bookingActionModify }}</button>
          <button @click="router.post(`/admin/bookings/${booking.id}/resend-confirmation`)" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.bookingActionResend }}</button>
          <button v-if="canCancel" @click="showCancelModal = true" class="px-3 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors">{{ tr.bookingActionCancel }}</button>
        </div>
      </div>

      <FlashBanner />

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left column -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Booking Details -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.bookingDetailsCard }}</h2>
            <dl class="grid grid-cols-2 gap-4 text-sm">
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingDate }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ booking.booking_date }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingTime }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ booking.start_time }} – {{ booking.end_time }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingDuration }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ booking.duration_minutes }} {{ tr.bookingDurationMin }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingDepositStatus }}</dt><dd class="mt-0.5"><span class="text-xs px-2 py-1 rounded-lg" :class="booking.deposit_status === 'paid' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'">{{ tr['depositStatus_' + booking.deposit_status] ?? booking.deposit_status }}</span></dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingTotal }}</dt><dd class="text-gray-900 dark:text-white font-bold mt-0.5" dir="ltr">{{ fmt(booking.total_price) }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingDeposit }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ fmt(booking.deposit_amount) }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingRemaining }}</dt><dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ fmt(booking.remaining_amount) }}</dd></div>
              <div><dt class="text-gray-500 dark:text-gray-400 text-xs">{{ tr.bookingCreatedAt }}</dt><dd class="text-gray-600 dark:text-gray-400 font-medium mt-0.5" dir="ltr">{{ fmtDate(booking.created_at) }}</dd></div>
            </dl>
            <div v-if="booking.notes" class="mt-4 p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl text-sm text-gray-700 dark:text-gray-300">{{ booking.notes }}</div>
            <div v-if="booking.cancellation_reason" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl text-sm text-red-700 dark:text-red-300">
              <span class="font-medium">{{ tr.bookingCancelReason }}: </span>{{ booking.cancellation_reason }}
              <span class="text-xs text-red-500 dark:text-red-400 ms-2" dir="ltr">{{ fmtDate(booking.cancelled_at) }}</span>
            </div>
          </div>

          <!-- Payments -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.bookingPaymentCard }}</h2>
            <div v-if="!booking.payments.length" class="text-sm text-gray-500 dark:text-gray-400">{{ tr.bookingNoPayments }}</div>
            <div v-else class="space-y-3">
              <div v-for="p in booking.payments" :key="p.id" class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl text-sm">
                <div>
                  <div class="font-medium text-gray-900 dark:text-white">{{ tr['paymentProvider_' + p.provider] ?? p.provider }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400" dir="ltr">{{ p.provider_transaction_id ?? '—' }}</div>
                </div>
                <div class="text-end">
                  <div class="font-bold text-gray-900 dark:text-white" dir="ltr">{{ fmt(p.amount) }}</div>
                  <span :class="payStatusTone(p.status)" class="text-xs px-2 py-0.5 rounded-lg">{{ tr['paymentStatus_' + p.status] ?? p.status }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Activity -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.bookingTimeline }}</h2>
            <div v-if="!activity.length" class="text-sm text-gray-500 dark:text-gray-400">{{ tr.bookingActivityEmpty }}</div>
            <div v-else class="space-y-3">
              <div v-for="a in activity" :key="a.id" class="flex items-start gap-3 text-sm">
                <div class="w-2 h-2 rounded-full bg-emerald-500 mt-1.5 shrink-0"></div>
                <div>
                  <div class="text-gray-900 dark:text-white">{{ a.description }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" dir="ltr">{{ fmtDateTime(a.created_at) }} — {{ a.causer?.name ?? '—' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right column -->
        <div class="space-y-6">
          <!-- Player -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.bookingPlayerInfo }}</h2>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerName }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ booking.user?.name ?? '—' }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerPhone }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ booking.user?.phone_number ?? '—' }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.bookingPrevBookings }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ player_bookings_count }}</dd></div>
            </dl>
            <Link v-if="booking.user" :href="`/admin/players/${booking.user.id}`" class="mt-4 block text-center text-sm text-emerald-600 dark:text-emerald-400 hover:underline">{{ tr.playerView }}</Link>
          </div>

          <!-- Venue -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.bookingVenueInfo }}</h2>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.venueName }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ booking.venue ? t(booking.venue.name) : '—' }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubName }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ booking.venue?.club ? t(booking.venue.club.name) : '—' }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubCity }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ isAr ? booking.venue?.club?.city?.name_ar || booking.venue?.club?.city?.name : booking.venue?.club?.city?.name ?? '—' }}</dd></div>
              <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.venueCategory }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ booking.venue?.category ? t(booking.venue.category.name) : '—' }}</dd></div>
            </dl>
            <Link v-if="booking.venue" :href="`/admin/venues/${booking.venue.slug}`" class="mt-4 block text-center text-sm text-emerald-600 dark:text-emerald-400 hover:underline">{{ tr.bookingView }}</Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Cancel Modal -->
    <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="showCancelModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ tr.bookingCancelTitle }}</h3>
          <p class="text-sm text-amber-600 dark:text-amber-400">{{ tr.bookingCancelWarning }}</p>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-2">{{ tr.bookingCancelReason }}</label>
            <div class="space-y-2">
              <label v-for="opt in ['player_request','technical_issue','weather','other']" :key="opt" class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
                <input type="radio" v-model="cancelForm.reason_type" :value="opt" class="text-emerald-600" />
                {{ tr['bookingCancelReason' + opt.replace(/_./g, m => m[1].toUpperCase())] ?? opt }}
              </label>
            </div>
          </div>
          <textarea v-if="cancelForm.reason_type === 'other'" v-model="cancelForm.custom_reason" rows="3" :placeholder="tr.bookingCancelCustomPlaceholder" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="cancelForm.notify_whatsapp" class="rounded text-emerald-600" />
            {{ tr.bookingNotifyWhatsapp }}
          </label>
          <div class="flex gap-3">
            <button @click="submitCancel" :disabled="cancelProcessing" class="flex-1 px-4 py-2 text-sm font-semibold bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl transition-colors">{{ tr.bookingCancelConfirm }}</button>
            <button @click="showCancelModal = false" class="flex-1 px-4 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.clubCancel }}</button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Modify Modal -->
    <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
      <div v-if="showModifyModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
          <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ tr.bookingModifyTitle }}</h3>
          <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-xl text-sm text-gray-600 dark:text-gray-400">
            <p class="font-medium text-gray-900 dark:text-white text-xs mb-1">{{ tr.bookingModifyCurrent }}</p>
            <span dir="ltr">{{ booking.booking_date }} {{ booking.start_time }} – {{ booking.end_time }}</span>
          </div>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.bookingModifyNewDate }}</label>
            <input v-model="modifyForm.booking_date" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.bookingModifyStart }}</label><input v-model="modifyForm.start_time" type="time" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" /></div>
            <div><label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.bookingModifyEnd }}</label><input v-model="modifyForm.end_time" type="time" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" /></div>
          </div>
          <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" v-model="modifyForm.notify_whatsapp" class="rounded text-emerald-600" />
            {{ tr.bookingNotifyWhatsapp }}
          </label>
          <div class="flex gap-3">
            <button @click="submitModify" :disabled="modifyProcessing" class="flex-1 px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl transition-colors">{{ tr.bookingModifyConfirm }}</button>
            <button @click="showModifyModal = false" class="flex-1 px-4 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.clubCancel }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </AdminLayout>
</template>
