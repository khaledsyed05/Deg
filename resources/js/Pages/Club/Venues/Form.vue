<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3'
import ClubLayout from '@/Layouts/ClubLayout.vue'
import { computed } from 'vue'
import { useI18n } from '@/i18n'

interface CategoryOpt { id: number; name: { ar?: string; en?: string } | string }
interface SportOpt { id: number; name: { ar?: string; en?: string } | string }

interface Props {
  venue: Record<string, any> | null
  options: { categories: CategoryOpt[]; sports: SportOpt[]; statuses: string[] }
  amenities: string[]
}

const props = defineProps<Props>()
const page = usePage()
const locale = computed(() => (page.props.locale as string) ?? 'ar')

const isEditing = computed(() => !!props.venue?.id)

const form = useForm({
  name_ar: props.venue?.name?.ar ?? props.venue?.name_ar ?? '',
  name_en: props.venue?.name?.en ?? props.venue?.name_en ?? '',
  description_ar: props.venue?.description?.ar ?? props.venue?.description_ar ?? '',
  description_en: props.venue?.description?.en ?? props.venue?.description_en ?? '',
  price_from: props.venue?.price_from ?? '',
  category_id: props.venue?.category_id ?? '',
  status: props.venue?.status ?? 'active',
  amenities: (props.venue?.amenities ?? []) as string[],
})

function toggleAmenity(a: string) {
  const idx = form.amenities.indexOf(a)
  if (idx === -1) {
    form.amenities.push(a)
  } else {
    form.amenities.splice(idx, 1)
  }
}

function submit() {
  if (isEditing.value) {
    form.put(`/club/venues/${props.venue!.slug}`)
  } else {
    form.post('/club/venues')
  }
}

const inputClass = 'w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 transition placeholder-gray-400'
const labelClass = 'block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1.5 uppercase tracking-wide'
</script>

<template>
  <Head :title="isEditing ? 'تعديل الملعب' : 'إضافة ملعب جديد'" />

  <ClubLayout>
    <div class="px-4 sm:px-6 py-6 sm:py-8 max-w-3xl">
      <h1 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
        {{ isEditing ? 'تعديل الملعب' : 'إضافة ملعب جديد' }}
      </h1>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Basic info -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 space-y-4">
          <h2 class="font-semibold text-gray-900 dark:text-white">المعلومات الأساسية</h2>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">الاسم (عربي)</label>
              <input v-model="form.name_ar" type="text" :class="[inputClass, form.errors.name_ar && 'border-red-400']" />
              <p v-if="form.errors.name_ar" class="text-xs text-red-500 mt-1">{{ form.errors.name_ar }}</p>
            </div>
            <div>
              <label :class="labelClass">الاسم (إنجليزي)</label>
              <input v-model="form.name_en" type="text" dir="ltr" :class="[inputClass, form.errors.name_en && 'border-red-400']" />
              <p v-if="form.errors.name_en" class="text-xs text-red-500 mt-1">{{ form.errors.name_en }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">الوصف (عربي)</label>
              <textarea v-model="form.description_ar" rows="3" :class="inputClass" />
            </div>
            <div>
              <label :class="labelClass">الوصف (إنجليزي)</label>
              <textarea v-model="form.description_en" rows="3" dir="ltr" :class="inputClass" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label :class="labelClass">السعر الابتدائي (ل.س)</label>
              <input v-model="form.price_from" type="number" min="0" dir="ltr" :class="[inputClass, form.errors.price_from && 'border-red-400']" />
              <p v-if="form.errors.price_from" class="text-xs text-red-500 mt-1">{{ form.errors.price_from }}</p>
            </div>
            <div>
              <label :class="labelClass">الفئة</label>
              <select v-model="form.category_id" :class="[inputClass, form.errors.category_id && 'border-red-400']">
                <option value="">-- اختر --</option>
                <option v-for="cat in options.categories" :key="cat.id" :value="cat.id">
                  {{ typeof cat.name === 'object' ? (locale === 'ar' ? cat.name.ar : cat.name.en) || cat.name.ar || cat.name.en : cat.name }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label :class="labelClass">الحالة</label>
            <select v-model="form.status" :class="inputClass">
              <option value="active">نشط</option>
              <option value="inactive">غير نشط</option>
            </select>
          </div>
        </div>

        <!-- Amenities -->
        <div class="bg-white dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-6">
          <h2 class="font-semibold text-gray-900 dark:text-white mb-4">المرافق</h2>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="a in amenities"
              :key="a"
              type="button"
              @click="toggleAmenity(a)"
              :class="[
                'text-xs px-3 py-1.5 rounded-lg border transition-colors',
                form.amenities.includes(a)
                  ? 'bg-emerald-500 border-emerald-500 text-white'
                  : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-emerald-400',
              ]"
            >
              {{ a }}
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white transition-colors shadow-sm"
          >
            {{ form.processing ? 'جارٍ الحفظ...' : isEditing ? 'حفظ التعديلات' : 'إضافة الملعب' }}
          </button>
          <a
            :href="isEditing ? `/club/venues/${venue!.slug}` : '/club/venues'"
            class="px-6 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
          >
            إلغاء
          </a>
        </div>
      </form>
    </div>
  </ClubLayout>
</template>
