<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface City {
  id: number
  name: string
  name_ar: string | null
  is_active: boolean | number
}

const props = defineProps<{ cities: City[] }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)

function cityDisplayName(city: City): string {
  if (locale.value === 'ar' && city.name_ar) return city.name_ar
  return city.name
}

function toggleCity(city: City) {
  const newState = !city.is_active
  const action = newState ? tr.value.activate : tr.value.deactivate
  if (!confirm(tr.value.toggleConfirm(action, cityDisplayName(city)))) return
  router.post(`/admin/geography/cities/${city.id}/toggle`, { is_active: newState })
}

const activeCount = computed(() => props.cities.filter((c) => c.is_active).length)
</script>

<template>
  <Head :title="tr.manageCities" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-4xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tr.manageCities }}</h1>
        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-full">
          {{ tr.activeCities(activeCount, cities.length) }}
        </span>
      </div>

      <!-- Mobile card list -->
      <div class="sm:hidden space-y-2">
        <div
          v-for="city in cities"
          :key="city.id"
          class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 px-4 py-3 flex items-center justify-between shadow-sm"
        >
          <div class="flex items-center gap-3">
            <span
              class="w-2 h-2 rounded-full flex-shrink-0"
              :class="city.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"
            />
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ cityDisplayName(city) }}</span>
          </div>
          <button
            @click="toggleCity(city)"
            class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
            :class="city.is_active
              ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-700 hover:bg-red-100'
              : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700 hover:bg-emerald-100'"
          >
            {{ city.is_active ? tr.deactivate : tr.activate }}
          </button>
        </div>
      </div>

      <!-- Desktop table -->
      <div class="hidden sm:block bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b dark:border-gray-800 bg-gray-50/80 dark:bg-gray-800/50">
              <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.cityName }}</th>
              <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.cityStatus }}</th>
              <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.action }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
            <tr
              v-for="city in cities"
              :key="city.id"
              class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
            >
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-2.5">
                  <span
                    class="w-2 h-2 rounded-full flex-shrink-0"
                    :class="city.is_active ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'"
                  />
                  <span class="font-medium text-gray-900 dark:text-white">{{ cityDisplayName(city) }}</span>
                  <span v-if="locale === 'ar' && city.name" class="text-xs text-gray-400 dark:text-gray-600">({{ city.name }})</span>
                </div>
              </td>
              <td class="px-5 py-3.5">
                <span
                  class="text-xs font-semibold px-2.5 py-1 rounded-full"
                  :class="city.is_active
                    ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400'
                    : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400'"
                >
                  {{ city.is_active ? tr.cityActive : tr.cityInactive }}
                </span>
              </td>
              <td class="px-5 py-3.5">
                <button
                  @click="toggleCity(city)"
                  class="text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors"
                  :class="city.is_active
                    ? 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-700 hover:bg-red-100 dark:hover:bg-red-900/30'
                    : 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'"
                >
                  {{ city.is_active ? tr.deactivate : tr.activate }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
