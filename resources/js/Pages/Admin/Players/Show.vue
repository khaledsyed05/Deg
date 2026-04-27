<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Player {
  id: number; name: string; phone_number: string | null; email: string | null; address: string | null
  default_city_id: number | null; account_status: string; block_reason: string | null
  created_at: string | null; blocked_at: string | null; unblocked_at: string | null
  last_login_at: string | null; phone_verified_at: string | null; email_verified_at: string | null
  city: { id: number; name: string; name_ar: string | null } | null
}
interface Analytics {
  total_bookings: number; total_spent: number; avg_booking_value: number
  bookings_this_month: number; favorite_sport: Translated; favorite_venue: Translated
}
interface SpendingMonth { month: string; month_en: string; spent: number }
interface BookingRow {
  id: number; booking_code: string; booking_date: string; start_time: any; end_time: any; status: any; total_price: number
  venue: { id: number; name: Translated; club: { id: number; name: Translated } | null; category: { id: number; name: Translated } | null } | null
}
interface PaymentRow {
  id: number; created_at: string | null; booking_code: string | null; booking_id: number; provider: any; amount: number; currency: string | null; status: any; provider_transaction_id: string | null
}
interface FavoriteVenue { id: number; slug: string; name: Translated; club_name: Translated; sport_name: Translated; bookings_count: number; cover_url: string | null }

