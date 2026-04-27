<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ConfirmModal from '@/Components/Admin/ConfirmModal.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface CityOpt { id: number; name: string; name_ar: string | null }
interface OwnerRef { id: number; name: string; phone_number: string | null; email: string | null }
interface Row {
  id: number
  slug: string
  name: Translated
  phone_number: string | null
  status: string
  venues_count: number
  total_revenue: number
  created_at: string | null
  approved_at: string | null
  city: CityOpt | null
  owner: OwnerRef | null
}

interface Props {
  clubs: { data: Row[]; links: any[]; meta?: any; from?: number; to?: number; total?: number }
  filters: Record<string, any>
  stats: { total: number; pending: number; active: number; suspended: number; rejected: number; total_revenue: number }
  options: { cities: CityOpt[]; statuses: string[] }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  search: props.filters.search ?? '',
  status: props.filters.status ?? '',
  city_id: props.filters.city_id ?? '',
  revenue_min: props.filters.revenue_min ?? '',
  revenue_max: props.filters.revenue_max ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/clubs', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

type Action = 'approve' | 'reject' | 'suspend' | 'unsuspend'
const modalAction = ref<Action | null>(null)
const modalClub = ref<Row | null>(null)
const processing = ref(false)

const expandedRows = ref(new Set<number>())
function toggleRow(id: number) {
  const next = new Set(expandedRows.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  expandedRows.value = next
}

function open(action: Action, club: Row) {
  modalAction.value = action
  modalClub.value = club
}

function close() {
  modalAction.value = null
  modalClub.value = null
}

function confirmAction(payload: { reason?: string; send_email: boolean }) {
  if (!modalClub.value || !modalAction.value) return
  processing.value = true
  router.post(
    `/admin/clubs/${modalClub.value.slug}/${modalAction.value}`,
    payload as any,
    {
      preserveScroll: true,
      onFinish: () => { processing.value = false; close() },
    }
  )
}

function t(name: Translated) {
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function cityLabel(c: CityOpt | null) {
  if (!c) return '—'
  return (isAr.value ? c.name_ar : c.name) || c.name
}
function statusLabel(s: string) {
  return tr.value['clubStatus_' + s] ?? s
}
function statusTone(s: string) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'pending_approval') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  if (s === 'suspended') return 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'
  if (s === 'rejected') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}
function fmt(n: number) {
  return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n)
}

const modalConfig = computed(() => {
  if (!modalAction.value || !modalClub.value) return null
  const cn = t(modalClub.value.name)
  switch (modalAction.value) {
    case 'approve': return {
      title: tr.value.clubApproveTitle,
      subtitle: cn,
      body: tr.value.clubApproveBody,
      requireReason: false,
      confirmLabel: tr.value.clubApproveConfirm,
      confirmTone: 'emerald' as const,
    }
    case 'reject': return {
      title: tr.value.clubRejectTitle,
      subtitle: cn,
      requireReason: true,
      reasonLabel: tr.value.clubReasonLabel,
      reasonPlaceholder: tr.value.clubRejectPlaceholder,
      confirmLabel: tr.value.clubRejectConfirm,
      confirmTone: 'red' as const,
    }
    case 'suspend': return {
      title: tr.value.clubSuspendTitle,
      subtitle: cn,
      warning: tr.value.clubSuspendWarning,
      requireReason: true,
      reasonLabel: tr.value.clubReasonLabel,
      reasonPlaceholder: tr.value.clubSuspendPlaceholder,
      confirmLabel: tr.value.clubSuspendConfirm,
      confirmTone: 'amber' as const,
    }
    case 'unsuspend': return {
      title: tr.value.clubUnsuspendTitle,
      subtitle: cn,
      body: tr.value.clubUnsuspendBody,
      requireReason: false,
      confirmLabel: tr.value.clubUnsuspendConfirm,
      confirmTone: 'emerald' as const,
    }
  }
})

const statCards = computed(() => [
  { label: tr.value.clubStatTotal, value: props.stats.total },
  { label: tr.value.clubStatPending, value: props.stats.pending, tone: 'text-amber-600 dark:text-amber-400' },
  { label: tr.value.clubStatActive, value: props.stats.active, tone: 'text-emerald-600 dark:text-emerald-400' },
  { label: tr.value.clubStatSuspended, value: props.stats.suspended, tone: 'text-gray-600 dark:text-gray-400' },
  { label: tr.value.clubStatRejected, value: props.stats.rejected, tone: 'text-red-600 dark:text-red-400' },
  { label: tr.value.clubStatRevenue, value: fmt(props.stats.total_revenue), tone: 'text-blue-600 dark:text-blue-400' },
])
</script>

