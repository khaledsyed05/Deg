<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface ClubRow {
  id: number; slug: string; name: Translated
  city: { id: number; name: string; name_ar: string | null } | null
  default_rate: number | null; override_rate: number | null
  effective_from: string | null; note: string | null
  has_override: boolean; revenue_30d: number; commission_30d: number
}
interface HistoryRow { id: number; description: string; properties: any; causer: { id: number; name: string } | null; created_at: string | null }
interface Analytics { total_commission: number; avg_rate: number; top_earning_clubs: any[]; trend: { day: string; commission: number }[] }
interface PlatformDefault { rate: number | null; clubs_using: number; revenue_30d: number; commission_30d: number; effective_from: string | null; note: string | null }

interface Props {
  platform_default: PlatformDefault
  change_history: HistoryRow[]
  club_overrides: { data: ClubRow[]; links: any[]; meta?: any }
  filters: Record<string, any>
  analytics: Analytics
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  search: props.filters.search ?? '',
  has_override: props.filters.has_override ?? '',
  rate_min: props.filters.rate_min ?? '',
  rate_max: props.filters.rate_max ?? '',
  city_id: props.filters.city_id ?? '',
})

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/commissions', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

function t(name: Translated) { return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—' }
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }

// Trend chart
const trendMax = computed(() => Math.max(...props.analytics.trend.map(r => r.commission), 1))

// Edit default rate modal
const showDefaultModal = ref(false)
const defaultForm = reactive({ rate: props.platform_default.rate ?? 10, effective_from: new Date().toISOString().split('T')[0], reason: '' })
const defaultProcessing = ref(false)
function submitDefault() {
  defaultProcessing.value = true
  router.post('/admin/commissions/default', defaultForm as any, {
    preserveScroll: true,
    onFinish: () => { defaultProcessing.value = false; showDefaultModal.value = false },
  })
}

// Add override modal
const showOverrideModal = ref(false)
const overrideForm = reactive({ club_id: '', rate: 10, effective_from: new Date().toISOString().split('T')[0], reason: '' })
const overrideProcessing = ref(false)
function submitOverride() {
  overrideProcessing.value = true
  router.post('/admin/commissions/overrides', overrideForm as any, {
    preserveScroll: true,
    onFinish: () => { overrideProcessing.value = false; showOverrideModal.value = false },
  })
}

// Remove override
const removeForm = reactive({ reason: '' })
const removingClub = ref<ClubRow | null>(null)
const removeProcessing = ref(false)
function openRemove(club: ClubRow) { removingClub.value = club; removeForm.reason = '' }
function closeRemove() { removingClub.value = null }
function submitRemove() {
  if (!removingClub.value) return
  removeProcessing.value = true
  router.delete(`/admin/commissions/overrides/${removingClub.value.id}`, { data: removeForm as any, preserveScroll: true, onFinish: () => { removeProcessing.value = false; closeRemove() } })
}
</script>

