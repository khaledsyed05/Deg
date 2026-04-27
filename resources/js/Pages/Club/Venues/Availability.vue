<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'

interface CalendarDay {
  date: string
  day: number
  is_current_month: boolean
  is_past: boolean
  is_today: boolean
  is_blocked: boolean
  has_special_price: boolean
  special_price: number | null
  special_label: string | null
  bookings_count: number
}

interface Block {
  id: number
  start_date: string | null
  end_date: string | null
  start_time: string | null
  end_time: string | null
  reason_type: string | null
  reason_note: string | null
  is_recurring: boolean
  created_by: string | null
}

interface SpecialPrice {
  id: number
  start_date: string | null
  end_date: string | null
  price_per_hour: number
  label: string | null
}

interface Props {
  venue: { id: number; slug: string; name: string }
  calendar: CalendarDay[]
  currentMonth: string
  monthLabel: string
  prevMonth: string
  nextMonth: string
  upcomingBlocks: Block[]
  upcomingSpecials: SpecialPrice[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

const blockForm = useForm({
  start_date: '',
  end_date: '',
  start_time: '',
  end_time: '',
  reason_type: '',
  reason_note: '',
})

const specialForm = useForm({
  start_date: '',
  end_date: '',
  price_per_hour: '',
  label: '',
})

function submitBlock() {
  blockForm.post(`/club/venues/${props.venue.slug}/blocks`, { preserveScroll: true, onSuccess: () => blockForm.reset() })
}

function deleteBlock(id: number) {
  router.delete(`/club/venues/${props.venue.slug}/blocks/${id}`, { preserveScroll: true })
}

function submitSpecial() {
  specialForm.post(`/club/venues/${props.venue.slug}/special-prices`, { preserveScroll: true, onSuccess: () => specialForm.reset() })
}

function deleteSpecial(id: number) {
  router.delete(`/club/venues/${props.venue.slug}/special-prices/${id}`, { preserveScroll: true })
}

function navigate(month: string) {
  router.get(`/club/venues/${props.venue.slug}/availability`, { month }, { preserveState: false })
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wide'
</script>

<template>
  <Head :title="locale === 'ar' ? `توفر: ${venue.name}` : `Availability: ${venue.name}`" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-3 flex-wrap">
        <Link :href="`/club/venues/${venue.slug}`" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </Link>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">
          {{ locale === 'ar' ? 'إدارة التوفر' : 'Manage Availability' }} — {{ venue.name }}
        </h1>
      </div>

      <!-- Calendar navigation -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
          <button @click="navigate(prevMonth)" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">
            <svg class="w-4 h-4" :class="locale === 'ar' ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
          <h2 class="font-semibold text-gray-900 dark:text-white">{{ monthLabel }}</h2>
          <button @click="navigate(nextMonth)" type="button" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800">
            <svg class="w-4 h-4" :class="locale === 'ar' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>

        <!-- Calendar grid -->
        <div class="p-4 grid grid-cols-7 gap-1">
          <div v-for="(day, i) in calendar" :key="i"
            class="aspect-square flex flex-col items-center justify-center rounded-xl text-xs font-medium transition-colors relative"
            :class="[
              !day.is_current_month ? 'opacity-30' : '',
              day.is_today ? 'ring-2 ring-emerald-500' : '',
              day.is_blocked ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300' :
              day.has_special_price ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' :
              day.bookings_count > 0 ? 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' :
              'text-gray-700 dark:text-gray-300'
            ]"
          >
            <span>{{ day.day }}</span>
            <span v-if="day.bookings_count > 0" class="text-[9px] opacity-70">{{ day.bookings_count }}</span>
          </div>
        </div>

        <!-- Legend -->
        <div class="px-6 pb-4 flex gap-4 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-red-200 dark:bg-red-900/40 inline-block"></span> {{ locale === 'ar' ? 'محجوب' : 'Blocked' }}</span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-blue-200 dark:bg-blue-900/40 inline-block"></span> {{ locale === 'ar' ? 'سعر خاص' : 'Special Price' }}</span>
          <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-emerald-100 dark:bg-emerald-900/20 inline-block"></span> {{ locale === 'ar' ? 'لديه حجوزات' : 'Has Bookings' }}</span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Block dates -->
        <div class="space-y-4">
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
              <h2 class="font-semibold text-gray-900 dark:text-white">{{ locale === 'ar' ? 'إضافة فترة إغلاق' : 'Block Dates' }}</h2>
            </div>
            <form @submit.prevent="submitBlock" class="p-6 space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label :class="labelClass">{{ locale === 'ar' ? 'من' : 'From' }}</label>
                  <input v-model="blockForm.start_date" type="date" :class="inputClass" />
                </div>
                <div>
                  <label :class="labelClass">{{ locale === 'ar' ? 'إلى' : 'To' }}</label>
                  <input v-model="blockForm.end_date" type="date" :class="inputClass" />
                </div>
              </div>
              <div>
                <label :class="labelClass">{{ locale === 'ar' ? 'السبب' : 'Reason' }}</label>
                <select v-model="blockForm.reason_type" :class="inputClass">
                  <option value="">{{ locale === 'ar' ? '-- اختر --' : '-- Select --' }}</option>
                  <option value="holiday">{{ locale === 'ar' ? 'عطلة' : 'Holiday' }}</option>
                  <option value="maintenance">{{ locale === 'ar' ? 'صيانة' : 'Maintenance' }}</option>
                  <option value="private">{{ locale === 'ar' ? 'خاص' : 'Private' }}</option>
                  <option value="other">{{ locale === 'ar' ? 'أخرى' : 'Other' }}</option>
                </select>
              </div>
              <button type="submit" :disabled="blockForm.processing" class="px-4 py-2 text-sm font-semibold rounded-xl bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white transition-colors">
                {{ locale === 'ar' ? 'إضافة إغلاق' : 'Block' }}
              </button>
            </form>
          </div>

          <!-- Upcoming blocks list -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
              <h2 class="font-semibold text-gray-900 dark:text-white">{{ locale === 'ar' ? `فترات الإغلاق (${upcomingBlocks.length})` : `Blocks (${upcomingBlocks.length})` }}</h2>
            </div>
            <div v-if="!upcomingBlocks.length" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
              {{ locale === 'ar' ? 'لا توجد فترات إغلاق' : 'No blocks' }}
            </div>
            <div v-for="b in upcomingBlocks" :key="b.id" class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800">
              <div class="text-sm">
                <div class="text-gray-900 dark:text-white" dir="ltr">{{ fmtDate(b.start_date) }}<span v-if="b.end_date && b.end_date !== b.start_date"> — {{ fmtDate(b.end_date) }}</span></div>
                <div v-if="b.reason_type" class="text-xs text-gray-500 dark:text-gray-400">{{ b.reason_type }}</div>
              </div>
              <button @click="deleteBlock(b.id)" type="button" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-300">
                {{ locale === 'ar' ? 'حذف' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Special prices -->
        <div class="space-y-4">
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
              <h2 class="font-semibold text-gray-900 dark:text-white">{{ locale === 'ar' ? 'إضافة سعر خاص' : 'Add Special Price' }}</h2>
            </div>
            <form @submit.prevent="submitSpecial" class="p-6 space-y-3">
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label :class="labelClass">{{ locale === 'ar' ? 'من' : 'From' }}</label>
                  <input v-model="specialForm.start_date" type="date" :class="inputClass" />
                </div>
                <div>
                  <label :class="labelClass">{{ locale === 'ar' ? 'إلى' : 'To' }}</label>
                  <input v-model="specialForm.end_date" type="date" :class="inputClass" />
                </div>
              </div>
              <div>
                <label :class="labelClass">{{ locale === 'ar' ? 'السعر بالساعة (ل.س)' : 'Price/Hour (SYP)' }}</label>
                <input v-model="specialForm.price_per_hour" type="number" min="0" dir="ltr" :class="inputClass" />
              </div>
              <div>
                <label :class="labelClass">{{ locale === 'ar' ? 'الوصف' : 'Label' }}</label>
                <input v-model="specialForm.label" type="text" :class="inputClass" />
              </div>
              <button type="submit" :disabled="specialForm.processing" class="px-4 py-2 text-sm font-semibold rounded-xl bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white transition-colors">
                {{ locale === 'ar' ? 'إضافة سعر' : 'Add Price' }}
              </button>
            </form>
          </div>

          <!-- Upcoming specials list -->
          <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
              <h2 class="font-semibold text-gray-900 dark:text-white">{{ locale === 'ar' ? `الأسعار الخاصة (${upcomingSpecials.length})` : `Special Prices (${upcomingSpecials.length})` }}</h2>
            </div>
            <div v-if="!upcomingSpecials.length" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
              {{ locale === 'ar' ? 'لا توجد أسعار خاصة' : 'No special prices' }}
            </div>
            <div v-for="sp in upcomingSpecials" :key="sp.id" class="flex items-center justify-between px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 border-t border-gray-100 dark:border-gray-800">
              <div class="text-sm">
                <div class="text-gray-900 dark:text-white" dir="ltr">{{ fmtDate(sp.start_date) }}<span v-if="sp.end_date && sp.end_date !== sp.start_date"> — {{ fmtDate(sp.end_date) }}</span></div>
                <div class="text-xs text-blue-600 dark:text-blue-400" dir="ltr">{{ new Intl.NumberFormat('ar-SY').format(sp.price_per_hour) }} ل.س/ساعة<span v-if="sp.label"> · {{ sp.label }}</span></div>
              </div>
              <button @click="deleteSpecial(sp.id)" type="button" class="text-xs text-red-500 hover:text-red-700 dark:hover:text-red-300">
                {{ locale === 'ar' ? 'حذف' : 'Delete' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
