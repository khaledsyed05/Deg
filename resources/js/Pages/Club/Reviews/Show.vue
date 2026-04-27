<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface Review {
  id: number
  rating: number
  comment: string | null
  created_at: string
  user: { name: string; phone_number: string } | null
  venue: { name: string | { ar?: string; en?: string } }
  reply: string | null
}

const props = defineProps<{ review: Review }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

function venueName(v: Review['venue']) {
  if (!v) return '—'
  if (typeof v.name === 'string') return v.name
  return (locale.value === 'ar' ? v.name.ar : v.name.en) || v.name.ar || v.name.en || '—'
}

function fmtDate(s: string | null) {
  if (!s) return '—'
  try { return new Date(s).toLocaleDateString('ar-SY') } catch { return s }
}

function stars(n: number) {
  return '★'.repeat(n) + '☆'.repeat(5 - n)
}

function starTone(n: number) {
  if (n >= 4) return 'text-emerald-500'
  if (n === 3) return 'text-amber-500'
  return 'text-red-500'
}

const replyForm = useForm({
  reply: props.review.reply ?? '',
})

function submitReply() {
  replyForm.post(`/club/reviews/${props.review.id}/reply`, { preserveScroll: true })
}
</script>

<template>
  <Head :title="`تقييم #${review.id}`" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-2xl space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-3">
        <Link href="/club/reviews" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </Link>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">تقييم #{{ review.id }}</h1>
      </div>

      <!-- Review card -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
        <!-- Stars + meta -->
        <div class="flex items-start justify-between gap-3 flex-wrap">
          <div>
            <div :class="starTone(review.rating)" class="text-2xl font-mono tracking-widest" dir="ltr">
              {{ stars(review.rating) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ venueName(review.venue) }}</div>
          </div>
          <div class="text-xs text-gray-400 dark:text-gray-500" dir="ltr">{{ fmtDate(review.created_at) }}</div>
        </div>

        <!-- Comment -->
        <div v-if="review.comment">
          <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
            {{ review.comment }}
          </p>
        </div>
        <p v-else class="text-sm text-gray-400 dark:text-gray-500 italic">لا يوجد تعليق</p>

        <!-- Player -->
        <div v-if="review.user" class="pt-2 border-t border-gray-100 dark:border-gray-800">
          <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">اللاعب</div>
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-300">
              {{ review.user.name.charAt(0) }}
            </div>
            <div>
              <div class="text-sm font-medium text-gray-900 dark:text-white">{{ review.user.name }}</div>
              <div class="text-xs text-gray-500 dark:text-gray-400" dir="ltr">{{ review.user.phone_number }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Existing reply -->
      <div v-if="review.reply" class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-6">
        <div class="flex items-center gap-2 mb-3">
          <div class="w-6 h-6 rounded-full bg-emerald-500 flex items-center justify-center">
            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
          </div>
          <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">ردك</span>
        </div>
        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ review.reply }}</p>
      </div>

      <!-- Reply form -->
      <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
        <h2 class="font-semibold text-gray-900 dark:text-white mb-4">
          {{ review.reply ? 'تعديل الرد' : 'الرد على التقييم' }}
        </h2>
        <form @submit.prevent="submitReply" class="space-y-3">
          <textarea
            v-model="replyForm.reply"
            rows="4"
            placeholder="اكتب ردك هنا..."
            class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400 resize-none"
          />
          <p v-if="replyForm.errors.reply" class="text-xs text-red-500">{{ replyForm.errors.reply }}</p>
          <button
            type="submit"
            :disabled="replyForm.processing || !replyForm.reply.trim()"
            class="px-5 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white transition-colors shadow-sm"
          >
            {{ replyForm.processing ? 'جارٍ الإرسال...' : 'إرسال الرد' }}
          </button>
        </form>
      </div>
    </div>
  </ClubLayout>
</template>