<template>
  <Head :title="tr.commissionsTitle" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.commissionsTitle }}</h1>
        <button @click="showOverrideModal = true" class="px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl transition-colors">
          {{ tr.commissionsAddOverride }}
        </button>
      </div>

      <FlashBanner />

      <!-- Platform default card -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 mb-5">
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm mb-1">{{ tr.commissionsPlatformDefault }}</h2>
            <div class="flex items-center gap-3 flex-wrap">
              <span class="text-3xl font-bold text-emerald-600 dark:text-emerald-400" dir="ltr">{{ platform_default.rate ?? '—' }}%</span>
              <div class="text-sm text-gray-500 dark:text-gray-400 space-y-0.5">
                <div>{{ tr.commissionsClubsUsing }}: <strong class="text-gray-700 dark:text-gray-300">{{ platform_default.clubs_using }}</strong></div>
                <div>{{ tr.commissionsRevenue30d }}: <strong class="text-gray-700 dark:text-gray-300" dir="ltr">{{ fmt(platform_default.revenue_30d) }}</strong></div>
                <div>{{ tr.commissionsCommission30d }}: <strong class="text-emerald-600 dark:text-emerald-400" dir="ltr">{{ fmt(platform_default.commission_30d) }}</strong></div>
                <div v-if="platform_default.effective_from">{{ tr.commissionsEffectiveFrom }}: <strong class="text-gray-700 dark:text-gray-300" dir="ltr">{{ platform_default.effective_from }}</strong></div>
              </div>
            </div>
            <p v-if="platform_default.note" class="text-xs text-gray-400 mt-2">{{ platform_default.note }}</p>
          </div>
          <button @click="showDefaultModal = true" class="px-3 py-2 text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-xl transition-colors">
            {{ tr.commissionsEditDefault }}
          </button>
        </div>
      </div>

      <!-- Analytics -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-3">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.commissionsAnalyticsTitle }}</h2>
          <div>
            <div class="text-xs text-gray-500">{{ tr.commissionsTotalCommission }}</div>
            <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400" dir="ltr">{{ fmt(analytics.total_commission) }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">{{ tr.commissionsAvgRate }}</div>
            <div class="text-xl font-bold text-gray-900 dark:text-white" dir="ltr">{{ analytics.avg_rate }}%</div>
          </div>
        </div>

        <!-- Top earning clubs -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.commissionsTopClubs }}</h2>
          <div class="space-y-2">
            <div v-for="(c, i) in analytics.top_earning_clubs.slice(0,5)" :key="c.club_id" class="flex items-center justify-between text-sm">
              <span class="text-gray-700 dark:text-gray-300 text-xs truncate flex-1">
                <span class="text-gray-400 me-1" dir="ltr">{{ i+1 }}.</span>{{ t(c.club_name) }}
              </span>
              <span class="text-emerald-600 dark:text-emerald-400 font-medium text-xs ms-2" dir="ltr">{{ fmt(c.commission) }}</span>
            </div>
          </div>
        </div>

        <!-- Trend chart -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.commissionsTrend }}</h2>
          <div v-if="!analytics.trend.length" class="text-sm text-gray-400 text-center py-4">{{ tr.revenueEmptyData }}</div>
          <div v-else class="flex items-end gap-0.5 h-24">
            <div v-for="row in analytics.trend" :key="row.day" class="flex-1 flex flex-col items-center">
              <div :style="`height: ${Math.round((row.commission / trendMax) * 100)}%; min-height: ${row.commission > 0 ? 2 : 0}px`"
                class="w-full bg-emerald-500 rounded-t-sm"
                :title="`${row.day}: ${fmt(row.commission)}`"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <input v-model="form.search" type="text" :placeholder="tr.clubSearchPlaceholder" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <select v-model="form.has_override" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.commissionsFilterAll }}</option>
          <option value="yes">{{ tr.commissionsFilterYes }}</option>
          <option value="no">{{ tr.commissionsFilterNo }}</option>
        </select>
        <div class="grid grid-cols-2 gap-3">
          <input v-model="form.rate_min" type="number" min="0" max="100" :placeholder="tr.commissionsRateMin" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          <input v-model="form.rate_max" type="number" min="0" max="100" :placeholder="tr.commissionsRateMax" dir="ltr" class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        </div>
      </div>

      <!-- Club overrides table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.commissionsClubOverrides }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.commissionsClubCol }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.commissionsCityCol }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.commissionsEffectiveRate }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.commissionsRevenue30d }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.commissionsCommission30d }}</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!club_overrides.data.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <tr v-for="c in club_overrides.data" :key="c.id" class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ t(c.name) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ c.city ? (isAr ? c.city.name_ar || c.city.name : c.city.name) : '—' }}</td>
                <td class="px-4 py-3">
                  <span v-if="c.has_override" class="text-sm font-semibold text-purple-600 dark:text-purple-400" dir="ltr">{{ c.override_rate }}%</span>
                  <span v-else class="text-sm text-gray-500" dir="ltr">{{ c.default_rate }}% <span class="text-xs text-gray-400">({{ tr.commissionsDefaultApplied }})</span></span>
                </td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmt(c.revenue_30d) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-emerald-600 dark:text-emerald-400" dir="ltr">{{ fmt(c.commission_30d) }}</td>
                <td class="px-4 py-3">
                  <button v-if="c.has_override" @click="openRemove(c)" class="text-xs text-red-500 hover:underline">{{ tr.commissionsRemoveOverride }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="club_overrides.links" :meta="club_overrides.meta ?? club_overrides" :showing-label="tr.geoPageShowing" />
      </div>

      <!-- Change history -->
      <div v-if="change_history.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.commissionsChangeHistory }}</h2>
        <div class="space-y-2">
          <div v-for="h in change_history" :key="h.id" class="flex items-start gap-3 text-sm border-b border-gray-100 dark:border-gray-800 pb-2 last:border-0 last:pb-0">
            <div class="flex-1">
              <span class="font-medium text-gray-900 dark:text-white">{{ (tr.commissionsChangeDesc as any)?.[h.description] ?? h.description }}</span>
              <span v-if="h.causer" class="text-gray-500 ms-2 text-xs">{{ h.causer.name }}</span>
            </div>
            <span class="text-xs text-gray-400 whitespace-nowrap" dir="ltr">{{ fmtDate(h.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit default rate modal -->
    <Teleport to="body">
      <div v-if="showDefaultModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md shadow-2xl">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ tr.commissionsEditDefaultTitle }}</h2>
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsDefaultRate }} (%)</label>
              <input v-model="defaultForm.rate" type="number" min="0" max="50" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsEffectiveFrom }}</label>
              <input v-model="defaultForm.effective_from" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsReason }}</label>
              <textarea v-model="defaultForm.reason" rows="3" :placeholder="tr.commissionsReasonMin20" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-5">
            <button @click="showDefaultModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ tr.clubCancel }}</button>
            <button @click="submitDefault" :disabled="defaultProcessing" class="px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl">
              {{ defaultProcessing ? tr.commissionsSaving : tr.commissionsConfirm }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Add override modal -->
    <Teleport to="body">
      <div v-if="showOverrideModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-md shadow-2xl">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4">{{ tr.commissionsAddOverrideTitle }}</h2>
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsClubCol }}</label>
              <select v-model="overrideForm.club_id" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none">
                <option value="">{{ tr.commissionsSelectClub }}</option>
                <option v-for="c in club_overrides.data" :key="c.id" :value="c.id">{{ t(c.name) }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsOverrideRate }} (%)</label>
              <input v-model="overrideForm.rate" type="number" min="0" max="50" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsEffectiveFrom }}</label>
              <input v-model="overrideForm.effective_from" type="date" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsReason }}</label>
              <textarea v-model="overrideForm.reason" rows="3" :placeholder="tr.commissionsReasonMin10" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>
          </div>
          <div class="flex justify-end gap-3 mt-5">
            <button @click="showOverrideModal = false" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ tr.clubCancel }}</button>
            <button @click="submitOverride" :disabled="overrideProcessing" class="px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl">
              {{ overrideProcessing ? tr.commissionsSaving : tr.commissionsConfirm }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Remove override modal -->
    <Teleport to="body">
      <div v-if="removingClub" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-1">{{ tr.commissionsRemoveOverrideTitle }}</h2>
          <p class="text-sm text-gray-500 mb-4">{{ t(removingClub.name) }} — {{ tr.commissionsRemoveOverrideBody }}</p>
          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.commissionsReason }}</label>
            <textarea v-model="removeForm.reason" rows="3" :placeholder="tr.commissionsReasonMin10" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
          </div>
          <div class="flex justify-end gap-3 mt-4">
            <button @click="closeRemove" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ tr.clubCancel }}</button>
            <button @click="submitRemove" :disabled="removeProcessing" class="px-4 py-2 text-sm font-semibold bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-xl">
              {{ removeProcessing ? tr.commissionsSaving : tr.commissionsRemoveOverride }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>
