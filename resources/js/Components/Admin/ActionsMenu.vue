<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ref } from 'vue'

interface MenuItem {
  label: string
  href?: string
  onClick?: () => void
  tone?: 'success' | 'danger' | 'warning' | 'default'
  hidden?: boolean
}

defineProps<{
  items: MenuItem[]
  dir?: 'ltr' | 'rtl'
}>()

const open = ref(false)

function handleItem(item: MenuItem) {
  open.value = false
  item.onClick?.()
}
</script>

<template>
  <div class="relative inline-block text-start" :dir="dir ?? 'ltr'">
    <button
      type="button"
      @click.stop="open = !open"
      class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z" />
      </svg>
    </button>

    <Transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute end-0 z-50 mt-1 min-w-[160px] origin-top-end rounded-xl bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700 py-1"
      >
        <template v-for="(item, i) in items" :key="i">
          <Link
            v-if="!item.hidden && item.href"
            :href="item.href"
            :class="[
              'flex w-full items-center px-4 py-2 text-sm transition-colors',
              item.tone === 'danger' ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
              : item.tone === 'warning' ? 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20'
              : item.tone === 'success' ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800',
            ]"
            @click="open = false"
          >
            {{ item.label }}
          </Link>
          <button
            v-else-if="!item.hidden && item.onClick"
            type="button"
            :class="[
              'flex w-full items-center px-4 py-2 text-sm transition-colors',
              item.tone === 'danger' ? 'text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
              : item.tone === 'warning' ? 'text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20'
              : item.tone === 'success' ? 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800',
            ]"
            @click="handleItem(item)"
          >
            {{ item.label }}
          </button>
        </template>
      </div>
    </Transition>

    <!-- Backdrop -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />
  </div>
</template>
