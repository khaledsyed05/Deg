<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import MarkSettlementPaidModal from '@/Components/Admin/MarkSettlementPaidModal.vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface SettlementPayload {
  id: number
  status: string
  period_from: string | null
  period_to: string | null
  total_bookings: number
  total_venue_price: number
  total_commission: number
  total_cancellation_fees: number
  net_payable: number
  paid_amount: number
  payment_method: string | null
  payment_reference: string | null
  payment_notes: string | null
  admin_notes: string | null
  receipt_url: string | null
  receipt_filename: string | null
  settled_at: string | null
  settled_by: { id: number; name: string } | null
  notes_updated_at: string | null
  notes_updated_by: { id: number; name: string } | null
  created_at: string | null
  club: {
    id: number
    slug: string
    name: Translated
    phone_number: string | null
    address: string | null
    owner: { id: number; name: string; phone_number: string | null; email: string | null } | null
  } | null
}

interface ItemRow {
  id: number
  venue_price: number
  commission_amount: number
  club_payout_amount: number
  cancellation_comm: number
  booking: {
    id: number
    booking_code: string | null
    booking_date: string | null
    start_time: string
    end_time: string
    total_price: number
    deposit_amount: number
    remaining_amount: number
    status: string
    user: { id: number; name: string } | null
    venue: { id: number; name: Translated } | null
  } | null
}

interface Props {
  settlement: SettlementPayload
  items: ItemRow[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const markPaidOpen = ref(false)

const filters = reactive({
  venue_id: '' as number | '',
  status: '',
})

const venueOptions = computed(() => {
  const map = new Map<number, Translated>()
  props.items.forEach((it) => {
    if (it.booking?.venue) map.set(it.booking.venue.id, it.booking.venue.name)
  })
  return Array.from(map, ([id, name]) => ({ id, name }))
})

const filteredItems = computed(() => props.items.filter((it) => {
  if (filters.venue_id && it.booking?.venue?.id !== Number(filters.venue_id)) return false
  if (filters.status && it.booking?.status !== filters.status) return false
  return true
}))

const filteredTotals = computed(() => {
  let total = 0, deposit = 0, remaining = 0, venue_price = 0, commission = 0, cancellation = 0, club_net = 0
  for (const it of filteredItems.value) {
    total += it.booking?.total_price ?? 0
    deposit += it.booking?.deposit_amount ?? 0
    remaining += it.booking?.remaining_amount ?? 0
    venue_price += it.venue_price
    commission += it.commission_amount
    cancellation += it.cancellation_comm
    club_net += it.club_payout_amount
  }
  return { total, deposit, remaining, venue_price, commission, cancellation, club_net }
})

const notesForm = useForm({ admin_notes: props.settlement.admin_notes ?? '' })
function saveNotes() {
  notesForm.put(`/admin/settlements/${props.settlement.id}/notes`, { preserveScroll: true })
}

function t(o: Translated | null | undefined) { if (!o) return '—'; return (isAr.value ? o.ar : o.en) || o.ar || o.en || '—' }
function statusLabel(s: string) { return tr.value['settlementStatus_' + s] ?? s }
function statusTone(s: string) {
  if (s === 'paid') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'pending') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}
function paymentMethodLabel(m: string | null) { return m ? (tr.value['settlementPaymentMethod_' + m] ?? m) : '—' }
function bookingStatusLabel(s: string) { return tr.value['bookingStatus_' + s] ?? s }
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') } catch { return s }
}
function fmtDateTime(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleString(isAr.value ? 'ar-SY' : 'en-US') } catch { return s }
}
function copyId() { if (navigator.clipboard) navigator.clipboard.writeText(String(props.settlement.id)) }

const summaryCards = computed(() => [
  { icon: '📅', label: tr.value.settlementTotalBookings, value: props.settlement.total_bookings, tone: 'text-blue-600 dark:text-blue-400', dir: 'ltr' },
  { icon: '💰', label: tr.value.settlementTotalRevenue, value: fmt(props.settlement.total_venue_price), tone: 'text-emerald-600 dark:text-emerald-400', dir: 'ltr' },
  { icon: '📊', label: tr.value.settlementPlatformCommission, value: fmt(props.settlement.total_commission), tone: 'text-purple-600 dark:text-purple-400', dir: 'ltr' },
  { icon: '🏦', label: tr.value.settlementClubNet, value: fmt(props.settlement.net_payable), tone: 'text-emerald-700 dark:text-emerald-300', dir: 'ltr' },
])