<template>
  <Head :title="tr.clubs" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.clubs }}</h1>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div v-for="(s, i) in statCards" :key="i"
          class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ s.label }}</div>
          <div :class="s.tone ?? 'text-gray-900 dark:text-white'" class="text-xl sm:text-2xl font-bold mt-1" dir="ltr">{{ s.value }}</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input v-model="form.search" type="text" :placeholder="tr.clubSearchPlaceholder" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <select v-model="form.status" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.clubStatusAll }}</option>
          <option v-for="s in options.statuses" :key="s" :value="s">{{ statusLabel(s) }}</option>
        </select>
        <select v-model="form.city_id" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.clubFilterCity }}</option>
          <option v-for="c in options.cities" :key="c.id" :value="c.id">{{ cityLabel(c) }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="form.revenue_min" type="number" min="0" :placeholder="tr.clubRevenueMin" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          <input v-model="form.revenue_max" type="number" min="0" :placeholder="tr.clubRevenueMax" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
        <div class="grid grid-cols-2 gap-3 sm:col-span-2 lg:col-span-2">
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubJoinedFrom }}</label>
            <input v-model="form.date_from" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubJoinedTo }}</label>
            <input v-model="form.date_to" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white" />
          </div>
        </div>
      </div>

      <!-- Table: single structure, columns hidden/shown via Tailwind responsive classes -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <!-- Expand toggle: mobile only -->
                <th class="md:hidden w-9 px-2 py-3"></th>
                <th class="px-2 md:px-4 py-3 text-start font-medium">{{ tr.clubName }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.clubOwner }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.clubPhone }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.clubCity }}</th>
                <th class="px-2 md:px-4 py-3 text-start font-medium">{{ tr.clubStatus }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.clubVenuesCount }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.clubRevenue }}</th>
                <th class="px-2 md:px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!clubs.data.length">
                <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <template v-for="c in clubs.data" :key="c.id">
                <!-- Main row -->
                <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                  <!-- Chevron: mobile only -->
                  <td class="md:hidden px-2 py-2.5">
                    <button
                      @click="toggleRow(c.id)"
                      class="w-7 h-7 flex items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                      type="button"
                    >
                      <svg
                        class="w-4 h-4 transition-transform duration-200"
                        :class="expandedRows.has(c.id) ? 'rotate-90' : isAr ? '-rotate-90' : 'rotate-0'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                  </td>
                  <!-- Name: always visible -->
                  <td class="px-2 md:px-4 py-2.5 md:py-3 max-w-[130px] md:max-w-none truncate">
                    <Link :href="`/admin/clubs/${c.slug}`" class="font-medium text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">{{ t(c.name) }}</Link>
                  </td>
                  <!-- Owner: desktop only -->
                  <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[120px] truncate">{{ c.owner?.name ?? '—' }}</td>
                  <!-- Phone: desktop only -->
                  <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ c.phone_number ?? c.owner?.phone_number ?? '—' }}</td>
                  <!-- City: desktop only -->
                  <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400 max-w-[100px] truncate">{{ cityLabel(c.city) }}</td>
                  <!-- Status: always visible -->
                  <td class="px-2 md:px-4 py-2.5 md:py-3">
                    <span :class="statusTone(c.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">{{ statusLabel(c.status) }}</span>
                  </td>
                  <!-- Venues: desktop only -->
                  <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ c.venues_count }}</td>
                  <!-- Revenue: desktop only -->
                  <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(c.total_revenue) }}</td>
                  <!-- Actions: always visible -->
                  <td class="px-2 md:px-4 py-2.5 md:py-3 text-end">
                    <ActionsMenu :dir="isAr ? 'rtl' : 'ltr'" :items="[
                      { label: tr.clubView, href: `/admin/clubs/${c.slug}` },
                      { label: tr.geoEdit, href: `/admin/clubs/${c.slug}/edit` },
                      { label: tr.clubApprove, tone: 'success', onClick: () => open('approve', c), hidden: c.status !== 'pending_approval' },
                      { label: tr.clubReject, tone: 'danger', onClick: () => open('reject', c), hidden: c.status !== 'pending_approval' },
                      { label: tr.clubSuspend, tone: 'warning', onClick: () => open('suspend', c), hidden: c.status !== 'active' },
                      { label: tr.clubUnsuspend, tone: 'success', onClick: () => open('unsuspend', c), hidden: c.status !== 'suspended' },
                    ]" />
                  </td>
                </tr>
                <!-- Expanded detail row: mobile only -->
                <tr v-if="expandedRows.has(c.id)" class="md:hidden border-t border-gray-100 dark:border-gray-800">
                  <td colspan="4" class="bg-gray-50/70 dark:bg-gray-900/40 px-4 py-3">
                    <dl class="space-y-2 text-xs">
                      <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubOwner }}</dt>
                        <dd class="text-gray-900 dark:text-white font-medium truncate text-end">{{ c.owner?.name ?? '—' }}</dd>
                      </div>
                      <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubPhone }}</dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ c.phone_number ?? c.owner?.phone_number ?? '—' }}</dd>
                      </div>
                      <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubCity }}</dt>
                        <dd class="text-gray-900 dark:text-white font-medium truncate text-end">{{ cityLabel(c.city) }}</dd>
                      </div>
                      <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubVenuesCount }}</dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ c.venues_count }}</dd>
                      </div>
                      <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500 dark:text-gray-400 shrink-0">{{ tr.clubRevenue }}</dt>
                        <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ fmt(c.total_revenue) }}</dd>
                      </div>
                    </dl>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
        <Pagination :links="clubs.links" :meta="clubs.meta ?? clubs" :showing-label="tr.geoPageShowing" />
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
