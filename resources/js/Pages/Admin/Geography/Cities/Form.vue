<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, ref, watch } from 'vue'
import { useI18n } from '@/i18n'

interface Country { id: number; name: string; name_ar: string | null }
interface StateOpt { id: number; country_id: number; name: string; name_ar: string | null }
interface City {
  id: number
  name: string
  name_ar: string | null
  latitude: number | null
  longitude: number | null
  state_id: number
  state: { id: number; country_id: number }
}

const props = defineProps<{ city: City | null; countries: Country[]; states: StateOpt[] }>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')
const isEdit = computed(() => !!props.city)

const selectedCountryId = ref(props.city?.state?.country_id ?? '')

const filteredStates = computed(() => {
  if (!selectedCountryId.value) return props.states
  return props.states.filter((s) => s.country_id === Number(selectedCountryId.value))
})

const form = useForm({
  name: props.city?.name ?? '',
  name_ar: props.city?.name_ar ?? '',
  state_id: props.city?.state_id ?? '',
  latitude: props.city?.latitude ?? '',
  longitude: props.city?.longitude ?? '',
  is_active: props.city !== null ? (props.city as any).is_active ?? true : true,
})

watch(selectedCountryId, () => { form.state_id = '' })

function submit() {
  if (isEdit.value && props.city) {
    form.put(`/admin/geography/cities/${props.city.id}`)
  } else {
    form.post('/admin/geography/cities')
  }
}

function name(en: string, ar: string | null) { return isAr.value ? (ar || en) : en }

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide'
</script>

<template>
  <Head :title="isEdit ? tr.geoEditCity : tr.geoNewCity" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a href="/admin/geography/cities" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ isEdit ? tr.geoEditCity : tr.geoNewCity }}</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
          <!-- Country selector (client-side filter only) -->
          <div>
            <label :class="labelClass">{{ tr.geoCountry }}</label>
            <select v-model="selectedCountryId" :class="inputClass">
              <option value="">— {{ tr.geoFilterCountryAll }} —</option>
              <option v-for="c in countries" :key="c.id" :value="c.id">{{ name(c.name, c.name_ar) }}</option>
            </select>
          </div>

          <div>
            <label :class="labelClass">{{ tr.geoState }}</label>
            <select v-model="form.state_id" :class="[inputClass, form.errors.state_id && 'border-red-400']">
              <option value="">— {{ tr.geoFilterStateAll }} —</option>
              <option v-for="s in filteredStates" :key="s.id" :value="s.id">{{ name(s.name, s.name_ar) }}</option>
            </select>
            <p v-if="form.errors.state_id" class="text-xs text-red-500 mt-1">{{ form.errors.state_id }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">{{ tr.geoNameEn }}</label>
              <input v-model="form.name" type="text" dir="ltr" :class="[inputClass, form.errors.name && 'border-red-400']" />
              <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
            </div>
            <div>
              <label :class="labelClass">{{ tr.geoNameAr }}</label>
              <input v-model="form.name_ar" type="text" dir="rtl" :class="inputClass" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">{{ tr.geoLatitude }}</label>
              <input v-model="form.latitude" type="number" step="any" dir="ltr" :class="[inputClass, form.errors.latitude && 'border-red-400']" />
              <p v-if="form.errors.latitude" class="text-xs text-red-500 mt-1">{{ form.errors.latitude }}</p>
            </div>
            <div>
              <label :class="labelClass">{{ tr.geoLongitude }}</label>
              <input v-model="form.longitude" type="number" step="any" dir="ltr" :class="[inputClass, form.errors.longitude && 'border-red-400']" />
              <p v-if="form.errors.longitude" class="text-xs text-red-500 mt-1">{{ form.errors.longitude }}</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <input v-model="form.is_active" type="checkbox" id="is_active" class="w-4 h-4 rounded border-gray-300 text-emerald-500 focus:ring-emerald-400 cursor-pointer" />
            <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">{{ tr.geoIsActive }}</label>
          </div>
        </div>

        <div class="flex gap-3">
          <button type="submit" :disabled="form.processing"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white transition-colors shadow-sm cursor-pointer">
            {{ form.processing ? tr.geoSaving : tr.geoSave }}
          </button>
          <a href="/admin/geography/cities"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer">
            {{ tr.geoCancel }}
          </a>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
