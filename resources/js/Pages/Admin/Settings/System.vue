<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from '@/i18n'

interface SettingRow { name: string; type: string; value: any; options: string[] | null; locked: boolean }
type Groups = Record<string, SettingRow[]>

interface Props { groups: Groups }

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

// Deep copy of group values for editing
const localGroups = reactive<Groups>(
  Object.fromEntries(
    Object.entries(props.groups).map(([g, rows]) => [
      g,
      rows.map(r => ({ ...r, value: r.value })),
    ]),
  ),
)

const hasChanges = computed(() => {
  for (const [g, rows] of Object.entries(localGroups)) {
    const orig = props.groups[g] ?? []
    for (let i = 0; i < rows.length; i++) {
      if (String(rows[i].value) !== String(orig[i]?.value ?? '')) {
        return true
      }
    }
  }
  return false
})

function revert() {
  for (const [g, rows] of Object.entries(props.groups)) {
    rows.forEach((r, i) => {
      localGroups[g][i].value = r.value
    })
  }
}

const processing = ref(false)
const maintenanceProcessing = ref(false)

function save() {
  const settings: { name: string; value: any; type: string }[] = []
  for (const rows of Object.values(localGroups)) {
    for (const row of rows) {
      if (!row.locked) {
        settings.push({ name: row.name, value: row.value, type: row.type })
      }
    }
  }
  processing.value = true
  router.post('/admin/settings', { settings } as any, {
    preserveScroll: true,
    onFinish: () => { processing.value = false },
  })
}

const maintenanceMsg = ref(
  props.groups.maintenance?.find(r => r.name === 'maintenance_message')?.value ?? '',
)
function toggleMaintenance(enabled: boolean) {
  maintenanceProcessing.value = true
  router.post('/admin/settings/maintenance', { enabled, message: maintenanceMsg.value } as any, {
    preserveScroll: true,
    onFinish: () => { maintenanceProcessing.value = false },
  })
}

const maintenanceRow = computed(() => localGroups.maintenance?.find(r => r.name === 'maintenance_mode'))
const isMaintenanceOn = computed(() => Boolean(maintenanceRow.value?.value))

function groupLabel(g: string): string {
  return (tr.value.settingsGroupLabel as any)?.[g] ?? g
}
function fieldLabel(name: string): string {
  return (tr.value.settingsFieldLabel as any)?.[name] ?? name
}
</script>

<template>
  <Head :title="tr.settingsTitle" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-3xl">
      <div class="flex items-center justify-between mb-6 gap-3 flex-wrap">
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.settingsTitle }}</h1>
        <div class="flex items-center gap-2">
          <span v-if="hasChanges" class="text-xs text-amber-600 dark:text-amber-400">{{ tr.settingsUnsavedChanges }}</span>
          <button v-if="hasChanges" @click="revert" type="button" class="px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white">
            {{ tr.settingsRevertChanges }}
          </button>
          <button @click="save" :disabled="processing || !hasChanges" class="px-4 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl transition-colors">
            {{ processing ? tr.settingsSaving : tr.settingsSaveChanges }}
          </button>
        </div>
      </div>

      <FlashBanner />

      <!-- Maintenance quick toggle (special) -->
      <div class="bg-amber-50 dark:bg-amber-900/15 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 mb-5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <div>
            <h2 class="font-semibold text-amber-800 dark:text-amber-300 text-sm">{{ groupLabel('maintenance') }}</h2>
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">{{ fieldLabel('maintenance_mode') }}</p>
          </div>
          <div class="flex items-center gap-3">
            <span :class="isMaintenanceOn ? 'text-amber-600 font-semibold' : 'text-gray-500'" class="text-xs">
              {{ isMaintenanceOn ? 'مفعّل' : 'معطّل' }}
            </span>
            <button @click="toggleMaintenance(!isMaintenanceOn)" :disabled="maintenanceProcessing"
              :class="isMaintenanceOn ? 'bg-amber-500 hover:bg-amber-600' : 'bg-gray-300 dark:bg-gray-700 hover:bg-gray-400'"
              class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors disabled:opacity-50">
              <span :class="isMaintenanceOn ? (isAr ? '-translate-x-5' : 'translate-x-5') : (isAr ? '-translate-x-1' : 'translate-x-1')"
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow"></span>
            </button>
          </div>
        </div>
      </div>

      <!-- Settings groups -->
      <div v-for="[groupKey, rows] in Object.entries(localGroups)" :key="groupKey" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 mb-4">
        <h2 class="font-semibold text-gray-900 dark:text-white text-sm mb-4">{{ groupLabel(groupKey) }}</h2>
        <div class="space-y-4">
          <div v-for="row in rows" :key="row.name" class="flex items-center justify-between gap-4">
            <div class="flex-1 min-w-0">
              <label :for="`setting-${row.name}`" class="text-sm text-gray-700 dark:text-gray-300">
                {{ fieldLabel(row.name) }}
              </label>
              <span v-if="row.locked" class="ms-2 text-xs text-gray-400">{{ tr.settingsLocked }}</span>
            </div>

            <!-- Bool toggle -->
            <template v-if="row.type === 'bool'">
              <button type="button"
                :disabled="row.locked"
                @click="row.locked ? null : (row.value = !row.value)"
                :class="row.value ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-700'"
                class="relative inline-flex h-6 w-11 flex-shrink-0 items-center rounded-full transition-colors disabled:opacity-50">
                <span :class="row.value ? (isAr ? '-translate-x-5' : 'translate-x-5') : (isAr ? '-translate-x-1' : 'translate-x-1')"
                  class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow"></span>
              </button>
            </template>

            <!-- Select (options) -->
            <template v-else-if="row.options">
              <select :id="`setting-${row.name}`" v-model="row.value" :disabled="row.locked"
                class="px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50">
                <option v-for="opt in row.options" :key="opt" :value="opt">{{ opt }}</option>
              </select>
            </template>

            <!-- Int number -->
            <template v-else-if="row.type === 'int'">
              <input :id="`setting-${row.name}`" v-model.number="row.value" type="number" :disabled="row.locked" dir="ltr"
                class="w-24 px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white text-end focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50" />
            </template>

            <!-- String -->
            <template v-else>
              <input :id="`setting-${row.name}`" v-model="row.value" type="text" :disabled="row.locked" dir="ltr"
                class="w-40 sm:w-56 px-3 py-1.5 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50" />
            </template>
          </div>
        </div>
      </div>

      <!-- Save button (bottom) -->
      <div class="flex justify-end gap-3">
        <button v-if="hasChanges" @click="revert" type="button" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{{ tr.settingsRevertChanges }}</button>
        <button @click="save" :disabled="processing || !hasChanges" class="px-5 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl transition-colors">
          {{ processing ? tr.settingsSaving : tr.settingsSaveChanges }}
        </button>
      </div>
    </div>
  </AdminLayout>
</template>
