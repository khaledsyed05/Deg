<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Country { id: number; name: string; name_ar: string | null }
interface State {
  id: number
  name: string
  name_ar: string | null
  state_code: string | null
  country_id: number
  is_active: boolean
}

const props = defineProps<{ state: State | null; countries: Country[] }>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')
const isEdit = computed(() => !!props.state)

const form = useForm({
  name: props.state?.name ?? '',
  name_ar: props.state?.name_ar ?? '',
  state_code: props.state?.state_code ?? '',
  country_id: props.state?.country_id ?? '',
  is_active: props.state?.is_active ?? true,
})

function submit() {
  if (isEdit.value && props.state) {
    form.put(`/admin/geography/states/${props.state.id}`)
  } else {
    form.post('/admin/geography/states')
  }
}

function countryName(c: Country) { return isAr.value ? (c.name_ar || c.name) : c.name }

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide'
</script>

<template>
  <Head :title="isEdit ? tr.geoEditState : tr.geoNewState" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a href="/admin/geography/states" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ isEdit ? tr.geoEditState : tr.geoNewState }}</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
          <div>
            <label :class="labelClass">{{ tr.geoCountry }}</label>
            <select v-model="form.country_id" :class="[inputClass, form.errors.country_id && 'border-red-400']">
              <option value="">— {{ tr.geoFilterCountryAll }} —</option>
              <option v-for="c in countries" :key="c.id" :value="c.id">{{ countryName(c) }}</option>
            </select>
            <p v-if="form.errors.country_id" class="text-xs text-red-500 mt-1">{{ form.errors.country_id }}</p>
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

          <div>
            <label :class="labelClass">{{ tr.geoStateCode }}</label>
            <input v-model="form.state_code" type="text" dir="ltr" :class="inputClass" />
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
          <a href="/admin/geography/states"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer">
            {{ tr.geoCancel }}
          </a>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
