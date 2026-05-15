<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Environment {
  id: number
  name: string
  base_url: string
  is_active: boolean
}

interface Platform {
  id: number
  platform_key: string
  name: Record<string, string>
  latest_version: string
  minimum_required_version: string
  store_url: string
  direct_apk_url: string | null
  direct_apk_enabled: boolean
  is_active: boolean
  order_column: number
  environments: Environment[]
}

interface Maintenance {
  id: number
  is_active: boolean
  message: string | null
  message_ar: string | null
  starts_at: string | null
  ends_at: string | null
}

interface Props {
  platforms: Platform[]
  maintenance: Maintenance | null
}

const props = defineProps<Props>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const nonLiveActive = computed(() =>
  props.platforms.some(p => p.environments.some(e => e.is_active && e.name !== 'live')),
)

function platformName(p: Platform): string {
  return p.name[locale.value] ?? p.name.en ?? p.platform_key
}

function envDotClass(name: string): string {
  if (name === 'live') return 'bg-emerald-500'
  if (name === 'stage') return 'bg-amber-500'
  return 'bg-sky-500'
}

function activateEnv(env: Environment): void {
  if (!confirm(tr.value.appStartupConfirmActivate)) return
  router.post(`/admin/settings/app-startup/environments/${env.id}/activate`, {}, {
    preserveScroll: true,
  })
}

function destroyEnv(env: Environment): void {
  if (!confirm(tr.value.appStartupConfirmDelete)) return
  router.delete(`/admin/settings/app-startup/environments/${env.id}`, {
    preserveScroll: true,
  })
}

// ─── Platform Form (per-platform refs) ───────────────────────
const platformForms = ref<Record<number, ReturnType<typeof useForm>>>({})

function platformForm(p: Platform) {
  if (!platformForms.value[p.id]) {
    platformForms.value[p.id] = useForm({
      latest_version: p.latest_version,
      minimum_required_version: p.minimum_required_version,
      store_url: p.store_url ?? '',
      direct_apk_url: p.direct_apk_url ?? '',
      direct_apk_enabled: p.direct_apk_enabled,
      is_active: p.is_active,
    }) as any
  }
  return platformForms.value[p.id]
}

function savePlatform(p: Platform): void {
  const form = platformForm(p)
  form.put(`/admin/settings/app-startup/platforms/${p.id}`, { preserveScroll: true })
}

// ─── Add Environment ─────────────────────────────────────────
const addingEnvFor = ref<number | null>(null)
const newEnv = useForm({ platform_id: 0, name: '', base_url: '' })

function openAddEnv(platformId: number): void {
  addingEnvFor.value = platformId
  newEnv.platform_id = platformId
  newEnv.name = ''
  newEnv.base_url = ''
}

function submitAddEnv(): void {
  newEnv.post('/admin/settings/app-startup/environments', {
    preserveScroll: true,
    onSuccess: () => { addingEnvFor.value = null },
  })
}

// ─── Maintenance Form ────────────────────────────────────────
const maintenanceForm = useForm({
  is_active: props.maintenance?.is_active ?? false,
  message: props.maintenance?.message ?? '',
  message_ar: props.maintenance?.message_ar ?? '',
  ends_at: props.maintenance?.ends_at ? props.maintenance.ends_at.slice(0, 16) : '',
})

function saveMaintenance(): void {
  maintenanceForm.put('/admin/settings/app-startup/maintenance', { preserveScroll: true })
}
</script>

