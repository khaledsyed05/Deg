<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface CityOpt { id: number; name: string; name_ar: string | null }
interface Owner { id: number; name: string; phone_number: string | null; email: string | null }
interface VenueRow {
  id: number; slug: string; name: Translated; status: string
  avg_rating: number | null; price_from: number
  category: { id: number; name: Translated } | null
  bookings_count: number; revenue: number
}
interface Settlement {
  id: number; club_id: number; period_from: string; period_to: string
  net_payable: number; paid_amount: number; status: string; settled_at: string | null
}
interface Club {
  id: number; slug: string; name: Translated; description: Translated
  phone_number: string | null; address: string | null; status: string
  is_featured: boolean; commission_rate: number | null; avg_rating: number | null
  reviews_count: number; city_id: number | null; city: CityOpt | null; owner: Owner | null
  rejection_reason: string | null; suspension_reason: string | null
  created_at: string | null; approved_at: string | null; rejected_at: string | null
  suspended_at: string | null; unsuspended_at: string | null
}
interface Analytics { total_venues: number; total_bookings: number; total_revenue: number; average_rating: number }
interface RevenueMonth { month: string; month_en: string; revenue: number }

interface Props {
  club: Club
  analytics: Analytics
  revenue_by_month: RevenueMonth[]
  venues: VenueRow[]
  settlements: Settlement[]
  whatsapp_status: any
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const activeTab = ref<'venues' | 'settlements' | 'whatsapp'>('venues')

function t(name: Translated) {
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function fmt(n: number) {
  return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n)
}
function fmtDate(d: string | null) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US')
}

type ModalAction = 'approve' | 'reject' | 'suspend' | 'unsuspend'
const modalAction = ref<ModalAction | null>(null)
const processing = ref(false)

function open(action: ModalAction) { modalAction.value = action }
function close() { modalAction.value = null }

function confirmAction(payload: { reason?: string; send_email: boolean }) {
  if (!modalAction.value) return
  processing.value = true
  router.post(
    `/admin/clubs/${props.club.slug}/${modalAction.value}`,
    payload as any,
    { preserveScroll: true, onFinish: () => { processing.value = false; close() } }
  )
}

const modalConfig = computed(() => {
  if (!modalAction.value) return null
  const cn = t(props.club.name)
  switch (modalAction.value) {
    case 'approve': return { title: tr.value.clubApproveTitle, subtitle: cn, body: tr.value.clubApproveBody, requireReason: false, confirmLabel: tr.value.clubApproveConfirm, confirmTone: 'emerald' as const }
    case 'reject': return { title: tr.value.clubRejectTitle, subtitle: cn, requireReason: true, reasonLabel: tr.value.clubReasonLabel, reasonPlaceholder: tr.value.clubRejectPlaceholder, confirmLabel: tr.value.clubRejectConfirm, confirmTone: 'red' as const }
    case 'suspend': return { title: tr.value.clubSuspendTitle, subtitle: cn, warning: tr.value.clubSuspendWarning, requireReason: true, reasonLabel: tr.value.clubReasonLabel, reasonPlaceholder: tr.value.clubSuspendPlaceholder, confirmLabel: tr.value.clubSuspendConfirm, confirmTone: 'amber' as const }
    case 'unsuspend': return { title: tr.value.clubUnsuspendTitle, subtitle: cn, body: tr.value.clubUnsuspendBody, requireReason: false, confirmLabel: tr.value.clubUnsuspendConfirm, confirmTone: 'emerald' as const }
  }
})

function statusTone(s: string) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'pending_approval') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  if (s === 'suspended') return 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'
  if (s === 'rejected') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

const maxRevenue = computed(() => Math.max(...props.revenue_by_month.map(m => m.revenue), 1))
</script>

