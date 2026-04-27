<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  open: boolean
  title: string
  subtitle?: string
  body?: string
  warning?: string
  requireReason?: boolean
  reasonLabel?: string
  reasonPlaceholder?: string
  emailLabel?: string
  confirmLabel: string
  confirmTone?: 'emerald' | 'red' | 'amber'
  cancelLabel?: string
  processing?: boolean
}>()

const emit = defineEmits<{
  confirm: [payload: { reason?: string; send_email: boolean }]
  cancel: []
}>()

const reason = ref('')
const sendEmail = ref(true)

watch(() => props.open, (v) => {
  if (!v) {
    reason.value = ''
    sendEmail.value = true
  }
})

function confirm() {
  emit('confirm', { reason: reason.value || undefined, send_email: sendEmail.value })
}

const confirmClass = {
  emerald: 'bg-emerald-500 hover:bg-emerald-600 disabled:bg-emerald-300',
  red: 'bg-red-500 hover:bg-red-600 disabled:bg-red-300',
  amber: 'bg-amber-500 hover:bg-amber-600 disabled:bg-amber-300',
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @mousedown.self="$emit('cancel')">
        <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
          <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ title }}</h2>
            <p v-if="subtitle" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ subtitle }}</p>
          </div>

          <p v-if="body" class="text-sm text-gray-600 dark:text-gray-400">{{ body }}</p>

          <div v-if="warning" class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 px-4 py-3 text-sm text-amber-700 dark:text-amber-300">
            {{ warning }}
          </div>

          <div v-if="requireReason">
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
              {{ reasonLabel }}
            </label>
            <textarea
              v-model="reason"
              rows="3"
              :placeholder="reasonPlaceholder"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition resize-none"
            />
          </div>

          <div v-if="emailLabel" class="flex items-center gap-2">
            <input
              id="send_email"
              v-model="sendEmail"
              type="checkbox"
              class="w-4 h-4 rounded border-gray-300 text-emerald-500 focus:ring-emerald-400"
            />
            <label for="send_email" class="text-sm text-gray-600 dark:text-gray-400 select-none cursor-pointer">
              {{ emailLabel }}
            </label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              :disabled="processing"
              @click="$emit('cancel')"
              class="px-4 py-2 text-sm font-medium rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors disabled:opacity-50"
            >
              {{ cancelLabel ?? 'Cancel' }}
            </button>
            <button
              type="button"
              :disabled="processing || (requireReason && !reason.trim())"
              @click="confirm"
              :class="['px-4 py-2 text-sm font-semibold rounded-xl text-white transition-colors disabled:opacity-50', confirmClass[confirmTone ?? 'emerald']]"
            >
              {{ confirmLabel }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
