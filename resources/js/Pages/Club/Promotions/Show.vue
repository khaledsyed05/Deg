<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Promotion {
  id: number
  slug: string
  code: string
  discount_type: string
  discount_value: number
  uses_count: number
  max_uses: number | null
  valid_from: string | null
  valid_until: string | null
  is_active: boolean
  venues: Array<{ id: number; name: string | { ar?: string; en?: string } }>
}

defineProps<{ promotion: Promotion }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function venueName(v: { name: string | { ar?: string; en?: string } }) {
  if (typeof v.name === 'string') return v.name
  return (locale.value === 'ar' ? v.name.ar : v.name.en) || v.name.ar || v.name.en || '—'
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

function discountLabel(p: Promotion) {
  if (p.discount_type === 'percentage') return p.discount_value + '%'
  return fmt(p.discount_value) + ' ل.س'
}

function toggleActive(slug: string) {
  router.post(`/club/promotions/${slug}/toggle`, {}, { preserveScroll: true })
}
</script>

<template>
  <Head :title="`ترقية: ${promotion.code}`" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
          <Link href="/club/promotions" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </Link>
          <h1 class="text-xl font-bold text-gray-900 dark:text-white font-mono">{{ promotion.code }}</h1>
          <span
            :class="promotion.is_active
              ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
              : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
            class="text-xs px-2 py-1 rounded-lg"
          >
            {{ promotion.is_active ? 'نشط' : 'متوقف' }}
          </span>
        </div>
        <div class="flex gap-2">
          <Link :href="`/club/promotions/${promotion.slug}/edit`" class="px-4 py-2.5 text-xs font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white transition-colors">
            تعديل
          </Link>
          <button
            type="button"
            @click="toggleActive(promotion.slug)"
            class="px-4 py-2.5 text-xs font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            {{ promotion.is_active ? 'إيقاف' : 'تفعيل' }}
          </button>
        </div>
      </div>

      <!-- Details -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">تفاصيل الترقية</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">قيمة الخصم</dt>
            <dd class="text-gray-900 dark:text-white font-semibold text-lg" dir="ltr">{{ discountLabel(promotion) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">نوع الخصم</dt>
            <dd class="text-gray-900 dark:text-white">{{ promotion.discount_type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">الاستخدامات</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">
              {{ promotion.uses_count }}
              <span v-if="promotion.max_uses" class="text-gray-400"> / {{ promotion.max_uses }}</span>
              <span v-else class="text-gray-400"> / غير محدود</span>
            </dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">صالحية</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">
              {{ fmtDate(promotion.valid_from) }} — {{ fmtDate(promotion.valid_until) }}
            </dd>
          </div>
        </dl>
      </div>

      <!-- Usage bar -->
      <div v-if="promotion.max_uses" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">نسبة الاستخدام</span>
          <span class="text-sm text-gray-500 dark:text-gray-400" dir="ltr">
            {{ promotion.uses_count }} / {{ promotion.max_uses }}
          </span>
        </div>
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2">
          <div
            class="bg-emerald-500 h-2 rounded-full transition-all"
            :style="`width: ${Math.min(100, (promotion.uses_count / promotion.max_uses) * 100)}%`"
          />
        </div>
      </div>

      <!-- Venues -->
      <div v-if="promotion.venues && promotion.venues.length" class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">الملاعب المشمولة</h2>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="v in promotion.venues"
            :key="v.id"
            class="text-xs px-2.5 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
          >
            {{ venueName(v) }}
          </span>
        </div>
      </div>
      <div v-else class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">تشمل جميع الملاعب</p>
      </div>
    </div>
  </ClubLayout>
</template>
