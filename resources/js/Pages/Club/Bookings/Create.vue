<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface VenueOpt {
  id: number
  slug: string
  name: string | { ar?: string; en?: string }
}

defineProps<{ venues: VenueOpt[] }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function venueName(v: VenueOpt) {
  if (typeof v.name === 'string') return v.name
  return (locale.value === 'ar' ? v.name.ar : v.name.en) || v.name.ar || v.name.en || '—'
}

const form = useForm({
  venue_id: '',
  booking_date: '',
  start_time: '',
  end_time: '',
  player_name: '',
  player_phone: '',
  notes: '',
  deposit_amount: '',
})

function submit() {
  form.post('/club/bookings')
}

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide'
</script>

<template>
  <Head title="حجز يدوي جديد" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl">
      <div class="flex items-center gap-3 mb-6">
        <a href="/club/bookings" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">حجز يدوي جديد</h1>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Venue & Date -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white">تفاصيل الحجز</h2>

          <div>
            <label :class="labelClass">الملعب</label>
            <select v-model="form.venue_id" :class="[inputClass, form.errors.venue_id && 'border-red-400']">
              <option value="">-- اختر الملعب --</option>
              <option v-for="v in venues" :key="v.id" :value="v.id">{{ venueName(v) }}</option>
            </select>
            <p v-if="form.errors.venue_id" class="text-xs text-red-500 mt-1">{{ form.errors.venue_id }}</p>
          </div>

          <div>
            <label :class="labelClass">تاريخ الحجز</label>
            <input v-model="form.booking_date" type="date" :class="[inputClass, form.errors.booking_date && 'border-red-400']" />
            <p v-if="form.errors.booking_date" class="text-xs text-red-500 mt-1">{{ form.errors.booking_date }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">وقت البدء</label>
              <input v-model="form.start_time" type="time" dir="ltr" :class="[inputClass, form.errors.start_time && 'border-red-400']" />
              <p v-if="form.errors.start_time" class="text-xs text-red-500 mt-1">{{ form.errors.start_time }}</p>
            </div>
            <div>
              <label :class="labelClass">وقت الانتهاء</label>
              <input v-model="form.end_time" type="time" dir="ltr" :class="[inputClass, form.errors.end_time && 'border-red-400']" />
              <p v-if="form.errors.end_time" class="text-xs text-red-500 mt-1">{{ form.errors.end_time }}</p>
            </div>
          </div>

          <div>
            <label :class="labelClass">قيمة العربون (ل.س)</label>
            <input v-model="form.deposit_amount" type="number" min="0" dir="ltr" :class="inputClass" />
          </div>

          <div>
            <label :class="labelClass">ملاحظات</label>
            <textarea v-model="form.notes" rows="3" :class="inputClass" />
          </div>
        </div>

        <!-- Player info -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white">بيانات اللاعب (اختياري)</h2>

          <div>
            <label :class="labelClass">الاسم</label>
            <input v-model="form.player_name" type="text" :class="inputClass" />
          </div>

          <div>
            <label :class="labelClass">رقم الهاتف</label>
            <input v-model="form.player_phone" type="tel" dir="ltr" placeholder="+963..." :class="inputClass" />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white transition-colors shadow-sm"
          >
            {{ form.processing ? 'جارٍ الحفظ...' : 'إنشاء الحجز' }}
          </button>
          <a href="/club/bookings" class="px-6 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
            إلغاء
          </a>
        </div>
      </form>
    </div>
  </ClubLayout>
</template>
