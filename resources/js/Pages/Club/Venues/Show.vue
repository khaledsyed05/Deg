<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Venue {
  id: number
  slug: string
  name: string
  description: string | null
  status: string
  price_from: number
  category: string | null
  capacity: number | null
  size: number | null
  photos: Array<{ id: number; url: string }>
  amenities: string[]
  opening_hours: Array<{ day: string; open: string; close: string; closed: boolean }>
  pricing_tiers: any[]
  sports: string[]
}

interface Stats {
  total_bookings: number
  total_revenue: number
  avg_rating: number
  reviews_count: number
  occupancy_rate: number
}

const props = defineProps<{
  venue: Venue
  stats: Stats
  recentBookings: any[]
  charts: Record<string, any>
  metrics: { avg_duration_hours: number; avg_value: number; cancellation_rate: number }
  topPlayers: any[]
}>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function t(v: { ar?: string; en?: string } | string | null | undefined) {
  if (!v) return '—'
  if (typeof v === 'string') return v
  return (locale.value === 'ar' ? v.ar : v.en) || v.ar || v.en || '—'
}

function statusTone(s: string) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'inactive') return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
  return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
}

function statusLabel(s: string) {
  const map: Record<string, string> = { active: 'نشط', inactive: 'غير نشط', pending: 'قيد المراجعة' }
  return map[s] ?? s
}

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

const days: Record<string, string> = {
  monday: 'الاثنين', tuesday: 'الثلاثاء', wednesday: 'الأربعاء',
  thursday: 'الخميس', friday: 'الجمعة', saturday: 'السبت', sunday: 'الأحد',
}
</script>

<template>
  <Head :title="t(venue.name)" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-4xl space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div>
          <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ t(venue.name) }}</h1>
            <span :class="statusTone(venue.status)" class="text-xs px-2 py-1 rounded-lg">{{ statusLabel(venue.status) }}</span>
          </div>
          <p v-if="venue.category" class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ venue.category }}</p>
        </div>
        <div class="flex gap-2 flex-wrap">
          <Link
            :href="`/club/venues/${venue.slug}/edit`"
            class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors"
          >
            تعديل
          </Link>
          <Link
            :href="`/club/venues/${venue.slug}/availability`"
            class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            إدارة التوفر
          </Link>
        </div>
      </div>

      <!-- Stats cards -->
      <div class="grid grid-cols-3 gap-3">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">الحجوزات</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ stats.total_bookings }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">الإيرادات</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ fmt(stats.total_revenue) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">التقييم</div>
          <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1" dir="ltr">
            {{ stats.avg_rating ? stats.avg_rating.toFixed(1) + ' ★' : '—' }}
          </div>
        </div>
      </div>

      <!-- Details -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
        <h2 class="font-semibold text-gray-900 dark:text-white">تفاصيل الملعب</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">السعر الابتدائي</div>
            <div class="text-gray-900 dark:text-white font-semibold" dir="ltr">{{ fmt(venue.price_from) }} ل.س</div>
          </div>
          <div v-if="venue.category">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">الفئة</div>
            <div class="text-gray-900 dark:text-white">{{ venue.category }}</div>
          </div>
          <div class="sm:col-span-2" v-if="venue.description">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">الوصف</div>
            <div class="text-gray-700 dark:text-gray-300 leading-relaxed">{{ t(venue.description) }}</div>
          </div>
        </div>
      </div>

      <!-- Amenities -->
      <div v-if="venue.amenities && venue.amenities.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">المرافق</h2>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="a in venue.amenities"
            :key="a"
            class="text-xs px-2.5 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
          >
            {{ a }}
          </span>
        </div>
      </div>

      <!-- Opening hours -->
      <div v-if="venue.opening_hours" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">أوقات العمل</h2>
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
          <div
            v-for="(hours, day) in venue.opening_hours"
            :key="day"
            class="flex items-center justify-between py-2.5 text-sm"
          >
            <span class="text-gray-700 dark:text-gray-300 font-medium">{{ days[day as string] ?? day }}</span>
            <span v-if="hours && hours.open" class="text-gray-600 dark:text-gray-400" dir="ltr">
              {{ hours.from }} – {{ hours.to }}
            </span>
            <span v-else class="text-gray-400 dark:text-gray-600 text-xs">مغلق</span>
          </div>
        </div>
      </div>

      <!-- Photos -->
      <div v-if="venue.photos && venue.photos.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">الصور</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <img
            v-for="(photo, i) in venue.photos"
            :key="i"
            :src="photo.url"
            class="w-full h-32 object-cover rounded-xl border border-gray-200 dark:border-gray-700"
            :alt="`صورة ${i + 1}`"
          />
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
