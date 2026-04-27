<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Translated { ar?: string; en?: string }
interface CityOpt { id: number; name: string; name_ar: string | null }
interface Club {
  id: number; slug: string; name: Translated; description: Translated
  phone_number: string | null; address: string | null; status: string
  is_featured: boolean; commission_rate: number | null; city_id: number | null
  city: CityOpt | null; owner: { id: number; name: string } | null
}

interface Props {
  club: Club
  cities: CityOpt[]
  statuses: string[]
  platform_commission_rate: number
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const errors = computed(() => (page.props.errors as Record<string, string>) ?? {})

const form = reactive({
  name: { ar: props.club.name?.ar ?? '', en: props.club.name?.en ?? '' },
  phone_number: props.club.phone_number ?? '',
  address: props.club.address ?? '',
  city_id: props.club.city_id ?? '',
  commission_rate: props.club.commission_rate !== null ? props.club.commission_rate : '',
  is_featured: props.club.is_featured ?? false,
})

const useCustomCommission = ref(props.club.commission_rate !== null)
const processing = ref(false)

function submit() {
  processing.value = true
  const payload = {
    ...form,
    commission_rate: useCustomCommission.value ? form.commission_rate : null,
  }
  router.put(`/admin/clubs/${props.club.slug}`, payload as any, {
    preserveScroll: true,
    onFinish: () => { processing.value = false },
  })
}
</script>

<template>
  <Head :title="tr.clubEdit" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-3xl">
      <div class="flex items-center gap-3 mb-6">
        <Link :href="`/admin/clubs/${club.slug}`" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </Link>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.clubEdit }}</h1>
      </div>

      <FlashBanner />

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Name -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ tr.clubName }}</h2>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubNameAr }}</label>
            <input v-model="form.name.ar" type="text" dir="rtl" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" :class="errors['name.ar'] ? 'border-red-400' : 'border-gray-300 dark:border-gray-700'" />
            <p v-if="errors['name.ar']" class="text-xs text-red-500 mt-1">{{ errors['name.ar'] }}</p>
          </div>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubNameEn }}</label>
            <input v-model="form.name.en" type="text" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>
        </div>

        <!-- Contact -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ tr.clubContactInfo ?? tr.clubPhone }}</h2>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubPhone }}</label>
            <input v-model="form.phone_number" type="tel" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" :class="errors.phone_number ? 'border-red-400' : 'border-gray-300 dark:border-gray-700'" />
            <p v-if="errors.phone_number" class="text-xs text-red-500 mt-1">{{ errors.phone_number }}</p>
          </div>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubAddress }}</label>
            <input v-model="form.address" type="text" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>
          <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubCity }}</label>
            <select v-model="form.city_id" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" :class="errors.city_id ? 'border-red-400' : 'border-gray-300 dark:border-gray-700'">
              <option value="">{{ tr.clubFilterCity }}</option>
              <option v-for="c in cities" :key="c.id" :value="c.id">{{ isAr ? c.name_ar || c.name : c.name }}</option>
            </select>
            <p v-if="errors.city_id" class="text-xs text-red-500 mt-1">{{ errors.city_id }}</p>
          </div>
        </div>

        <!-- Commission -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
          <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ tr.clubCommissionOverride }}</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400">{{ tr.clubCommissionDefault }}: {{ platform_commission_rate }}%</p>
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="useCustomCommission" class="w-4 h-4 rounded text-emerald-600 border-gray-300 dark:border-gray-700" />
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ tr.clubCommissionOverride }}</span>
          </label>
          <div v-if="useCustomCommission">
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ tr.clubCommissionRate }}</label>
            <input v-model="form.commission_rate" type="number" min="0" max="100" step="0.01" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500" />
          </div>
        </div>

        <!-- Featured -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5">
          <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" v-model="form.is_featured" class="w-4 h-4 rounded text-emerald-600 border-gray-300 dark:border-gray-700" />
            <div>
              <p class="text-sm font-medium text-gray-900 dark:text-white">{{ tr.clubFeatured }}</p>
            </div>
          </label>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button type="submit" :disabled="processing" class="px-6 py-2.5 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl transition-colors">
            {{ processing ? tr.clubSaving : tr.clubSave }}
          </button>
          <Link :href="`/admin/clubs/${club.slug}`" class="px-6 py-2.5 text-sm font-medium bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            {{ tr.clubCancel }}
          </Link>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