interface Props {
  player: Player
  analytics: Analytics
  spending_by_month: SpendingMonth[]
  bookings: { data: BookingRow[]; links: any[]; meta?: any }
  payments: { data: PaymentRow[]; links: any[]; meta?: any }
  favorite_venues: FavoriteVenue[]
  bookings_filters: Record<string, any>
  payments_filters: Record<string, any>
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const activeTab = ref<'bookings' | 'payments' | 'favorites'>('bookings')

function t(name: Translated) { return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—' }
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }

const maxSpent = computed(() => Math.max(...props.spending_by_month.map(m => m.spent), 1))

function statusTone(s: string) {
  const sv = typeof s === 'object' ? (s as any).value ?? s : s
  if (sv === 'confirmed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (sv === 'completed') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (sv === 'cancelled') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}
function statusLabel(s: any) {
  const sv = typeof s === 'object' ? s.value ?? s : s
  return tr.value['bookingStatus_' + sv] ?? sv
}
function payStatusTone(s: any) {
  const sv = typeof s === 'object' ? s.value ?? s : s
  if (sv === 'completed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (sv === 'failed') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

// Block modal
type ModalAction = 'block' | 'unblock'
const modalAction = ref<ModalAction | null>(null)
const processing = ref(false)

function open(action: ModalAction) { modalAction.value = action }
function close() { modalAction.value = null }

function confirmAction(payload: { reason?: string; send_email: boolean }) {
  processing.value = true
  router.post(`/admin/players/${props.player.id}/${modalAction.value}`, payload as any, {
    preserveScroll: true,
    onFinish: () => { processing.value = false; close() },
  })
}

const modalConfig = computed(() => {
  if (!modalAction.value) return null
  if (modalAction.value === 'block') return {
    title: tr.value.playerBlockTitle, subtitle: props.player.name, warning: tr.value.playerBlockWarning,
    requireReason: true, reasonLabel: tr.value.playerReasonLabel, reasonPlaceholder: tr.value.playerBlockPlaceholder,
    confirmLabel: tr.value.playerBlockConfirm, confirmTone: 'red' as const,
  }
  return {
    title: tr.value.playerUnblockTitle, subtitle: props.player.name, body: tr.value.playerUnblockBody,
    requireReason: false, confirmLabel: tr.value.playerUnblockConfirm, confirmTone: 'emerald' as const,
  }
})
</script>

<template>
  <Head :title="player.name" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <Link href="/admin/players" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          </Link>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ player.name }}</h1>
          <span class="text-xs px-2 py-1 rounded-lg" :class="player.account_status === 'active' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'">{{ tr['playerStatus_' + player.account_status] ?? player.account_status }}</span>
        </div>
        <div class="flex items-center gap-2">
          <Link :href="`/admin/players/${player.id}/edit`" class="px-3 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">{{ tr.playerEdit }}</Link>
          <button v-if="player.account_status !== 'blocked'" @click="open('block')" class="px-3 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors">{{ tr.playerBlock }}</button>
          <button v-else @click="open('unblock')" class="px-3 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">{{ tr.playerUnblock }}</button>
        </div>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.playerTotalBookings }}</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ analytics.total_bookings }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.playerTotalSpent }}</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ fmt(analytics.total_spent) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.playerAvgBookingValue }}</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ fmt(analytics.avg_booking_value) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.playerBookingsThisMonth }}</div>
          <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1" dir="ltr">{{ analytics.bookings_this_month }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.playerFavoriteSport }}</div>
          <div class="text-sm font-bold text-gray-900 dark:text-white mt-1">{{ t(analytics.favorite_sport) || '—' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.playerFavoriteVenue }}</div>
          <div class="text-sm font-bold text-gray-900 dark:text-white mt-1 truncate">{{ t(analytics.favorite_venue) || '—' }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Player Info -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.playerContactInfo }}</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerPhone }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ player.phone_number ?? '—' }}
              <span v-if="player.phone_verified_at" class="ms-1 text-xs text-emerald-600 dark:text-emerald-400">✓</span>
              <span v-else class="ms-1 text-xs text-gray-400">✗</span>
            </dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerEmail }}</dt><dd class="text-gray-900 dark:text-white font-medium truncate text-end">{{ player.email ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerCity }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ isAr ? player.city?.name_ar || player.city?.name : player.city?.name ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerJoined }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmtDate(player.created_at) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.playerLastLogin }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmtDate(player.last_login_at) }}</dd></div>
          </dl>
          <div v-if="player.block_reason" class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-xl text-xs text-red-700 dark:text-red-300">
            <span class="font-medium">{{ tr.playerBlockReason }}: </span>{{ player.block_reason }}
          </div>
        </div>

        <!-- Spending Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.playerSpendingChart }}</h2>
          <div v-if="spending_by_month.every(m => m.spent === 0)" class="h-32 flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm">{{ tr.noRevenueData }}</div>
          <div v-else class="flex items-end gap-2 h-32">
            <div v-for="m in spending_by_month" :key="m.month" class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
              <div class="w-full bg-emerald-500/80 dark:bg-emerald-500/60 rounded-t-md" :style="{ height: Math.round((m.spent / maxSpent) * 100) + '%', minHeight: m.spent > 0 ? '4px' : '0' }" :title="fmt(m.spent)"></div>
              <div class="text-xs text-gray-500 dark:text-gray-400 truncate w-full text-center">{{ isAr ? m.month.split(' ')[0] : m.month_en.split(' ')[0] }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
          <button v-for="tab in ['bookings','payments','favorites']" :key="tab" @click="activeTab = tab as any"
            class="px-4 py-3 text-sm font-medium transition-colors"
            :class="activeTab === tab ? 'text-emerald-600 dark:text-emerald-400 border-b-2 border-emerald-600 dark:border-emerald-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
            {{ tab === 'bookings' ? tr.playerTabBookings : tab === 'payments' ? tr.playerTabPayments : tr.playerTabFavorites }}
          </button>
        </div>

        <!-- Bookings -->
        <div v-if="activeTab === 'bookings'" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingCode }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.playerBookingDate }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playerBookingVenue }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.playerBookingStatus }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playerBookingAmount }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!bookings.data.length"><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.playerNoBookings }}</td></tr>
              <tr v-for="b in bookings.data" :key="b.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3">
                  <Link :href="`/admin/bookings/${b.id}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400" dir="ltr">{{ b.booking_code }}</Link>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ b.booking_date }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[160px] truncate">{{ b.venue ? t(b.venue.name) : '—' }}</td>
                <td class="px-4 py-3"><span :class="statusTone(b.status)" class="text-xs px-2 py-1 rounded-lg">{{ statusLabel(b.status) }}</span></td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(b.total_price) }}</td>
              </tr>
            </tbody>
          </table>
          <Pagination :links="bookings.links" :meta="bookings.meta ?? bookings" :showing-label="tr.geoPageShowing" />
        </div>

        <!-- Payments -->
        <div v-if="activeTab === 'payments'" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentCode }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentMethod }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentAmount }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.paymentStatus }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.paymentDate }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!payments.data.length"><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.playerNoPayments }}</td></tr>
              <tr v-for="p in payments.data" :key="p.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3">
                  <Link :href="`/admin/payments/${p.id}`" class="text-emerald-600 dark:text-emerald-400 hover:underline" dir="ltr">{{ p.booking_code ?? '#' + p.id }}</Link>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ tr['paymentProvider_' + (typeof p.provider === 'object' ? p.provider.value : p.provider)] ?? p.provider }}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmt(p.amount) }}</td>
                <td class="px-4 py-3"><span :class="payStatusTone(p.status)" class="text-xs px-2 py-1 rounded-lg">{{ tr['paymentStatus_' + (typeof p.status === 'object' ? p.status.value : p.status)] ?? p.status }}</span></td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(p.created_at) }}</td>
              </tr>
            </tbody>
          </table>
          <Pagination :links="payments.links" :meta="payments.meta ?? payments" :showing-label="tr.geoPageShowing" />
        </div>

        <!-- Favorites -->
        <div v-if="activeTab === 'favorites'" class="p-4">
          <div v-if="!favorite_venues.length" class="py-8 text-center text-gray-500 dark:text-gray-400 text-sm">{{ tr.playerNoFavorites }}</div>
          <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="v in favorite_venues" :key="v.id" class="bg-gray-50 dark:bg-gray-900/40 rounded-2xl overflow-hidden">
              <div class="h-24 bg-gray-200 dark:bg-gray-800">
                <img v-if="v.cover_url" :src="v.cover_url" :alt="t(v.name)" class="w-full h-full object-cover" />
              </div>
              <div class="p-3">
                <Link :href="`/admin/venues/${v.slug}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 text-sm">{{ t(v.name) }}</Link>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ t(v.club_name) }}</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ v.bookings_count }} {{ tr.playerBookingCount }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ConfirmModal
      v-if="modalConfig"
      :open="!!modalAction"
      :title="modalConfig.title"
      :subtitle="modalConfig.subtitle"
      :body="modalConfig.body"
      :warning="modalConfig.warning"
      :require-reason="modalConfig.requireReason"
      :reason-label="modalConfig.reasonLabel"
      :reason-placeholder="modalConfig.reasonPlaceholder"
      :email-label="tr.playerSendEmail"
      :confirm-label="modalConfig.confirmLabel"
      :confirm-tone="modalConfig.confirmTone"
      :cancel-label="tr.playerCancel"
      :processing="processing"
      @confirm="confirmAction"
      @cancel="close"
    />
  </AdminLayout>
</template>
