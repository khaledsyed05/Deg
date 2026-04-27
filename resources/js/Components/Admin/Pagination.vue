<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

interface Meta {
  from?: number | null
  to?: number | null
  total?: number | null
}

const props = defineProps<{
  links: PaginationLink[]
  meta?: Meta
  showingLabel?: string
}>()
</script>

<template>
  <div
    v-if="links && links.length > 3"
    class="flex items-center justify-between gap-3 flex-wrap border-t border-gray-100 dark:border-gray-800 px-4 py-3"
  >
    <p v-if="meta?.from != null && meta?.to != null && meta?.total != null" class="text-xs text-gray-500 dark:text-gray-400">
      {{ showingLabel ?? 'Showing' }} {{ meta.from }}–{{ meta.to }} / {{ meta.total }}
    </p>
    <div class="flex items-center gap-1 flex-wrap">
      <template v-for="(link, i) in links" :key="i">
        <span
          v-if="!link.url && link.label !== '...'"
          class="px-3 py-1.5 text-xs rounded-lg text-gray-400 dark:text-gray-600 cursor-not-allowed"
          v-html="link.label"
        />
        <Link
          v-else-if="link.url"
          :href="link.url"
          :class="[
            'px-3 py-1.5 text-xs rounded-lg transition-colors',
            link.active
              ? 'bg-emerald-500 text-white font-semibold'
              : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800',
          ]"
          :preserve-scroll="true"
          v-html="link.label"
        />
      </template>
    </div>
  </div>
</template>
