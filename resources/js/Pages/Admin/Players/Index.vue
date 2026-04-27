<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from '@/i18n'

interface CityOpt { id: number; name: string; name_ar: string | null }
interface Row {
  id: number; name: string; phone_number: string | null; email: string | null
  account_status: string; created_at: string | null; last_login_at: string | null
  bookings_count: number; total_spent: number; last_booking_date: string | null
  city: CityOpt | null
}
interface Stats { total: number; active: number; blocked: number; total_spending: number; avg_spending: number; zero_bookings: number }

interface Props {
  players: { data: Row[]; links: any[]; meta?: any }
  filters: Record<string, any>
  stats: Stats
  options: { cities: CityOpt[]; statuses: string[] }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  search: props.filters.search ?? '',
  city_id: props.filters.city_id ?? '',
  status: props.filters.status ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  spending_min: props.filters.spending_min ?? '',
  spending_max: props.filters.spending_max ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/players', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }

function statusTone(s: string) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'blocked') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

// Block/Unblock
type ModalAction = 'block' | 'unblock'
const modalAction = ref<ModalAction | null>(null)
const modalPlayer = ref<Row | null>(null)
const processing = ref(false)

function open(action: ModalAction, player: Row) { modalAction.value = action; modalPlayer.value = player }
function close() { modalAction.value = null; modalPlayer.value = null }

function confirmAction(payload: { reason?: string; send_email: boolean }) {
  if (!modalPlayer.value || !modalAction.value) return
  processing.value = true
  router.post(`/admin/players/${modalPlayer.value.id}/${modalAction.value}`, payload as any, {
    preserveScroll: true,
    onFinish: () => { processing.value = false; close() },
  })
}

const modalConfig = computed(() => {
  if (!modalAction.value || !modalPlayer.value) return null
  if (modalAction.value === 'block') return {
    title: tr.value.playerBlockTitle, subtitle: modalPlayer.value.name, warning: tr.value.playerBlockWarning,
    requireReason: true, reasonLabel: tr.value.playerReasonLabel, reasonPlaceholder: tr.value.playerBlockPlaceholder,
    confirmLabel: tr.value.playerBlockConfirm, confirmTone: 'red' as const,
  }
  return {
    title: tr.value.playerUnblockTitle, subtitle: modalPlayer.value.name, body: tr.value.playerUnblockBody,
    requireReason: false, confirmLabel: tr.value.playerUnblockConfirm, confirmTone: 'emerald' as const,
  }
})

const statCards = computed(() => [
  { label: tr.value.playerStatTotal, value: props.stats.total },
  { label: tr.value.playerStatActive, value: props.stats.active, tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.playerStatBlocked, value: props.stats.blocked, tone: 'text-red-600 dark:text-red-400' },
  { label: tr.value.playerStatZero, value: props.stats.zero_bookings, tone: 'text-gray-600 dark:text-gray-400' },
  { label: tr.value.playerStatSpending, value: fmt(props.stats.total_spending), tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.playerStatAvgSpending, value: fmt(props.stats.avg_spending), tone: 'text-blue-600 dark:text-blue-400' },
])
</script>

<template>
  <Head :title="tr.players" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.players }}</h1>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div v-for="(s, i) in statCards" :key="i" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.label }}</div>
          <div :class="s.tone ?? 'text-gray-900 dark:text-white'" class="text-xl sm:text-2xl font-bold mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input v-model="form.search" type="text" :placeholder="tr.playerSearchPlaceholder" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <select v-model="form.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.playerStatusAll }}</option>
          <option v-for="s in options.statuses" :key="s" :value="s">{{ tr['playerStatus_' + s] ?? s }}</option>
        </select>
        <select v-model="form.city_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.playerFilterCity }}</option>
          <option v-for="c in options.cities" :key="c.id" :value="c.id">{{ isAr ? c.name_ar || c.name : c.name }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="form.spending_min" type="number" min="0" :placeholder="tr.playerSpentMin" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          <input v-model="form.spending_max" type="number" min="0" :placeholder="tr.playerSpentMax" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.playerName }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playerPhone }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playerCity }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.playerStatus }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playerBookings }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playerSpent }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.playerLastBooking }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!players.data.length">
                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <tr v-for="p in players.data" :key="p.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3">
                  <Link :href="`/admin/players/${p.id}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">{{ p.name }}</Link>
                  <div class="text-xs text-gray-500 dark:text-gray-400 md:hidden" dir="ltr">{{ p.phone_number }}</div>
                </td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ p.phone_number ?? '—' }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ isAr ? p.city?.name_ar || p.city?.name : p.city?.name ?? '—' }}</td>
                <td class="px-4 py-3"><span :class="statusTone(p.account_status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">{{ tr['playerStatus_' + p.account_status] ?? p.account_status }}</span></td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ p.bookings_count }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(p.total_spent) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ p.last_booking_date ?? '—' }}</td>
                <td class="px-4 py-3">
                  <ActionsMenu :dir="isAr ? 'rtl' : 'ltr'" :items="[
                    { label: tr.playerView, href: `/admin/players/${p.id}` },
                    { label: tr.playerEdit, href: `/admin/players/${p.id}/edit` },
                    { label: tr.playerBlock, tone: 'danger', onClick: () => open('block', p), hidden: p.account_status === 'blocked' },
                    { label: tr.playerUnblock, tone: 'success', onClick: () => open('unblock', p), hidden: p.account_status !== 'blocked' },
                  ]" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="players.links" :meta="players.meta ?? players" :showing-label="tr.geoPageShowing" />
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
