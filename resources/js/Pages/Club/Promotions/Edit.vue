<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface VenueOpt {
  id: number
  slug: string
  name: string | { ar?: string; en?: string }
}

interface Promotion {
  id: number
  slug: string
  code: string
  name: { ar?: string; en?: string }
  type: string
  value: number
  max_uses: number | null
  valid_from: string | null
  valid_to: string | null
  status: string
  venue_slugs: string[]
  applies_to: string
}

const props = defineProps<{ venues: VenueOpt[]; promotion: Promotion }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function venueName(v: VenueOpt) {
  if (typeof v.name === 'string') return v.name
  return (locale.value === 'ar' ? v.name.ar : v.name.en) || v.name.ar || v.name.en || '—'
}

const form = useForm({
  code: props.promotion.code,
  type: props.promotion.type,
  value: String(props.promotion.value),
  max_uses: props.promotion.max_uses ? String(props.promotion.max_uses) : '',
  valid_from: props.promotion.valid_from ?? '',
  valid_to: props.promotion.valid_to ?? '',
  venue_slugs: [...props.promotion.venue_slugs],
})

function toggleVenue(slug: string) {
  const idx = form.venue_slugs.indexOf(slug)
  if (idx === -1) { form.venue_slugs.push(slug) } else { form.venue_slugs.splice(idx, 1) }
}

function submit() {
  form.put(`/club/promotions/${props.promotion.slug}`)
}

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide'
</script>

<template>
  <Head :title="`تعديل: ${promotion.code}`" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a :href="`/club/promotions/${promotion.slug}`" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">تعديل الترقية — <span class="font-mono">{{ promotion.code }}</span></h1>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white">تفاصيل الترقية</h2>

          <div>
            <label :class="labelClass">كود الخصم</label>
            <input v-model="form.code" type="text" dir="ltr" :class="[inputClass, form.errors.code && 'border-red-400']" />
            <p v-if="form.errors.code" class="text-xs text-red-500 mt-1">{{ form.errors.code }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">نوع الخصم</label>
              <select v-model="form.type" :class="inputClass">
                <option value="percentage">نسبة مئوية (%)</option>
                <option value="fixed">مبلغ ثابت (ل.س)</option>
              </select>
            </div>
            <div>
              <label :class="labelClass">قيمة الخصم</label>
              <input v-model="form.value" type="number" min="0" dir="ltr" :class="[inputClass, form.errors.value && 'border-red-400']" />
              <p v-if="form.errors.value" class="text-xs text-red-500 mt-1">{{ form.errors.value }}</p>
            </div>
          </div>

          <div>
            <label :class="labelClass">الحد الأقصى للاستخدام (اختياري)</label>
            <input v-model="form.max_uses" type="number" min="1" dir="ltr" :class="inputClass" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">صالح من</label>
              <input v-model="form.valid_from" type="date" :class="inputClass" />
            </div>
            <div>
              <label :class="labelClass">صالح حتى</label>
              <input v-model="form.valid_to" type="date" :class="inputClass" />
            </div>
          </div>

        </div>

        <!-- Venues -->
        <div v-if="venues.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4">الملاعب المشمولة</h2>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">اترك فارغاً لتشمل جميع الملاعب</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="v in venues"
              :key="v.id"
              type="button"
              @click="toggleVenue(v.slug)"
              :class="[
                'text-xs px-3 py-1.5 rounded-lg border transition-colors',
                form.venue_slugs.includes(v.slug)
                  ? 'bg-emerald-500 border-emerald-500 text-white'
                  : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400',
              ]"
            >
              {{ venueName(v) }}
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button type="submit" :disabled="form.processing" class="px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white transition-colors shadow-sm">
            {{ form.processing ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}
          </button>
          <a :href="`/club/promotions/${promotion.slug}`" class="px-6 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            إلغاء
          </a>
        </div>
      </form>
    </div>
  </ClubLayout>
</template>
