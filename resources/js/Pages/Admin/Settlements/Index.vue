<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Club { id: number; name: string }

interface Settlement {
  id: number
  club: Club
  total_amount: number
  status: string
  period_from: string
  period_to: string
  created_at: string
}

interface PaginatedSettlements {
  data: Settlement[]
  current_page: number
  last_page: number
  total: number
}

defineProps<{ settlements: PaginatedSettlements; clubs: Club[] }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)

const showForm = ref(false)
const form = ref({ club_id: '', from_date: '', to_date: '' })
const isSubmitting = ref(false)

function submitForm() {
  isSubmitting.value = true
  router.post('/admin/settlements', form.value, {
    onSuccess: () => {
      showForm.value = false
      form.value = { club_id: '', from_date: '', to_date: '' }
    },
    onFinish: () => { isSubmitting.value = false },
  })
}

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300',
  paid: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
  pending: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
}

function statusLabel(status: string): string {
  const map: Record<string, keyof typeof tr.value> = {
    draft: 'statusDraft',
    paid: 'statusPaid',
    pending: 'statusPending',
  }
  const key = map[status]
  return key ? (tr.value[key] as string) : status
}
</script>

<template>
  <Head :title="tr.settlements" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tr.settlements }}</h1>
        <button
          @click="showForm = !showForm"
          class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm"
        >
          {{ tr.createSettlement }}
        </button>
      </div>

      <!-- Create Form -->
      <div v-if="showForm" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 mb-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ tr.createNewSettlement }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.club }}</label>
            <select
              v-model="form.club_id"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
            >
              <option value="">{{ tr.selectClub }}</option>
              <option v-for="club in clubs" :key="club.id" :value="club.id">{{ club.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.fromDate }}</label>
            <input
              v-model="form.from_date"
              type="date"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">{{ tr.toDate }}</label>
            <input
              v-model="form.to_date"
              type="date"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
            />
          </div>
        </div>
        <div class="flex gap-2 mt-4">
          <button
            @click="submitForm"
            :disabled="!form.club_id || !form.from_date || !form.to_date || isSubmitting"
            class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl disabled:opacity-40 transition-colors"
          >
            {{ tr.createBtn }}
          </button>
          <button
            @click="showForm = false"
            class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-semibold px-4 py-2.5 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
          >
            {{ tr.cancel }}
          </button>
        </div>
      </div>

      <!-- Settlements Table -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
        <div v-if="settlements.data.length === 0" class="p-14 text-center">
          <div class="w-14 h-14 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center text-2xl mx-auto mb-4">💰</div>
          <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">{{ tr.noSettlements }}</p>
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b dark:border-gray-800 bg-gray-50/80 dark:bg-gray-800/50">
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.club }}</th>
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide hidden md:table-cell">{{ tr.period }}</th>
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.total }}</th>
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.status }}</th>
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.details }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
              <tr
                v-for="s in settlements.data"
                :key="s.id"
                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
              >
                <td class="px-5 py-4 font-medium text-gray-900 dark:text-white">{{ s.club?.name }}</td>
                <td class="px-5 py-4 text-gray-500 dark:text-gray-400 text-xs hidden md:table-cell">{{ s.period_from }} — {{ s.period_to }}</td>
                <td class="px-5 py-4 font-semibold text-gray-900 dark:text-white">
                  {{ (s.total_amount || 0).toLocaleString() }}
                  <span class="text-xs text-gray-400 ms-1">{{ locale === 'ar' ? 'ل.س' : 'SYP' }}</span>
                </td>
                <td class="px-5 py-4">
                  <span
                    class="text-xs font-semibold px-2.5 py-1 rounded-full"
                    :class="statusColors[s.status] ?? 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300'"
                  >
                    {{ statusLabel(s.status) }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <a
                    :href="`/admin/settlements/${s.id}`"
                    class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 font-semibold text-xs underline-offset-2 hover:underline"
                  >
                    {{ tr.view }}
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <p v-if="settlements.last_page > 1" class="mt-4 text-xs text-gray-500 dark:text-gray-400 text-center">
        {{ tr.page(settlements.current_page, settlements.last_page, settlements.total) }}
      </p>
    </div>
  </AdminLayout>
</template>
