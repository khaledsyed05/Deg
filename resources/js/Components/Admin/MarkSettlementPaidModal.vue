<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

interface Translated { ar?: string; en?: string }

const props = defineProps<{
  settlement: {
    id: number
    net_payable: number
    period_from: string | null
    period_to: string | null
    club: { name: Translated } | null
  }
}>()

const emit = defineEmits<{ close: [] }>()

const receiptInput = ref<HTMLInputElement | null>(null)
const receiptName = ref<string | null>(null)

const form = useForm({
  payment_method: '' as string,
  payment_date: new Date().toISOString().slice(0, 10),
  payment_reference: '',
  payment_notes: '',
  send_notification: true,
  receipt: null as File | null,
})

function onReceipt(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] ?? null
  form.receipt = file
  receiptName.value = file?.name ?? null
}

function submit() {
  form.post(`/admin/settlements/${props.settlement.id}/mark-paid`, {
    forceFormData: true,
    onSuccess: () => emit('close'),
  })
}

const paymentMethods = ['bank_transfer', 'cash', 'cheque', 'other']
const methodLabels: Record<string, string> = {
  bank_transfer: 'تحويل بنكي',
  cash: 'نقداً',
  cheque: 'شيك',
  other: 'أخرى',
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @mousedown.self="$emit('close')">
      <div class="w-full max-w-md bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">تسجيل الدفعة</h2>

        <div v-if="form.errors.payment_method || form.errors.payment_date" class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 px-4 py-3 text-sm text-red-600 dark:text-red-400">
          {{ form.errors.payment_method || form.errors.payment_date }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">طريقة الدفع *</label>
            <select
              v-model="form.payment_method"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
              :class="{ 'border-red-400': form.errors.payment_method }"
              required
            >
              <option value="" disabled>اختر طريقة الدفع</option>
              <option v-for="m in paymentMethods" :key="m" :value="m">{{ methodLabels[m] }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">تاريخ الدفع *</label>
            <input
              v-model="form.payment_date"
              type="date"
              required
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">رقم المرجع</label>
            <input
              v-model="form.payment_reference"
              type="text"
              maxlength="100"
              dir="ltr"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">إيصال الدفع</label>
            <div
              class="flex items-center gap-3 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2.5 cursor-pointer hover:border-emerald-400 transition-colors"
              @click="receiptInput?.click()"
            >
              <span class="text-gray-400 dark:text-gray-500 text-sm">{{ receiptName ?? 'اختر ملفاً (PDF, JPG, PNG)' }}</span>
            </div>
            <input ref="receiptInput" type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="onReceipt" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">ملاحظات</label>
            <textarea
              v-model="form.payment_notes"
              rows="3"
              maxlength="500"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition resize-none"
            />
          </div>

          <div class="flex items-center gap-2">
            <input id="send_notif" v-model="form.send_notification" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-emerald-500 focus:ring-emerald-400" />
            <label for="send_notif" class="text-sm text-gray-600 dark:text-gray-400 select-none cursor-pointer">إرسال إشعار للنادي</label>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              :disabled="form.processing"
              @click="$emit('close')"
              class="px-4 py-2 text-sm font-medium rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors disabled:opacity-50"
            >
              إلغاء
            </button>
            <button
              type="submit"
              :disabled="form.processing || !form.payment_method"
              class="px-4 py-2 text-sm font-semibold rounded-xl text-white bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 transition-colors"
            >
              {{ form.processing ? 'جاري الحفظ...' : 'تأكيد الدفع' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
