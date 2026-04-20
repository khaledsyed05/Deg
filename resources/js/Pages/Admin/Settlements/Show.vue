<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Settlement {
  id: number
  club: { id: number; name: string }
  total_amount: number
  status: string
  period_from: string
  period_to: string
  created_at: string
}

defineProps<{ settlement: Settlement }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)
</script>

<template>
  <Head :title="tr.settlement(settlement.id)" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl">
      <a
        href="/admin/settlements"
        class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors mb-6 group"
      >
        <span class="text-base group-hover:-translate-x-0.5 transition-transform">{{ tr.backArrow }}</span>
        {{ tr.backToSettlements }}
      </a>

      <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        {{ tr.settlement(settlement.id) }}
      </h1>

      <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x dark:divide-gray-800 divide-gray-50">
          <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">{{ tr.club }}</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ settlement.club?.name }}</p>
          </div>
          <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">{{ tr.status }}</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ settlement.status }}</p>
          </div>
          <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">{{ tr.periodFrom }}</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ settlement.period_from }}</p>
          </div>
          <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">{{ tr.periodTo }}</p>
            <p class="font-semibold text-gray-900 dark:text-white">{{ settlement.period_to }}</p>
          </div>
          <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">{{ tr.total }}</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
              {{ (settlement.total_amount || 0).toLocaleString() }}
              <span class="text-sm font-medium text-gray-400 ms-1">{{ locale === 'ar' ? 'ل.س' : 'SYP' }}</span>
            </p>
          </div>
          <div class="p-5">
            <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wide mb-1">{{ tr.createdAt }}</p>
            <p class="font-semibold text-gray-900 dark:text-white">
              {{ new Date(settlement.created_at).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-GB') }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
