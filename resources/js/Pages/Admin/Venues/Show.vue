<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface Photo { id: number; url: string; order_column: number }
interface PricingTier {
  id: number; name: Translated; day_type: string; specific_day: string | null
  start_time: string; end_time: string; duration_minutes: number; price: number; is_active: boolean
}
interface Venue {
  id: number; slug: string; name: Translated; description: Translated | null
  club_id: number; category_id: number | null; size: string | null; capacity: number | null
  price_from: number; status: string; amenities: string[]; opening_hours: any[]
  latitude: number | null; longitude: number | null; avg_rating: number | null; reviews_count: number
  photos: Photo[]; sport_ids: number[]; sport_categories: { id: number; name: Translated }[]
  pricing_tiers: PricingTier[]
  club: { id: number; name: Translated; city: { id: number; name: string; name_ar: string | null } | null } | null
  category: { id: number; name: Translated } | null
}
interface RecentBooking {
  id: number; user_id: number; booking_code: string; status: string
  starts_at: string; ends_at: string; total_price: number
  user: { id: number; name: string; phone_number: string | null } | null
}

interface Props {
  venue: Venue
  recent_bookings: RecentBooking[]
  analytics: { total_bookings: number; total_revenue: number; avg_rating: number; occupancy_rate: number }
  amenities: string[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

function t(name: Translated | null | undefined) {
  if (!name) return '—'
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || '—'
}
function fmt(n: number) { return new Intl.NumberFormat(isAr.value ? 'ar-SY' : 'en-US').format(n) }
function fmtDate(d: string | null) { if (!d) return '—'; return new Date(d).toLocaleDateString(isAr.value ? 'ar-SY' : 'en-US') }

function statusTone(s: string) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'suspended') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

function bookingStatusTone(s: string) {
  if (s === 'confirmed') return 'text-emerald-600 dark:text-emerald-400'
  if (s === 'completed') return 'text-blue-600 dark:text-blue-400'
  if (s === 'cancelled') return 'text-red-600 dark:text-red-400'
  return 'text-gray-500 dark:text-gray-400'
}

const cover = computed(() => props.venue.photos[0]?.url ?? null)
</script>

<template>
  <Head :title="t(venue.name)" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-6 flex-wrap">
        <Link href="/admin/venues" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </Link>
        <div class="flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ t(venue.name) }}</h1>
            <span :class="statusTone(venue.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">
              {{ tr['venueStatus' + venue.status.charAt(0).toUpperCase() + venue.status.slice(1)] ?? venue.status }}
            </span>
          </div>
          <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ venue.club ? t(venue.club.name) : '' }}</p>
        </div>
        <Link :href="`/admin/venues/${venue.slug}/edit`" class="px-4 py-2 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors">
          {{ tr.venueEdit ?? 'تعديل' }}
        </Link>
      </div>

      <FlashBanner />

      <!-- Stats -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueBookings }}</div>
          <div class="text-xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ analytics.total_bookings }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueRevenue }}</div>
          <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ fmt(analytics.total_revenue) }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueAvgRating }}</div>
          <div class="text-xl font-bold text-amber-500 mt-1" dir="ltr">{{ analytics.avg_rating > 0 ? analytics.avg_rating.toFixed(1) : '—' }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueOccupancy }}</div>
          <div class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1" dir="ltr">{{ analytics.occupancy_rate.toFixed(1) }}%</div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Left: Info -->
        <div class="lg:col-span-2 space-y-5">
          <!-- Cover Photo -->
          <div v-if="cover" class="rounded-2xl overflow-hidden aspect-video bg-gray-100 dark:bg-gray-800">
            <img :src="cover" :alt="t(venue.name)" class="w-full h-full object-cover" />
          </div>

          <!-- Details -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-4 text-sm">{{ tr.venueInfo ?? 'معلومات الملعب' }}</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueClub }}</dt>
                <dd class="text-gray-900 dark:text-white font-medium mt-0.5">{{ venue.club ? t(venue.club.name) : '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueCity }}</dt>
                <dd class="text-gray-900 dark:text-white font-medium mt-0.5">{{ venue.club?.city ? (isAr ? venue.club.city.name_ar || venue.club.city.name : venue.club.city.name) : '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueCategory }}</dt>
                <dd class="text-gray-900 dark:text-white font-medium mt-0.5">{{ venue.category ? t(venue.category.name) : '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venuePriceFrom }}</dt>
                <dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ fmt(venue.price_from) }}</dd>
              </div>
              <div v-if="venue.size">
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueSize }}</dt>
                <dd class="text-gray-900 dark:text-white font-medium mt-0.5">{{ venue.size }}</dd>
              </div>
              <div v-if="venue.capacity">
                <dt class="text-xs text-gray-500 dark:text-gray-400">{{ tr.venueCapacity }}</dt>
                <dd class="text-gray-900 dark:text-white font-medium mt-0.5" dir="ltr">{{ venue.capacity }}</dd>
              </div>
            </dl>
          </div>

          <!-- Sports -->
          <div v-if="venue.sport_categories.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venueSports }}</h2>
            <div class="flex flex-wrap gap-2">
              <span v-for="s in venue.sport_categories" :key="s.id" class="text-xs px-2 py-1 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                {{ t(s.name) }}
              </span>
            </div>
          </div>

          <!-- Amenities -->
          <div v-if="venue.amenities && venue.amenities.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venueAmenities }}</h2>
            <div class="flex flex-wrap gap-2">
              <span v-for="a in venue.amenities" :key="a" class="text-xs px-2 py-1 bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 rounded-lg">
                {{ (tr.venueAmenity as any)?.[a] ?? a }}
              </span>
            </div>
          </div>

          <!-- Pricing Tiers -->
          <div v-if="venue.pricing_tiers && venue.pricing_tiers.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venuePricingTiers }}</h2>
            <div class="space-y-2">
              <div v-for="tier in venue.pricing_tiers" :key="tier.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl text-sm">
                <div>
                  <span class="font-medium text-gray-900 dark:text-white">{{ t(tier.name) }}</span>
                  <span class="text-xs text-gray-500 dark:text-gray-400 ms-2" dir="ltr">{{ tier.start_time }} – {{ tier.end_time }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-gray-500 dark:text-gray-400">{{ tier.duration_minutes }}{{ tr.bookingDurationMin }}</span>
                  <span class="font-semibold text-gray-900 dark:text-white" dir="ltr">{{ fmt(tier.price) }}</span>
                  <span v-if="!tier.is_active" class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 rounded-lg">{{ tr.venueStatusInactive }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Recent Bookings -->
        <div class="space-y-5">
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venueRecentBookings }}</h2>
            <div v-if="!recent_bookings.length" class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">{{ tr.venueBookingNone }}</div>
            <div v-else class="space-y-3">
              <div v-for="b in recent_bookings" :key="b.id" class="border-b border-gray-100 dark:border-gray-800 pb-3 last:border-0 last:pb-0">
                <div class="flex items-center justify-between">
                  <Link :href="`/admin/bookings/${b.id}`" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:underline" dir="ltr">
                    {{ b.booking_code }}
                  </Link>
                  <span :class="bookingStatusTone(b.status)" class="text-xs">{{ tr['bookingStatus_' + b.status] ?? b.status }}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ b.user?.name ?? '—' }}</div>
                <div class="flex justify-between items-center mt-0.5">
                  <span class="text-xs text-gray-400" dir="ltr">{{ fmtDate(b.starts_at) }}</span>
                  <span class="text-xs font-medium text-gray-900 dark:text-white" dir="ltr">{{ fmt(b.total_price) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Photos grid -->
          <div v-if="venue.photos.length > 1" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
            <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venuePhotos }}</h2>
            <div class="grid grid-cols-3 gap-2">
              <div v-for="photo in venue.photos" :key="photo.id" class="aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                <img :src="photo.url" class="w-full h-full object-cover" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
