<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Player {
  id: number
  name: string
  phone_number: string | null
  email: string | null
  bookings_count: number
  total_spent: number
}

interface BookingItem {
  id: number
  booking_code: string | null
  date: string | null
  start_time: string
  end_time: string
  total_price: number
  status: string
  venue?: { name: string | { ar?: string; en?: string } }
}

defineProps<{ player: Player; bookings: BookingItem[] }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function venueName(v: BookingItem['venue']) {
  if (!v) return '—'
  if (typeof v.name === 'string') return v.name
  return (locale.value === 'ar' ? v.name.ar : v.name.en) || v.name.ar || v.name.en || '—'
}

function statusTone(s: string) {
  if (s === 'confirmed' || s === 'completed') return 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300'
  if (s === 'scheduled') return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
  if (s === 'pending') return 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300'
  if (s === 'cancelled') return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
  return 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'
}

function statusLabel(s: string) {
  const map: Record<string, string> = {
    pending: 'معلق', confirmed: 'مؤكد', scheduled: 'مجدول',
    completed: 'مكتمل', cancelled: 'ملغى',
  }
  return map[s] ?? s
}

function fmt(n: number) {
  return new Intl.NumberFormat('ar-SY').format(n)
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}
</script>

<template>
  <Head :title="`اللاعب: ${player.name}`" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-3xl space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-2">
        <Link href="/club/players" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </Link>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ player.name }}</h1>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">الحجوزات</div>
          <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1" dir="ltr">{{ player.bookings_count }}</div>
        </div>
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
          <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي الإنفاق</div>
          <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" dir="ltr">{{ fmt(player.total_spent) }} ل.س</div>
        </div>
      </div>

      <!-- Player info -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">بيانات اللاعب</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">الاسم</dt>
            <dd class="text-gray-900 dark:text-white">{{ player.name }}</dd>
          </div>
          <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">رقم الهاتف</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ player.phone_number ?? '—' }}</dd>
          </div>
          <div v-if="player.email">
            <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">البريد الإلكتروني</dt>
            <dd class="text-gray-900 dark:text-white" dir="ltr">{{ player.email }}</dd>
          </div>
        </dl>
      </div>

      <!-- Bookings -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
          <h2 class="font-semibold text-gray-900 dark:text-white">سجل الحجوزات ({{ bookings.length }})</h2>
        </div>
        <div v-if="!bookings.length" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          لا توجد حجوزات
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400">
              <tr>
                <th class="px-4 py-3 text-start font-medium">الكود</th>
                <th class="px-4 py-3 text-start font-medium">التاريخ</th>
                <th class="px-4 py-3 text-start font-medium">الوقت</th>
                <th class="px-4 py-3 text-start font-medium">الملعب</th>
                <th class="px-4 py-3 text-start font-medium">الإجمالي</th>
                <th class="px-4 py-3 text-start font-medium">الحالة</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="b in bookings"
                :key="b.id"
                class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/40"
              >
                <td class="px-4 py-3">
                  <Link :href="`/club/bookings/${b.id}`" class="font-mono text-xs text-gray-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400">
                    {{ b.booking_code ?? '#' + b.id }}
                  </Link>
                </td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ fmtDate(b.date) }}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" dir="ltr">{{ b.start_time }}–{{ b.end_time }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ venueName(b.venue) }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium" dir="ltr">{{ fmt(b.total_price) }}</td>
                <td class="px-4 py-3">
                  <span :class="statusTone(b.status)" class="text-xs px-2 py-1 rounded-lg whitespace-nowrap">
                    {{ statusLabel(b.status) }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </ClubLayout>
</template>
