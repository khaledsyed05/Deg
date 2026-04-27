<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FlashBanner from '@/Components/Admin/FlashBanner.vue'
import { computed, reactive, ref } from 'vue'
import { useI18n } from '@/i18n'

interface CityOpt { id: number; name: string; name_ar: string | null }
interface Player {
  id: number; name: string; phone_number: string | null; email: string | null
  address: string | null; default_city_id: number | null; account_status: string
  block_reason: string | null
}

interface Props {
  player: Player
  cities: CityOpt[]
  statuses: string[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t as any)
const isAr = computed(() => locale.value === 'ar')

const form = reactive({
  name: props.player.name ?? '',
  phone_number: props.player.phone_number ?? '',
  email: props.player.email ?? '',
  address: props.player.address ?? '',
  default_city_id: props.player.default_city_id ?? '',
  account_status: props.player.account_status ?? '',
  block_reason: props.player.block_reason ?? '',
})

const errors = computed(() => (page.props.errors as Record<string, string>) ?? {})
const processing = ref(false)
const needsReason = computed(() => form.account_status === 'blocked')

function submit() {
  processing.value = true
  router.put(`/admin/players/${props.player.id}`, form as any, {
    preserveScroll: true,
    onFinish: () => { processing.value = false },
  })
}
</script>

<template>
  <Head :title="tr.playerEdit" />
  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <Link :href="`/admin/players/${player.id}`" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        </Link>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ tr.playerEdit }}</h1>
      </div>

      <FlashBanner />

      <form @submit.prevent="submit" class="space-y-5">
        <!-- Basic Info -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.playerContactInfo }}</h2>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.playerName }}</label>
            <input v-model="form.name" type="text" required class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            <p v-if="errors.name" class="text-xs text-red-500 mt-1">{{ errors.name }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.playerPhone }}</label>
            <input v-model="form.phone_number" type="tel" dir="ltr" required class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            <p v-if="errors.phone_number" class="text-xs text-red-500 mt-1">{{ errors.phone_number }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.playerEmail }}</label>
            <input v-model="form.email" type="email" dir="ltr" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.playerAddress }}</label>
            <input v-model="form.address" type="text" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500" />
            <p v-if="errors.address" class="text-xs text-red-500 mt-1">{{ errors.address }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.playerCity }}</label>
            <select v-model="form.default_city_id" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option value="">{{ tr.playerFilterCity }}</option>
              <option v-for="c in cities" :key="c.id" :value="c.id">{{ isAr ? c.name_ar || c.name : c.name }}</option>
            </select>
            <p v-if="errors.default_city_id" class="text-xs text-red-500 mt-1">{{ errors.default_city_id }}</p>
          </div>
        </div>

        <!-- Account Status -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tr.playerAccountInfo }}</h2>

          <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.playerStatus }}</label>
            <select v-model="form.account_status" required class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
              <option v-for="s in statuses" :key="s" :value="s">{{ tr['playerStatus_' + s] ?? s }}</option>
            </select>
            <p v-if="errors.account_status" class="text-xs text-red-500 mt-1">{{ errors.account_status }}</p>
          </div>

          <div v-if="needsReason">
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">{{ tr.playerBlockReason }}</label>
            <textarea v-model="form.block_reason" rows="3" :required="needsReason" :placeholder="tr.playerBlockPlaceholder" class="w-full px-3 py-2 text-sm bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            <p v-if="errors.block_reason" class="text-xs text-red-500 mt-1">{{ errors.block_reason }}</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3">
          <Link :href="`/admin/players/${player.id}`" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
            {{ tr.playerCancel }}
          </Link>
          <button type="submit" :disabled="processing" class="px-5 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded-xl transition-colors">
            {{ processing ? tr.playerSaving : tr.playerSave }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
