<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'

interface Club {
  id: number
  name: string
  phone?: string
  created_at: string
  status: string
}

defineProps<{ clubs: Club[] }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)

const rejectingClub = ref<Club | null>(null)
const rejectReason = ref('')
const isSubmitting = ref(false)

function approveClub(club: Club) {
  if (!confirm(tr.value.approveConfirm(club.name))) return
  router.post(`/admin/clubs/${club.id}/approve`)
}

function openRejectDialog(club: Club) {
  rejectingClub.value = club
  rejectReason.value = ''
}

function confirmReject() {
  if (!rejectingClub.value || !rejectReason.value.trim()) return
  isSubmitting.value = true
  router.post(
    `/admin/clubs/${rejectingClub.value.id}/reject`,
    { reason: rejectReason.value },
    {
      onFinish: () => {
        isSubmitting.value = false
        rejectingClub.value = null
        rejectReason.value = ''
      },
    },
  )
}
</script>

<template>
  <Head :title="tr.pendingClubs" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-5xl">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tr.pendingClubs }}</h1>
        <span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 text-xs font-semibold px-3 py-1.5 rounded-full">
          {{ clubs.length }} {{ locale === 'ar' ? 'نادٍ' : 'clubs' }}
        </span>
      </div>

      <!-- Empty state -->
      <div v-if="clubs.length === 0" class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-14 text-center shadow-sm">
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-emerald-900/20 flex items-center justify-center text-3xl mx-auto mb-4">✅</div>
        <p class="text-gray-500 dark:text-gray-400 font-medium text-sm">{{ tr.noClubsPending }}</p>
      </div>

      <!-- Clubs List (card style on mobile, table on desktop) -->
      <div v-else>
        <!-- Mobile cards -->
        <div class="sm:hidden space-y-3">
          <div
            v-for="club in clubs"
            :key="club.id"
            class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-4 shadow-sm"
          >
            <div class="flex items-start justify-between mb-3">
              <div>
                <p class="font-semibold text-gray-900 dark:text-white">{{ club.name }}</p>
                <p v-if="club.phone" class="text-xs text-gray-400 mt-0.5">{{ club.phone }}</p>
              </div>
              <span class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                {{ new Date(club.created_at).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-GB') }}
              </span>
            </div>
            <div class="flex gap-2 flex-wrap">
              <button
                @click="approveClub(club)"
                class="flex-1 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-3 py-2 rounded-xl transition-colors"
              >
                {{ tr.approve }}
              </button>
              <button
                @click="openRejectDialog(club)"
                class="flex-1 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-700 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
              >
                {{ tr.reject }}
              </button>
              <a
                :href="`/admin/whatsapp/clubs/${club.id}`"
                class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-700 text-xs font-semibold px-3 py-2 rounded-xl hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
              >
                💬 {{ tr.whatsapp }}
              </a>
            </div>
          </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden shadow-sm">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b dark:border-gray-800 bg-gray-50/80 dark:bg-gray-800/50">
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.clubName }}</th>
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.registrationDate }}</th>
                <th class="text-start px-5 py-3.5 font-semibold text-gray-500 dark:text-gray-400 text-xs uppercase tracking-wide">{{ tr.actions }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
              <tr v-for="club in clubs" :key="club.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                <td class="px-5 py-4">
                  <div class="font-semibold text-gray-900 dark:text-white">{{ club.name }}</div>
                  <div v-if="club.phone" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ club.phone }}</div>
                </td>
                <td class="px-5 py-4 text-gray-500 dark:text-gray-400 text-sm">
                  {{ new Date(club.created_at).toLocaleDateString(locale === 'ar' ? 'ar-SA' : 'en-GB') }}
                </td>
                <td class="px-5 py-4">
                  <div class="flex gap-2">
                    <button
                      @click="approveClub(club)"
                      class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg transition-colors"
                    >
                      {{ tr.approve }}
                    </button>
                    <button
                      @click="openRejectDialog(club)"
                      class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-700 text-xs font-semibold px-3.5 py-1.5 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors"
                    >
                      {{ tr.reject }}
                    </button>
                    <a
                      :href="`/admin/whatsapp/clubs/${club.id}`"
                      class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-700 text-xs font-semibold px-3.5 py-1.5 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors"
                    >
                      💬 {{ tr.whatsapp }}
                    </a>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Reject Dialog -->
    <Teleport to="body">
      <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div
          v-if="rejectingClub"
          class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center z-50 p-4"
          @click.self="rejectingClub = null"
          :dir="locale === 'ar' ? 'rtl' : 'ltr'"
        >
          <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-5 w-full max-w-md">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">{{ tr.rejectClub }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ tr.rejectingClub(rejectingClub.name) }}</p>
            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5 uppercase tracking-wide">{{ tr.rejectReason }}</label>
            <textarea
              v-model="rejectReason"
              rows="3"
              :placeholder="tr.rejectPlaceholder"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none placeholder-gray-400"
            />
            <div class="flex gap-2 mt-4">
              <button
                @click="confirmReject"
                :disabled="!rejectReason.trim() || isSubmitting"
                class="flex-1 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold py-2.5 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              >
                {{ tr.confirmReject }}
              </button>
              <button
                @click="rejectingClub = null"
                class="flex-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm font-semibold py-2.5 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
              >
                {{ tr.cancel }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>
