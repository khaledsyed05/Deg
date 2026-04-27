<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { computed, reactive, watch } from 'vue'
import { useI18n } from '@/i18n'

interface ReviewRow {
  id: number
  rating: number
  comment: string | null
  created_at: string
  user: { name: string } | null
  venue: { name: string | { ar?: string; en?: string } }
}

interface RatingStat { rating: number; count: number; percentage: number }

interface Props {
  stats: {
    avg_rating: number | null
    total_reviews: number
    this_month: number
    pending_replies: number
  }
  ratingStats: RatingStat[]
  reviews: { data: ReviewRow[]; links: any[]; meta?: any }
  filters: Record<string, any>
  venues: { slug: string; name: string }[]
}

defineProps<Props>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function venueName(v: ReviewRow['venue']) {
  if (!v) return '—'
  if (typeof v.name === 'string') return v.name
  return (locale.value === 'ar' ? v.name.ar : v.name.en) || v.name.ar || v.name.en || '—'
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

function stars(n: number) {
  return '★'.repeat(n) + '☆'.repeat(5 - n)
}

function starTone(n: number) {
  if (n >= 4) return 'text-emerald-500'
  if (n === 3) return 'text-amber-500'
  return 'text-red-500'
}
</script>

<template>
  <Head title="التقييمات" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-6">التقييمات</h1>

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">متوسط التقييم</div>
          <div class="text-2xl font-bold text-amber-500 mt-1" dir="ltr">
            {{ stats.avg_rating ? stats.avg_rating.toFixed(1) + ' ★' : '—' }}
          </div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي التقييمات</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ stats.total_reviews }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">هذا الشهر</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ stats.this_month }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">بانتظار الرد</div>
          <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1" dir="ltr">{{ stats.pending_replies }}</div>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">التقييم</th>
                <th class="px-4 py-3 text-start font-medium">اللاعب</th>
                <th class="px-4 py-3 text-start font-medium">الملعب</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">التعليق</th>
                <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!reviews.data.length">
                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">لا توجد تقييمات</td>
              </tr>
              <tr
                v-for="r in reviews.data"
                :key="r.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3">
                  <span :class="starTone(r.rating)" class="font-mono text-base" dir="ltr">
                    {{ stars(r.rating) }}
                  </span>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ r.user?.name ?? 'مجهول' }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ venueName(r.venue) }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-500 dark:text-gray-400 max-w-xs truncate">{{ r.comment ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400" dir="ltr">{{ fmtDate(r.created_at) }}</td>
                <td class="px-4 py-3 text-end">
                  <Link :href="`/club/reviews/${r.id}`" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">
                    عرض
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="reviews.links" :meta="reviews.meta ?? reviews" showing-label="عرض" />
      </div>
    </div>
  </ClubLayout>
</template>
