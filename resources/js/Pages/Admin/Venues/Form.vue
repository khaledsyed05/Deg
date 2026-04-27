<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface ClubOpt { id: number; name: Translated; city_id: number | null }
interface CategoryOpt { id: number; name: Translated }
interface SportOpt { id: number; name: Translated }
interface Photo { id: number; url: string; order_column: number }
interface PricingTier {
  id?: number; name: { ar: string; en: string }; day_type: string; specific_day: string | null
  start_time: string; end_time: string; duration_minutes: number; price: number; is_active: boolean
}
interface Venue {
  id: number; slug: string; name: Translated; description: Translated | null
  club_id: number; category_id: number | null; size: string | null; capacity: number | null
  price_from: number; status: string; amenities: string[]; opening_hours: any[]
  latitude: number | null; longitude: number | null
  photos: Photo[]; sport_ids: number[]; pricing_tiers: PricingTier[]
}

interface Props {
  venue: Venue | null
  options: {
    clubs: ClubOpt[]; cities: any[]; categories: CategoryOpt[]; sports: SportOpt[]
    statuses: string[]; day_types: string[]; days_of_week: string[]
  }
  amenities: string[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')
const isEdit = computed(() => props.venue !== null)

function t(name: Translated | null | undefined) {
  if (!name) return ''
  return (isAr.value ? name.ar : name.en) || name.ar || name.en || ''
}

const errors = computed(() => (page.props.errors as Record<string, string>) ?? {})
const processing = ref(false)

const defaultOpeningHours = () => props.options.days_of_week.map((day: string) => ({
  day, open: '08:00', close: '22:00', closed: false,
}))

const form = reactive({
  name: { ar: props.venue?.name?.ar ?? '', en: props.venue?.name?.en ?? '' },
  description: { ar: props.venue?.description?.ar ?? '', en: props.venue?.description?.en ?? '' },
  club_id: props.venue?.club_id ?? '',
  category_id: props.venue?.category_id ?? '',
  size: props.venue?.size ?? '',
  capacity: props.venue?.capacity ?? '',
  price_from: props.venue?.price_from ?? 0,
  status: props.venue?.status ?? 'active',
  latitude: props.venue?.latitude ?? '',
  longitude: props.venue?.longitude ?? '',
  amenities: [...(props.venue?.amenities ?? [])],
  sport_ids: [...(props.venue?.sport_ids ?? [])],
  pricing_tiers: props.venue?.pricing_tiers.map(t => ({ ...t, name: { ar: t.name.ar ?? '', en: t.name.en ?? '' } })) ?? [] as PricingTier[],
  opening_hours: props.venue?.opening_hours?.length ? props.venue.opening_hours : defaultOpeningHours(),
  photos: [] as File[],
  delete_photo_ids: [] as number[],
})

function toggleAmenity(key: string) {
  const idx = form.amenities.indexOf(key)
  if (idx >= 0) form.amenities.splice(idx, 1)
  else form.amenities.push(key)
}

function toggleSport(id: number) {
  const idx = form.sport_ids.indexOf(id)
  if (idx >= 0) form.sport_ids.splice(idx, 1)
  else form.sport_ids.push(id)
}

function addTier() {
  form.pricing_tiers.push({
    name: { ar: '', en: '' }, day_type: 'all_days', specific_day: null,
    start_time: '08:00', end_time: '22:00', duration_minutes: 60, price: 0, is_active: true,
  })
}

function removeTier(i: number) {
  form.pricing_tiers.splice(i, 1)
}

function handlePhotos(e: Event) {
  const target = e.target as HTMLInputElement
  if (target.files) {
    form.photos = [...form.photos, ...Array.from(target.files)]
  }
}

function removeNewPhoto(i: number) {
  form.photos.splice(i, 1)
}

const pendingDeleteIds = ref<Set<number>>(new Set())
function toggleDeleteExisting(id: number) {
  const s = new Set(pendingDeleteIds.value)
  if (s.has(id)) s.delete(id)
  else s.add(id)
  pendingDeleteIds.value = s
  form.delete_photo_ids = [...s]
}

function submit() {
  processing.value = true
  const fd = new FormData()
  fd.append('name[ar]', form.name.ar)
  fd.append('name[en]', form.name.en)
  fd.append('description[ar]', form.description.ar)
  fd.append('description[en]', form.description.en)
  fd.append('club_id', String(form.club_id))
  fd.append('category_id', String(form.category_id))
  fd.append('size', String(form.size))
  fd.append('capacity', String(form.capacity))
  fd.append('price_from', String(form.price_from))
  fd.append('status', form.status)
  fd.append('latitude', String(form.latitude))
  fd.append('longitude', String(form.longitude))
  form.amenities.forEach(a => fd.append('amenities[]', a))
  form.sport_ids.forEach(id => fd.append('sport_ids[]', String(id)))
  form.pricing_tiers.forEach((tier, i) => {
    fd.append(`pricing_tiers[${i}][name][ar]`, tier.name.ar)
    fd.append(`pricing_tiers[${i}][name][en]`, tier.name.en)
    fd.append(`pricing_tiers[${i}][day_type]`, tier.day_type)
    if (tier.specific_day) fd.append(`pricing_tiers[${i}][specific_day]`, tier.specific_day)
    fd.append(`pricing_tiers[${i}][start_time]`, tier.start_time)
    fd.append(`pricing_tiers[${i}][end_time]`, tier.end_time)
    fd.append(`pricing_tiers[${i}][duration_minutes]`, String(tier.duration_minutes))
    fd.append(`pricing_tiers[${i}][price]`, String(tier.price))
    fd.append(`pricing_tiers[${i}][is_active]`, tier.is_active ? '1' : '0')
  })
  form.opening_hours.forEach((oh, i) => {
    fd.append(`opening_hours[${i}][day]`, oh.day)
    fd.append(`opening_hours[${i}][open]`, oh.open)
    fd.append(`opening_hours[${i}][close]`, oh.close)
    fd.append(`opening_hours[${i}][closed]`, oh.closed ? '1' : '0')
  })
  form.photos.forEach(f => fd.append('photos[]', f))
  form.delete_photo_ids.forEach(id => fd.append('delete_photo_ids[]', String(id)))
  if (isEdit.value) {
    fd.append('_method', 'PUT')
  }

  router.post(
    isEdit.value ? `/admin/venues/${props.venue!.slug}` : '/admin/venues',
    fd as any,
    { preserveScroll: true, onFinish: () => { processing.value = false } },
  )
}
</script>

<template>
  <Head :title="isEdit ? (tr.venueEdit ?? 'تعديل الملعب') : (tr.venueAdd ?? 'إضافة ملعب')" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-3xl">
      <div class="flex items-center gap-3 mb-6">
        <Link :href="isEdit ? `/admin/venues/${venue!.slug}` : '/admin/venues'" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </Link>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ isEdit ? (tr.venueEdit ?? 'تعديل الملعب') : (tr.venueAdd ?? 'إضافة ملعب') }}</h1>
      </div>

      <FlashBanner />

      <form @submit.prevent="submit" class="space-y-5">
        <!-- Name & Description -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.venueName }}</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueNameAr ?? 'الاسم (عربي)' }} *</label>
              <input v-model="form.name.ar" type="text" required dir="rtl" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              <p v-if="errors['name.ar']" class="text-xs text-red-500 mt-1">{{ errors['name.ar'] }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueNameEn ?? 'الاسم (إنجليزي)' }}</label>
              <input v-model="form.name.en" type="text" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueDescriptionAr }}</label>
              <textarea v-model="form.description.ar" rows="3" dir="rtl" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueDescriptionEn }}</label>
              <textarea v-model="form.description.en" rows="3" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>
          </div>
        </div>

        <!-- Club, Category, Status -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.venueClub }}</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueClub }} *</label>
              <select v-model="form.club_id" required class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">{{ tr.venueFilterClub }}</option>
                <option v-for="c in options.clubs" :key="c.id" :value="c.id">{{ t(c.name) }}</option>
              </select>
              <p v-if="errors.club_id" class="text-xs text-red-500 mt-1">{{ errors.club_id }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueCategory }}</label>
              <select v-model="form.category_id" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">{{ tr.venueFilterCategory }}</option>
                <option v-for="c in options.categories" :key="c.id" :value="c.id">{{ t(c.name) }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueStatus }} *</label>
              <select v-model="form.status" required class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option v-for="s in options.statuses" :key="s" :value="s">{{ tr['venueStatus' + s.charAt(0).toUpperCase() + s.slice(1)] ?? s }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venuePriceFrom }} *</label>
              <input v-model="form.price_from" type="number" min="0" required dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              <p v-if="errors.price_from" class="text-xs text-red-500 mt-1">{{ errors.price_from }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueSize }}</label>
              <input v-model="form.size" type="text" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueCapacity }}</label>
              <input v-model="form.capacity" type="number" min="1" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
          </div>
        </div>

        <!-- Sports -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venueSports }}</h2>
          <div class="flex flex-wrap gap-2">
            <button v-for="s in options.sports" :key="s.id" type="button"
              @click="toggleSport(s.id)"
              :class="form.sport_ids.includes(s.id) ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-700'"
              class="text-xs px-3 py-1.5 rounded-lg border transition-colors">
              {{ t(s.name) }}
            </button>
          </div>
          <p v-if="!form.sport_ids.length" class="text-xs text-gray-400 mt-2">{{ tr.venueSportsEmpty }}</p>
        </div>

        <!-- Amenities -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venueAmenities }}</h2>
          <div class="flex flex-wrap gap-2">
            <button v-for="a in amenities" :key="a" type="button"
              @click="toggleAmenity(a)"
              :class="form.amenities.includes(a) ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-700'"
              class="text-xs px-3 py-1.5 rounded-lg border transition-colors">
              {{ (tr.venueAmenity as any)?.[a] ?? a }}
            </button>
          </div>
        </div>

        <!-- Pricing Tiers -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <div class="flex items-center justify-between mb-3">
            <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.venuePricingTiers }}</h2>
            <button type="button" @click="addTier" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline">{{ tr.venuePricingAdd }}</button>
          </div>
          <p v-if="!form.pricing_tiers.length" class="text-xs text-gray-400 text-center py-2">{{ tr.venuePricingEmpty }}</p>
          <div v-for="(tier, i) in form.pricing_tiers" :key="i" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 mb-3 space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingTierName }}</label>
                <input v-model="tier.name.ar" type="text" dir="rtl" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingTierNameEn }}</label>
                <input v-model="tier.name.en" type="text" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingDayType }}</label>
                <select v-model="tier.day_type" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                  <option v-for="dt in options.day_types" :key="dt" :value="dt">{{ (tr.dayType as any)?.[dt] ?? dt }}</option>
                </select>
              </div>
              <div v-if="tier.day_type === 'specific_day'">
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingSpecificDay }}</label>
                <select v-model="tier.specific_day" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                  <option v-for="d in options.days_of_week" :key="d" :value="d">{{ (tr.dayName as any)?.[d] ?? d }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingPrice }}</label>
                <input v-model="tier.price" type="number" min="0" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingStart }}</label>
                <input v-model="tier.start_time" type="time" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingEnd }}</label>
                <input v-model="tier.end_time" type="time" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePricingDuration }}</label>
                <input v-model="tier.duration_minutes" type="number" min="15" step="15" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
              </div>
            </div>
            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 cursor-pointer">
                <input v-model="tier.is_active" type="checkbox" class="rounded text-emerald-600" />
                {{ tr.venuePricingActive }}
              </label>
              <button type="button" @click="removeTier(i)" class="text-xs text-red-500 hover:underline">{{ tr.venuePricingRemove }}</button>
            </div>
          </div>
        </div>

        <!-- Opening Hours -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-3 text-sm">{{ tr.venueOpeningHours }}</h2>
          <div class="space-y-2">
            <div v-for="(oh, i) in form.opening_hours" :key="i" class="flex items-center gap-3 text-sm">
              <span class="w-24 text-xs text-gray-600 dark:text-gray-400">{{ (tr.dayName as any)?.[oh.day] ?? oh.day }}</span>
              <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer">
                <input v-model="oh.closed" type="checkbox" class="rounded text-emerald-600" />
                {{ tr.venueDayClosed }}
              </label>
              <template v-if="!oh.closed">
                <input v-model="oh.open" type="time" dir="ltr" class="px-2 py-1 text-xs bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white" />
                <span class="text-gray-400">—</span>
                <input v-model="oh.close" type="time" dir="ltr" class="px-2 py-1 text-xs bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg text-gray-900 dark:text-white" />
              </template>
            </div>
          </div>
        </div>

        <!-- Location -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-3">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.venueMap }}</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueLatitude }}</label>
              <input v-model="form.latitude" type="number" step="any" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.venueLongitude }}</label>
              <input v-model="form.longitude" type="number" step="any" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            </div>
          </div>
        </div>

        <!-- Photos -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-3">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.venuePhotos }}</h2>

          <!-- Existing photos -->
          <div v-if="venue?.photos?.length" class="space-y-2">
            <p class="text-xs text-gray-500">{{ tr.venuePhotoExisting }}</p>
            <div class="grid grid-cols-4 gap-2">
              <div v-for="photo in venue.photos" :key="photo.id" class="relative">
                <img :src="photo.url" class="w-full aspect-square object-cover rounded-lg" />
                <button type="button" @click="toggleDeleteExisting(photo.id)"
                  :class="pendingDeleteIds.has(photo.id) ? 'bg-red-500 text-white' : 'bg-black/40 text-white'"
                  class="absolute top-1 end-1 text-xs px-1.5 py-0.5 rounded">
                  {{ pendingDeleteIds.has(photo.id) ? tr.venuePhotoUndelete : tr.venuePhotoDelete }}
                </button>
              </div>
            </div>
          </div>

          <!-- New photos -->
          <div>
            <label class="block text-xs text-gray-500 mb-1">{{ tr.venuePhotoNew }}</label>
            <label class="flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-6 cursor-pointer hover:border-emerald-500 transition-colors">
              <svg class="w-8 h-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
              <span class="text-xs text-gray-500">{{ tr.venuePhotoDropzone }}</span>
              <input type="file" multiple accept="image/*" class="hidden" @change="handlePhotos" />
            </label>
            <div v-if="form.photos.length" class="grid grid-cols-4 gap-2 mt-2">
              <div v-for="(f, i) in form.photos" :key="i" class="relative">
                <img :src="URL.createObjectURL(f)" class="w-full aspect-square object-cover rounded-lg" />
                <button type="button" @click="removeNewPhoto(i)" class="absolute top-1 end-1 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded">×</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <Link :href="isEdit ? `/admin/venues/${venue!.slug}` : '/admin/venues'" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
            {{ tr.clubCancel }}
          </Link>
          <button type="submit" :disabled="processing" class="px-5 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl transition-colors">
            {{ processing ? tr.clubSaving : tr.clubSave }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