<template>
  <Head title="App Startup" />
  <AdminLayout>
    <div class="space-y-6 p-6">
      <FlashBanner />

      <header>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
          {{ tr.appStartupTitle }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          {{ tr.appStartupSubtitle }}
        </p>
      </header>

      <div
        v-if="nonLiveActive"
        class="rounded-lg border-s-4 border-red-500 bg-red-50 dark:bg-red-950/30 p-4"
      >
        <p class="font-semibold text-red-800 dark:text-red-300">
          ⚠️ {{ tr.appStartupWarnNonLive }}
        </p>
      </div>

      <!-- Platforms -->
      <section
        v-for="p in platforms"
        :key="p.id"
        class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm"
      >
        <header class="flex items-center justify-between mb-4">
          <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
              {{ platformName(p) }}
            </h2>
            <p class="text-xs font-mono text-gray-500 dark:text-gray-400">
              {{ p.platform_key }}
            </p>
          </div>
          <span
            :class="[
              'rounded-full px-3 py-1 text-xs font-medium',
              p.is_active
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
            ]"
          >
            {{ p.is_active ? tr.appStartupActive : 'Inactive' }}
          </span>
        </header>

        <!-- Environments -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
              {{ tr.appStartupEnvironments }}
            </h3>
            <button
              type="button"
              class="text-xs text-blue-600 hover:underline dark:text-blue-400"
              @click="openAddEnv(p.id)"
            >
              {{ tr.appStartupAddEnvironment }}
            </button>
          </div>
          <ul class="space-y-2">
            <li
              v-for="env in p.environments"
              :key="env.id"
              class="flex items-center justify-between rounded border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950 p-3"
            >
              <div class="flex items-center gap-3 min-w-0">
                <span :class="['h-2.5 w-2.5 rounded-full shrink-0', envDotClass(env.name)]" />
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">{{ env.name }}</p>
                  <p class="font-mono text-xs text-gray-500 dark:text-gray-400 truncate">{{ env.base_url }}</p>
                </div>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <span
                  v-if="env.is_active"
                  class="rounded-full bg-emerald-100 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400"
                >
                  {{ tr.appStartupActive }}
                </span>
                <button
                  v-else
                  type="button"
                  class="rounded border border-gray-300 dark:border-gray-700 px-2 py-1 text-xs text-gray-700 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800"
                  @click="activateEnv(env)"
                >
                  {{ tr.appStartupActivate }}
                </button>
                <button
                  v-if="!env.is_active"
                  type="button"
                  class="text-xs text-red-600 hover:underline dark:text-red-400"
                  @click="destroyEnv(env)"
                >
                  {{ tr.appStartupDelete }}
                </button>
              </div>
            </li>
          </ul>

          <!-- Add env inline form -->
          <div
            v-if="addingEnvFor === p.id"
            class="mt-3 grid grid-cols-1 md:grid-cols-[1fr_2fr_auto] gap-2 items-start"
          >
            <input
              v-model="newEnv.name"
              type="text"
              :placeholder="tr.appStartupEnvName"
              class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
            />
            <input
              v-model="newEnv.base_url"
              type="url"
              placeholder="https://api.example.com/v1"
              class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
            />
            <div class="flex gap-2">
              <button
                type="button"
                class="rounded bg-blue-600 text-white px-3 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                :disabled="newEnv.processing"
                @click="submitAddEnv"
              >
                {{ tr.appStartupActivate ? 'Add' : 'Add' }}
              </button>
              <button
                type="button"
                class="rounded border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm"
                @click="addingEnvFor = null"
              >
                Cancel
              </button>
            </div>
            <p v-if="newEnv.errors.name" class="md:col-span-3 text-xs text-red-600">{{ newEnv.errors.name }}</p>
            <p v-if="newEnv.errors.base_url" class="md:col-span-3 text-xs text-red-600">{{ newEnv.errors.base_url }}</p>
          </div>
        </div>

        <!-- Version Policy Form -->
        <form
          class="space-y-3"
          @submit.prevent="savePlatform(p)"
        >
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
            <div>
              <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">
                {{ tr.appStartupPlatformLatest }}
              </label>
              <input
                v-model="platformForm(p).latest_version"
                class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2"
                placeholder="1.2.0"
              />
              <p v-if="platformForm(p).errors.latest_version" class="text-xs text-red-600 mt-1">
                {{ platformForm(p).errors.latest_version }}
              </p>
            </div>
            <div>
              <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">
                {{ tr.appStartupPlatformMinimum }}
              </label>
              <input
                v-model="platformForm(p).minimum_required_version"
                class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2"
                placeholder="1.0.0"
              />
              <p v-if="platformForm(p).errors.minimum_required_version" class="text-xs text-red-600 mt-1">
                {{ platformForm(p).errors.minimum_required_version }}
              </p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">
              {{ tr.appStartupPlatformStoreUrl }}
            </label>
            <input
              v-model="platformForm(p).store_url"
              type="url"
              class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
            />
            <p v-if="platformForm(p).errors.store_url" class="text-xs text-red-600 mt-1">
              {{ platformForm(p).errors.store_url }}
            </p>
          </div>

          <div v-if="p.platform_key === 'android'">
            <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">
              {{ tr.appStartupPlatformDirectApkUrl }}
            </label>
            <input
              v-model="platformForm(p).direct_apk_url"
              type="url"
              class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
            />
            <label class="mt-2 flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
              <input v-model="platformForm(p).direct_apk_enabled" type="checkbox" />
              {{ tr.appStartupPlatformDirectApkEnabled }}
            </label>
          </div>

          <label class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-300">
            <input v-model="platformForm(p).is_active" type="checkbox" />
            {{ tr.appStartupPlatformIsActive }}
          </label>

          <button
            type="submit"
            :disabled="platformForm(p).processing"
            class="rounded bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {{ platformForm(p).processing ? tr.settingsSaving : tr.appStartupSavePlatform }}
          </button>
        </form>
      </section>

      <!-- Maintenance -->
      <section class="rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
          {{ tr.appStartupMaintenance }}
        </h2>

        <form class="space-y-3" @submit.prevent="saveMaintenance">
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input v-model="maintenanceForm.is_active" type="checkbox" />
            {{ tr.appStartupMaintenanceActive }}
          </label>

          <div v-if="maintenanceForm.is_active" class="space-y-3 pl-1">
            <div>
              <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">
                {{ tr.appStartupMaintenanceMessage }}
              </label>
              <textarea
                v-model="maintenanceForm.message"
                rows="2"
                class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">
                {{ tr.appStartupMaintenanceMessageAr }}
              </label>
              <textarea
                v-model="maintenanceForm.message_ar"
                rows="2"
                dir="rtl"
                class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label class="block text-xs font-medium mb-1 text-gray-700 dark:text-gray-300">
                {{ tr.appStartupMaintenanceEndsAt }}
              </label>
              <input
                v-model="maintenanceForm.ends_at"
                type="datetime-local"
                class="rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="maintenanceForm.processing"
            class="rounded bg-blue-600 text-white px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
          >
            {{ maintenanceForm.processing ? tr.settingsSaving : tr.settingsSaveChanges }}
          </button>
        </form>
      </section>
    </div>
  </AdminLayout>
</template>