<template>
  <Head :title="t(club.name)" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <Link href="/admin/clubs" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
          </Link>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ t(club.name) }}</h1>
          <span :class="statusTone(club.status)" class="text-xs px-2 py-1 rounded-lg">{{ tr['clubStatus_' + club.status] ?? club.status }}</span>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <Link :href="`/admin/clubs/${club.slug}/edit`" class="px-4 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            {{ tr.geoEdit }}
          </Link>
          <button v-if="club.status === 'pending_approval'" @click="open('approve')" class="px-4 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">{{ tr.clubApprove }}</button>
          <button v-if="club.status === 'pending_approval'" @click="open('reject')" class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors">{{ tr.clubReject }}</button>
          <button v-if="club.status === 'active'" @click="open('suspend')" class="px-4 py-2 text-sm font-medium bg-amber-600 hover:bg-amber-700 text-white rounded-xl transition-colors">{{ tr.clubSuspend }}</button>
          <button v-if="club.status === 'suspended'" @click="open('unsuspend')" class="px-4 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">{{ tr.clubUnsuspend }}</button>
        </div>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubTotalVenues }}</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ analytics.total_venues }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubTotalBookings }}</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ fmt(analytics.total_bookings) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubTotalRevenue }}</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ fmt(analytics.total_revenue) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubAvgRating }}</div>
          <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1" dir="ltr">{{ analytics.average_rating || '—' }}</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Info Card -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.clubOwnerInfo }}</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubOwner }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ club.owner?.name ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubPhone }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ club.phone_number ?? club.owner?.phone_number ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubEmail }}</dt><dd class="text-gray-900 dark:text-white font-medium truncate text-end">{{ club.owner?.email ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubCity }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ isAr ? club.city?.name_ar || club.city?.name : club.city?.name ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubAddress }}</dt><dd class="text-gray-900 dark:text-white font-medium text-end">{{ club.address ?? '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubJoined }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmtDate(club.created_at) }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubApprovedAt }}</dt><dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmtDate(club.approved_at) }}</dd></div>
            <div v-if="club.rejection_reason" class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubRejectionReason }}</dt><dd class="text-red-600 dark:text-red-400 font-medium text-end">{{ club.rejection_reason }}</dd></div>
            <div v-if="club.suspension_reason" class="flex justify-between gap-3"><dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubSuspensionReason }}</dt><dd class="text-amber-600 dark:text-amber-400 font-medium text-end">{{ club.suspension_reason }}</dd></div>
          </dl>
        </div>

        <!-- Revenue Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ tr.clubRevenueChart }}</h2>
          <div v-if="revenue_by_month.every(m => m.revenue === 0)" class="h-32 flex items-center justify-center text-gray-400 dark:text-gray-500 text-sm">{{ tr.noRevenueData }}</div>
          <div v-else class="flex items-end gap-2 h-32">
            <div v-for="m in revenue_by_month" :key="m.month" class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
              <div class="w-full bg-emerald-500/80 dark:bg-emerald-500/60 rounded-t-md transition-all" :style="{ height: Math.round((m.revenue / maxRevenue) * 100) + '%', minHeight: m.revenue > 0 ? '4px' : '0' }" :title="fmt(m.revenue)"></div>
              <div class="text-xs text-gray-500 dark:text-gray-400 truncate w-full text-center">{{ isAr ? m.month.split(' ')[0] : m.month_en.split(' ')[0] }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="flex border-b border-gray-200 dark:border-gray-700">
          <button v-for="tab in ['venues','settlements','whatsapp']" :key="tab" @click="activeTab = tab as any"
            class="px-4 py-3 text-sm font-medium transition-colors"
            :class="activeTab === tab ? 'text-emerald-600 dark:text-emerald-400 border-b-2 border-emerald-600 dark:border-emerald-400' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'">
            {{ tab === 'venues' ? tr.clubTabVenues : tab === 'settlements' ? tr.clubTabSettlements : tr.clubTabWhatsapp }}
          </button>
        </div>

        <!-- Venues Tab -->
        <div v-if="activeTab === 'venues'" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueName }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueCategory }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueStatus }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueBookings }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueRevenue }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.venueAvgRating }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!venues.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.clubNoVenues }}</td>
              </tr>
              <tr v-for="v in venues" :key="v.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3">
                  <Link :href="`/admin/venues/${v.slug}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">{{ t(v.name) }}</Link>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ v.category ? t(v.category.name) : '—' }}</td>
                <td class="px-4 py-3">
                  <span class="text-xs px-2 py-1 rounded-lg" :class="v.status === 'active' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'">{{ tr['venueStatus' + v.status.charAt(0).toUpperCase() + v.status.slice(1)] ?? v.status }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.bookings_count }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(v.revenue) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ v.avg_rating ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Settlements Tab -->
        <div v-if="activeTab === 'settlements'" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">#</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementPeriod }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementNetPayable }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementStatus }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementSettledAt }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!settlements.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.clubNoSettlements }}</td>
              </tr>
              <tr v-for="s in settlements" :key="s.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium">#{{ s.id }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ s.period_from }} — {{ s.period_to }}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmt(s.net_payable) }}</td>
                <td class="px-4 py-3">
                  <span class="text-xs px-2 py-1 rounded-lg" :class="s.status === 'paid' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'">{{ tr['settlementStatus_' + s.status] ?? s.status }}</span>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(s.settled_at) }}</td>
                <td class="px-4 py-3">
                  <Link :href="`/admin/settlements/${s.id}`" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">{{ tr.details }}</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- WhatsApp Tab -->
        <div v-if="activeTab === 'whatsapp'" class="p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ tr.clubTabWhatsapp }}</h3>
            <Link :href="`/admin/clubs/${club.slug}/whatsapp`" class="px-4 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">
              {{ tr.clubWhatsappManage }}
            </Link>
          </div>
          <div class="flex items-center gap-3">
            <div :class="whatsapp_status?.status === 'connected' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 dark:bg-gray-800'" class="w-10 h-10 rounded-full flex items-center justify-center">
              <svg class="w-5 h-5" :class="whatsapp_status?.status === 'connected' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ whatsapp_status?.status === 'connected' ? tr.clubWhatsappConnected : tr.clubWhatsappDisconnected }}</p>
              <p class="text-xs text-gray-500 dark:text-gray-400">{{ whatsapp_status?.phone_number ?? '' }}</p>
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
      :email-label="tr.clubSendEmail"
      :confirm-label="modalConfig.confirmLabel"
      :confirm-tone="modalConfig.confirmTone"
      :cancel-label="tr.clubCancel"
      :processing="processing"
      @confirm="confirmAction"
      @cancel="close"
    />
  </AdminLayout>
</template>