const bookingStatusOptions = ['confirmed', 'scheduled', 'completed', 'cancelled']
</script>

<template>
  <Head :title="tr.settlementTitle(settlement.id)" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-6xl space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white" dir="ltr">{{ tr.settlementTitle(settlement.id) }}</h1>
            <span :class="statusTone(settlement.status)" class="text-xs px-2 py-1 rounded-lg">{{ statusLabel(settlement.status) }}</span>
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            <span v-if="settlement.club">{{ t(settlement.club.name) }}</span>
            <span v-if="settlement.period_from && settlement.period_to"> · <span dir="ltr">{{ tr.settlementPeriodFromTo(settlement.period_from, settlement.period_to) }}</span></span>
          </div>
        </div>
        <div class="flex gap-2 flex-wrap">
          <button v-if="settlement.status !== 'paid'" @click="markPaidOpen = true" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
            {{ tr.settlementMarkAsPaid }}
          </button>
          <a :href="`/admin/settlements/${settlement.id}/export-pdf`" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">{{ tr.settlementExportPdf }}</a>
          <a :href="`/admin/settlements/${settlement.id}/export-excel`" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">{{ tr.settlementExportExcel }}</a>
          <button @click="copyId" class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">{{ tr.settlementCopyId }}</button>
        </div>
      </div>

      <FlashBanner />

      <!-- Summary cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="(c, i) in summaryCards" :key="i"
          class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span class="text-base">{{ c.icon }}</span>
            <span>{{ c.label }}</span>
          </div>
          <div :class="c.tone" class="text-xl sm:text-2xl font-bold mt-2" :dir="c.dir">{{ c.value }}</div>
        </div>
      </div>

      <!-- Club info -->
      <section v-if="settlement.club" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ tr.settlementClubInfo }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubName }}</div>
            <Link :href="`/admin/clubs/${settlement.club.slug}`" class="text-gray-900 dark:text-white hover:underline">{{ t(settlement.club.name) }}</Link>
          </div>
          <div v-if="settlement.club.owner">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubOwner }}</div>
            <div class="text-gray-900 dark:text-white">{{ settlement.club.owner.name }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubPhone }}</div>
            <div class="text-gray-900 dark:text-white" dir="ltr">{{ settlement.club.phone_number ?? settlement.club.owner?.phone_number ?? '—' }}</div>
          </div>
          <div v-if="settlement.club.address" class="sm:col-span-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubAddress }}</div>
            <div class="text-gray-900 dark:text-white">{{ settlement.club.address }}</div>
          </div>
        </div>
      </section>

      <!-- Payment info (only if paid) -->
      <section v-if="settlement.status === 'paid'" class="bg-white dark:bg-gray-800/50 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-6">
        <h2 class="font-semibold text-emerald-700 dark:text-emerald-400 mb-4">{{ tr.settlementPaymentInfo }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.settlementPaymentDate }}</div>
            <div class="text-gray-900 dark:text-white" dir="ltr">{{ fmtDate(settlement.settled_at) }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.settlementPaymentMethod }}</div>
            <div class="text-gray-900 dark:text-white">{{ paymentMethodLabel(settlement.payment_method) }}</div>
          </div>
          <div v-if="settlement.payment_reference">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.settlementPaymentReference }}</div>
            <div class="text-gray-900 dark:text-white font-mono text-xs" dir="ltr">{{ settlement.payment_reference }}</div>
          </div>
          <div v-if="settlement.receipt_url">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.settlementReceipt }}</div>
            <a :href="settlement.receipt_url" target="_blank" rel="noopener" class="text-emerald-600 dark:text-emerald-400 hover:underline text-xs">
              📎 {{ settlement.receipt_filename }}
            </a>
          </div>
          <div v-if="settlement.settled_by">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.settlementPaidBy }}</div>
            <div class="text-gray-900 dark:text-white">{{ settlement.settled_by.name }}</div>
          </div>
          <div v-if="settlement.payment_notes" class="sm:col-span-3">
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.settlementPaymentNotes }}</div>
            <div class="text-gray-900 dark:text-white whitespace-pre-line">{{ settlement.payment_notes }}</div>
          </div>
        </div>
      </section>

      <!-- Bookings breakdown -->
      <section class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between gap-3 px-6 pt-6 pb-3 flex-wrap">
          <h2 class="font-semibold text-gray-900 dark:text-white">{{ tr.settlementBookingsBreakdown }} ({{ filteredItems.length }})</h2>
          <div class="flex gap-2 flex-wrap">
            <select v-model="filters.venue_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">{{ tr.settlementFilterVenue }}</option>
              <option v-for="v in venueOptions" :key="v.id" :value="v.id">{{ t(v.name) }}</option>
            </select>
            <select v-model="filters.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">{{ tr.settlementFilterStatus }}</option>
              <option v-for="s in bookingStatusOptions" :key="s" :value="s">{{ bookingStatusLabel(s) }}</option>
            </select>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingCode }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingDate }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementTimeSlot }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingPlayer }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingVenue }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingTotal }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementDepositAmount }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementRemainingAmount }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementCommissionAmount }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.settlementClubNetAmount }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.bookingStatus }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!filteredItems.length"><td colspan="11" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td></tr>
              <tr v-for="it in filteredItems" :key="it.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3" dir="ltr">
                  <Link v-if="it.booking" :href="`/admin/bookings/${it.booking.id}`" class="text-gray-900 dark:text-white hover:underline font-mono text-xs">{{ it.booking.booking_code }}</Link>
                  <span v-else>—</span>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(it.booking?.booking_date ?? null) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ it.booking?.start_time }}–{{ it.booking?.end_time }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ it.booking?.user?.name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ t(it.booking?.venue?.name ?? null) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(it.booking?.total_price ?? 0) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(it.booking?.deposit_amount ?? 0) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(it.booking?.remaining_amount ?? 0) }}</td>
                <td class="px-4 py-3 text-purple-600 dark:text-purple-400" dir="ltr">{{ fmt(it.commission_amount) }}</td>
                <td class="px-4 py-3 text-emerald-600 dark:text-emerald-400 font-semibold" dir="ltr">{{ fmt(it.club_payout_amount) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">{{ it.booking ? bookingStatusLabel(it.booking.status) : '—' }}</td>
              </tr>
            </tbody>
            <tfoot v-if="filteredItems.length" class="bg-gray-50 dark:bg-gray-800 text-sm font-semibold">
              <tr>
                <td colspan="5" class="px-4 py-3 text-start text-gray-700 dark:text-gray-300">{{ tr.settlementTotalsRow }}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white" dir="ltr">{{ fmt(filteredTotals.total) }}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white" dir="ltr">{{ fmt(filteredTotals.deposit) }}</td>
                <td class="px-4 py-3 text-gray-900 dark:text-white" dir="ltr">{{ fmt(filteredTotals.remaining) }}</td>
                <td class="px-4 py-3 text-purple-700 dark:text-purple-400" dir="ltr">{{ fmt(filteredTotals.commission) }}</td>
                <td class="px-4 py-3 text-emerald-700 dark:text-emerald-400" dir="ltr">{{ fmt(filteredTotals.club_net) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      <!-- Admin notes -->
      <section class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ tr.settlementAdminNotes }}</h2>
        <form @submit.prevent="saveNotes" class="space-y-3">
          <textarea
            v-model="notesForm.admin_notes"
            rows="5"
            maxlength="1000"
            :placeholder="tr.settlementNotesPlaceholder"
            class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
          ></textarea>
          <div class="flex items-center justify-between flex-wrap gap-2">
            <div class="text-xs text-gray-500 dark:text-gray-400">
              <span dir="ltr">{{ tr.settlementNotesCounter(notesForm.admin_notes.length) }}</span>
              <span v-if="settlement.notes_updated_at && settlement.notes_updated_by" class="ms-3" dir="ltr">
                · {{ tr.settlementNotesLastUpdate(fmtDateTime(settlement.notes_updated_at), settlement.notes_updated_by.name) }}
              </span>
            </div>
            <button type="submit" :disabled="notesForm.processing" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white transition-colors">
              {{ notesForm.processing ? tr.settlementNotesSaving : tr.settlementSaveNotes }}
            </button>
          </div>
        </form>
      </section>
    </div>

    <MarkSettlementPaidModal
      v-if="markPaidOpen"
      :settlement="{ id: settlement.id, net_payable: settlement.net_payable, period_from: settlement.period_from, period_to: settlement.period_to, club: settlement.club }"
      @close="markPaidOpen = false"
    />
  </AdminLayout>
</template>
