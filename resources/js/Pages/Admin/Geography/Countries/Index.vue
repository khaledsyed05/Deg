<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Admin/Pagination.vue'
import { computed, reactive, ref, watch } from 'vue'
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
  states_count: number
}

interface Props {
  countries: { data: Country[]; links: any[]; meta?: any; from?: number; to?: number; total?: number }
  filters: { search: string; is_active: string | null }
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({ search: props.filters.search ?? '', is_active: props.filters.is_active ?? '' })

let debounce: ReturnType<typeof setTimeout> | null = null
watch(form, () => {
  if (debounce) clearTimeout(debounce)
  debounce = setTimeout(() => {
    router.get('/admin/geography/countries', form, { preserveState: true, preserveScroll: true, replace: true })
  }, 300)
})

const deleteId = ref<number | null>(null)
function confirmDelete(id: number) { deleteId.value = id }
function cancelDelete() { deleteId.value = null }
function doDelete(id: number) {
  router.delete(`/admin/geography/countries/${id}`, { preserveScroll: true, onSuccess: () => { deleteId.value = null } })
}

function countryName(c: Country) { return isAr.value ? (c.name_ar || c.name) : c.name }
</script>

<template>
  <Head :title="tr.geoCountries" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.geoCountries }}</h1>
        <Link href="/admin/geography/countries/create" class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm">
          {{ tr.geoAddCountry }}
        </Link>
      </div>

      <!-- Filters -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 mb-4 flex flex-wrap gap-3">
        <input v-model="form.search" type="text" :placeholder="tr.geoSearchPlaceholder"
          class="flex-1 min-w-[180px] px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <select v-model="form.is_active"
          class="px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
          <option value="">{{ tr.geoStatusAll }}</option>
          <option value="1">{{ tr.geoStatusActive }}</option>
          <option value="0">{{ tr.geoStatusInactive }}</option>
        </select>
      </div>

      <!-- Table -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ tr.geoNameEn }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.geoNameAr }}</th>
                <th class="hidden sm:table-cell px-4 py-3 text-start font-medium">{{ tr.geoIso2 }}</th>
                <th class="hidden md:table-cell px-4 py-3 text-start font-medium">{{ tr.geoPhoneCode }}</th>
                <th class="hidden lg:table-cell px-4 py-3 text-start font-medium">{{ tr.geoStatesCount }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ tr.geoStatus }}</th>
                <th class="px-4 py-3 w-20"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!countries.data.length">
                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ tr.geoNoResults }}</td>
              </tr>
              <tr v-for="c in countries.data" :key="c.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ c.name }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400">{{ c.name_ar ?? '—' }}</td>
                <td class="hidden sm:table-cell px-4 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs" dir="ltr">{{ c.iso2 }}</td>
                <td class="hidden md:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ c.phone_code }}</td>
                <td class="hidden lg:table-cell px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ c.states_count }}</td>
                <td class="px-4 py-3">
                  <span :class="c.is_active
                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                    class="text-xs px-2 py-1 rounded-lg">
                    {{ c.is_active ? tr.geoStatusActive : tr.geoStatusInactive }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2 justify-end">
                    <Link :href="`/admin/geography/countries/${c.id}/edit`"
                      class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline cursor-pointer">
                      {{ tr.geoEdit }}
                    </Link>
                    <template v-if="deleteId === c.id">
                      <button @click="doDelete(c.id)" class="text-xs text-red-600 dark:text-red-400 hover:underline cursor-pointer">{{ tr.geoDelete }}</button>
                      <button @click="cancelDelete" class="text-xs text-gray-500 hover:underline cursor-pointer">{{ tr.geoCancel }}</button>
                    </template>
                    <button v-else @click="confirmDelete(c.id)" class="text-xs text-red-500 dark:text-red-400 hover:underline cursor-pointer">{{ tr.geoDelete }}</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :links="countries.links" :meta="countries.meta ?? countries" :showing-label="tr.geoPageShowing" />
      </div>
    </div>
  </AdminLayout>
</template>
