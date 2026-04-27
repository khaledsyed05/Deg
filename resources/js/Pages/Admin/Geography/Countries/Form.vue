<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Country {
  id: number
  name: string
  name_ar: string | null
  iso2: string
  iso3: string
  phone_code: string
  capital: string | null
  currency: string | null
  is_active: boolean
}

const props = defineProps<{ country: Country | null }>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isEdit = computed(() => !!props.country)

const form = useForm({
  name: props.country?.name ?? '',
  name_ar: props.country?.name_ar ?? '',
  iso2: props.country?.iso2 ?? '',
  iso3: props.country?.iso3 ?? '',
  phone_code: props.country?.phone_code ?? '',
  capital: props.country?.capital ?? '',
  currency: props.country?.currency ?? '',
  is_active: props.country?.is_active ?? true,
})

function submit() {
  if (isEdit.value && props.country) {
    form.put(`/admin/geography/countries/${props.country.id}`)
  } else {
    form.post('/admin/geography/countries')
  }
}

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide'
</script>

<template>
  <Head :title="isEdit ? tr.geoEditCountry : tr.geoNewCountry" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a href="/admin/geography/countries" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ isEdit ? tr.geoEditCountry : tr.geoNewCountry }}</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-5">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
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

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
              <label :class="labelClass">{{ tr.geoIso2 }}</label>
              <input v-model="form.iso2" type="text" maxlength="2" dir="ltr" :class="[inputClass, form.errors.iso2 && 'border-red-400']" />
              <p v-if="form.errors.iso2" class="text-xs text-red-500 mt-1">{{ form.errors.iso2 }}</p>
            </div>
            <div>
              <label :class="labelClass">{{ tr.geoIso3 }}</label>
              <input v-model="form.iso3" type="text" maxlength="3" dir="ltr" :class="[inputClass, form.errors.iso3 && 'border-red-400']" />
              <p v-if="form.errors.iso3" class="text-xs text-red-500 mt-1">{{ form.errors.iso3 }}</p>
            </div>
            <div>
              <label :class="labelClass">{{ tr.geoPhoneCode }}</label>
              <input v-model="form.phone_code" type="text" dir="ltr" :class="[inputClass, form.errors.phone_code && 'border-red-400']" />
              <p v-if="form.errors.phone_code" class="text-xs text-red-500 mt-1">{{ form.errors.phone_code }}</p>
            </div>
            <div>
              <label :class="labelClass">{{ tr.geoCurrency }}</label>
              <input v-model="form.currency" type="text" dir="ltr" :class="inputClass" />
            </div>
          </div>

          <div>
            <label :class="labelClass">{{ tr.geoCapital }}</label>
            <input v-model="form.capital" type="text" dir="ltr" :class="inputClass" />
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
          <a href="/admin/geography/countries"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-pointer">
            {{ tr.geoCancel }}
          </a>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
