<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { computed, ref } from 'vue'
import { useI18n } from '@/i18n'
import DataTable from 'datatables.net-vue3'
import DataTablesCore from 'datatables.net-dt'
import 'datatables.net-responsive-dt'
import 'datatables.net-dt/css/dataTables.dataTables.min.css'
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css'

DataTable.use(DataTablesCore)

interface Club {
  id: number
  name: string
}

defineProps<{ clubs: Club[] }>()

const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')
const tr = computed(() => useI18n(locale.value).t)
const isAr = computed(() => locale.value === 'ar')

const showForm = ref(false)
const form = ref({ club_id: '', from_date: '', to_date: '' })
const isSubmitting = ref(false)

function submitForm() {
  isSubmitting.value = true
  router.post('/admin/settlements', form.value, {
    onSuccess: () => {
      showForm.value = false
      form.value = { club_id: '', from_date: '', to_date: '' }
    },
    onFinish: () => {
      isSubmitting.value = false
    },
  })
}

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-600',
  paid: 'bg-emerald-100 text-emerald-700',
  pending: 'bg-amber-100 text-amber-700',
}

const statusLabels: Record<string, Record<string, string>> = {
  ar: { draft: 'مسودة', paid: 'مدفوع', pending: 'معلق' },
  en: { draft: 'Draft', paid: 'Paid', pending: 'Pending' },
}

const dtOptions = computed(() => ({
  serverSide: true,
  processing: true,
  responsive: true,
  ajax: '/admin/settlements/datatables',
  order: [[0, 'desc']],
  columns: [
    { data: 'id', visible: false },
    { data: 'club_name' },
    { data: 'period' },
    {
      data: 'total_amount',
      render: (data: number) =>
        `<span class="font-semibold">${(data || 0).toLocaleString()}</span> <span class="text-xs text-gray-400">${isAr.value ? 'ل.س' : 'SYP'}</span>`,
    },
    {
      data: 'status',
      render: (data: string) => {
        const cls = statusColors[data] ?? 'bg-gray-100 text-gray-600'
        const label = (statusLabels[locale.value] ?? statusLabels.ar)[data] ?? data
        return `<span class="text-xs font-semibold px-2.5 py-1 rounded-full ${cls}">${label}</span>`
      },
    },
    {
      data: 'id',
      orderable: false,
      searchable: false,
      render: (data: number) => {
        const label = isAr.value ? 'عرض' : 'View'
        return `<a href="/admin/settlements/${data}" class="text-emerald-600 hover:text-emerald-700 font-semibold text-xs hover:underline underline-offset-2">${label}</a>`
      },
    },
  ],
  language: isAr.value
    ? {
        search: 'بحث:',
        lengthMenu: 'عرض _MENU_ سجلات',
        info: 'عرض _START_ إلى _END_ من _TOTAL_ سجل',
        infoEmpty: 'لا توجد سجلات',
        infoFiltered: '(من أصل _MAX_ سجل)',
        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
        emptyTable: 'لا توجد تسويات',
        processing: 'جارٍ التحميل...',
        zeroRecords: 'لا توجد نتائج مطابقة',
      }
    : {},
}))
</script>

<template>
  <Head :title="tr.settlements" />

  <AdminLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8">
      <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tr.settlements }}</h1>
        <button
          @click="showForm = !showForm"
          class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition-colors shadow-sm"
        >
          {{ tr.createSettlement }}
        </button>
      </div>

      <!-- Create Form -->
      <div
        v-if="showForm"
        class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 p-5 mb-5 shadow-sm"
      >
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ tr.createNewSettlement }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
              {{ tr.club }}
            </label>
            <select
              v-model="form.club_id"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
            >
              <option value="">{{ tr.selectClub }}</option>
              <option v-for="club in clubs" :key="club.id" :value="club.id">{{ club.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
              {{ tr.fromDate }}
            </label>
            <input
              v-model="form.from_date"
              type="date"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide">
              {{ tr.toDate }}
            </label>
            <input
              v-model="form.to_date"
              type="date"
              class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
            />
          </div>
        </div>
        <div class="flex gap-2 mt-4">
          <button
            @click="submitForm"
            :disabled="!form.club_id || !form.from_date || !form.to_date || isSubmitting"
            class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl disabled:opacity-40 transition-colors"
          >
            {{ tr.createBtn }}
          </button>
          <button
            @click="showForm = false"
            class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-semibold px-4 py-2.5 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
          >
            {{ tr.cancel }}
          </button>
        </div>
      </div>

      <!-- DataTable -->
      <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm overflow-hidden">
        <DataTable
          :key="locale"
          :options="dtOptions"
          class="display w-full"
          :dir="isAr ? 'rtl' : 'ltr'"
        >
          <thead>
            <tr>
              <th>ID</th>
              <th>{{ isAr ? 'النادي' : 'Club' }}</th>
              <th>{{ isAr ? 'الفترة' : 'Period' }}</th>
              <th>{{ isAr ? 'الإجمالي' : 'Total' }}</th>
              <th>{{ isAr ? 'الحالة' : 'Status' }}</th>
              <th>{{ isAr ? 'التفاصيل' : 'Details' }}</th>
            </tr>
          </thead>
        </DataTable>
      </div>
    </div>
  </AdminLayout>
</template>
