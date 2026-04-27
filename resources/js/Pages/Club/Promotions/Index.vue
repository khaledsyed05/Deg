<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import ActionsMenu from '@/Components/Admin/ActionsMenu.vue'
import { computed, reactive, watch } from 'vue'
import { useI18n } from '@/i18n'

interface PromoRow {
  id: number
  slug: string
  code: string
  discount_type: string
  discount_value: number
  uses_count: number
  max_uses: number | null
  valid_from: string | null
  valid_until: string | null
  is_active: boolean
}

interface Props {
  stats: {
    active_count: number
    total_uses: number
    total_discount: number
  }
  promotions: { data: PromoRow[]; links: any[]; meta?: any }
}

defineProps<Props>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function discountLabel(row: PromoRow) {
  if (row.discount_type === 'percentage') return row.discount_value + '%'
  return new Intl.NumberFormat('ar-SY').format(row.discount_value) + ' ل.س'
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

function toggleActive(slug: string, current: boolean) {
  router.post(`/club/promotions/${slug}/toggle`, {}, { preserveScroll: true })
}
</script>

<template>
  <Head title="الترقيات والخصومات" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">الترقيات والخصومات</h1>
        <Link href="/club/promotions/create" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors shadow-sm">
          إضافة ترقية
        </Link>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">الترقيات النشطة</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ stats.active_count }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الاستخدامات</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ stats.total_uses }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الخصم</div>
          <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1" dir="ltr">{{ fmt(stats.total_discount) }}</div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">الكود</th>
                <th class="px-4 py-3 text-start font-medium">الخصم</th>
                <th class="px-4 py-3 text-start font-medium">الاستخدامات</th>
                <th class="hidden sm:table-cell px-4 py-3 text-start font-medium">صالح من</th>
                <th class="hidden sm:table-cell px-4 py-3 text-start font-medium">صالح حتى</th>
                <th class="px-4 py-3 text-start font-medium">الحالة</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!promotions.data.length">
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد ترقيات</td>
              </tr>
              <tr
                v-for="p in promotions.data"
                :key="p.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3">
                  <Link :href="`/club/promotions/${p.slug}`" class="font-mono font-semibold text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">
                    {{ p.code }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium" dir="ltr">{{ discountLabel(p) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">
                  {{ p.uses_count }}
                  <span v-if="p.max_uses" class="text-gray-400"> / {{ p.max_uses }}</span>
                </td>
                <td class="hidden sm:table-cell px-4 py-3 text-gray-500 dark:text-gray-400" dir="ltr">{{ fmtDate(p.valid_from) }}</td>
                <td class="hidden sm:table-cell px-4 py-3 text-gray-500 dark:text-gray-400" dir="ltr">{{ fmtDate(p.valid_until) }}</td>
                <td class="px-4 py-3">
                  <span
                    :class="p.is_active
                      ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                      : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                    class="text-xs px-2 py-1 rounded-lg"
                  >
                    {{ p.is_active ? 'نشط' : 'متوقف' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-end">
                  <ActionsMenu
                    dir="rtl"
                    :items="[
                      { label: 'عرض', href: `/club/promotions/${p.slug}` },
                      { label: 'تعديل', href: `/club/promotions/${p.slug}/edit` },
                      { label: p.is_active ? 'إيقاف' : 'تفعيل', tone: p.is_active ? 'warning' : 'success', onClick: () => toggleActive(p.slug, p.is_active) },
                    ]"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="promotions.links" :meta="promotions.meta ?? promotions" showing-label="عرض" />
      </div>
    </div>
  </ClubLayout>
</template>
