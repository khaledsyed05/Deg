<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'

interface CityOpt {
  id: number
  name: string
}

interface ClubProfile {
  id: number
  slug: string
  name: { ar?: string; en?: string }
  description?: { ar?: string; en?: string }
  phone_number: string | null
  email: string | null
  whatsapp_number: string | null
  address: string | null
  city_id: number | null
  city_name: string | null
  logo_url: string | null
  cover_url: string | null
  settings?: Record<string, any>
  commission_rate?: number | null
}

interface Props {
  profile: ClubProfile
  cities: CityOpt[]
  tab?: string
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const clubStatus = computed(() => (page.props.club as any)?.status ?? '')

const form = useForm({
  name_ar: props.profile.name.ar ?? '',
  name_en: props.profile.name.en ?? '',
  phone_number: props.profile.phone_number ?? '',
  address: props.profile.address ?? '',
  city_id: props.profile.city_id ? String(props.profile.city_id) : '',
})

function statusTone(s: string) {
  if (s === 'active') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'pending_approval') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  if (s === 'suspended') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

function statusLabel(s: string) {
  const map: Record<string, string> = {
    active: locale.value === 'ar' ? 'نشط' : 'Active',
    pending_approval: locale.value === 'ar' ? 'قيد المراجعة' : 'Pending',
    suspended: locale.value === 'ar' ? 'موقوف' : 'Suspended',
    rejected: locale.value === 'ar' ? 'مرفوض' : 'Rejected',
  }
  return map[s] ?? s
}

function submit() {
  form.put('/club/settings')
}

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide'
</script>

<template>
  <Head :title="locale === 'ar' ? 'إعدادات النادي' : 'Settings'" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-3xl">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-6">{{ locale === 'ar' ? 'إعدادات النادي' : 'Club Settings' }}</h1>

      <div class="space-y-6">
        <!-- Status banner -->
        <div class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl">
          <div v-if="profile.logo_url" class="w-14 h-14 rounded-xl overflow-hidden shrink-0">
            <img :src="profile.logo_url" class="w-full h-full object-cover" alt="" />
          </div>
          <div v-else class="w-14 h-14 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
            {{ (profile.name.ar ?? profile.name.en ?? '?').charAt(0) }}
          </div>
          <div>
            <div class="font-semibold text-gray-900 dark:text-white">{{ profile.name.ar || profile.name.en }}</div>
            <div class="flex items-center gap-2 mt-1">
              <span v-if="clubStatus" :class="statusTone(clubStatus)" class="text-xs px-2 py-1 rounded-lg">{{ statusLabel(clubStatus) }}</span>
              <span class="text-xs text-gray-400 dark:text-gray-500 font-mono">{{ profile.slug }}</span>
            </div>
          </div>
        </div>

        <!-- Profile form -->
        <form @submit.prevent="submit" class="space-y-6">
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
            <h2 class="font-semibold text-gray-900 dark:text-white">{{ locale === 'ar' ? 'معلومات النادي' : 'Club Info' }}</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label :class="labelClass">{{ locale === 'ar' ? 'اسم النادي (عربي)' : 'Name (Arabic)' }}</label>
                <input v-model="form.name_ar" type="text" :class="[inputClass, form.errors.name_ar && 'border-red-400']" />
                <p v-if="form.errors.name_ar" class="text-xs text-red-500 mt-1">{{ form.errors.name_ar }}</p>
              </div>
              <div>
                <label :class="labelClass">{{ locale === 'ar' ? 'اسم النادي (إنجليزي)' : 'Name (English)' }}</label>
                <input v-model="form.name_en" type="text" dir="ltr" :class="[inputClass, form.errors.name_en && 'border-red-400']" />
                <p v-if="form.errors.name_en" class="text-xs text-red-500 mt-1">{{ form.errors.name_en }}</p>
              </div>
            </div>

            <div>
              <label :class="labelClass">{{ locale === 'ar' ? 'رقم الهاتف' : 'Phone' }}</label>
              <input v-model="form.phone_number" type="tel" dir="ltr" placeholder="+963..." :class="[inputClass, form.errors.phone_number && 'border-red-400']" />
              <p v-if="form.errors.phone_number" class="text-xs text-red-500 mt-1">{{ form.errors.phone_number }}</p>
            </div>

            <div>
              <label :class="labelClass">{{ locale === 'ar' ? 'المدينة' : 'City' }}</label>
              <select v-model="form.city_id" :class="[inputClass, form.errors.city_id && 'border-red-400']">
                <option value="">{{ locale === 'ar' ? '-- اختر المدينة --' : '-- Select City --' }}</option>
                <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <p v-if="form.errors.city_id" class="text-xs text-red-500 mt-1">{{ form.errors.city_id }}</p>
            </div>

            <div>
              <label :class="labelClass">{{ locale === 'ar' ? 'العنوان' : 'Address' }}</label>
              <textarea v-model="form.address" rows="2" :class="inputClass" />
            </div>
          </div>

          <div class="flex gap-3">
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white transition-colors shadow-sm"
            >
              {{ form.processing ? (locale === 'ar' ? 'جارٍ الحفظ...' : 'Saving...') : (locale === 'ar' ? 'حفظ التعديلات' : 'Save Changes') }}
            </button>
            <button
              type="button"
              @click="form.reset()"
              class="px-6 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              {{ locale === 'ar' ? 'إعادة تعيين' : 'Reset' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </ClubLayout>
</template>
