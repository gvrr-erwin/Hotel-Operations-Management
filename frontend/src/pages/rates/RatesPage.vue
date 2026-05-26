<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Hotel Rate Management" subtitle="Track and compare hotel room rates across properties.">
      <button v-if="auth.canEdit('rates')" @click="openForm()" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Add Rate
      </button>
    </PageHeader>

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-3">
      <div class="flex-1 min-w-[140px]">
        <label class="label">Date</label>
        <input v-model="filters.date" type="date" class="input" />
      </div>
      <div class="flex-1 min-w-[140px]">
        <label class="label">Hotel</label>
        <select v-model="filters.hotel_id" class="input">
          <option value="">All Hotels</option>
          <option v-for="h in store.hotels" :key="h.id" :value="h.id">{{ h.name }}</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button @click="loadRates" class="btn btn-primary">Search</button>
        <button @click="clearFilters" class="btn btn-secondary">Clear</button>
      </div>
    </div>

    <!-- Compare view -->
    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="label">Compare Date</label>
        <input v-model="compare.date" type="date" class="input" />
      </div>
      <div>
        <label class="label">vs. Date</label>
        <input v-model="compare.compare_date" type="date" class="input" />
      </div>
      <button @click="loadComparison" class="btn btn-secondary">Compare</button>
    </div>

    <!-- Comparison result -->
    <div v-if="comparison" class="card p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="section-title">Rate Comparison: {{ comparison.date }} vs {{ comparison.compare_date }}</h3>
        <button @click="comparison = null" class="text-slate-400 hover:text-slate-600">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead><tr>
            <th class="th">Hotel</th><th class="th">Room Type</th>
            <th class="th">Current</th><th class="th">Previous</th><th class="th">Change</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="r in comparison.rates" :key="`${r.hotel?.id}-${r.room_type?.id}`" class="tr-hover">
              <td class="td font-medium">{{ r.hotel?.name }}</td>
              <td class="td text-slate-500">{{ r.room_type?.name }}</td>
              <td class="td font-semibold">{{ fmt.currency(r.rate) }}</td>
              <td class="td text-slate-400">{{ r.prev_rate != null ? fmt.currency(r.prev_rate) : '—' }}</td>
              <td class="td">
                <span v-if="r.change != null" :class="['text-xs font-semibold', r.change >= 0 ? 'text-emerald-600' : 'text-red-500']">
                  {{ r.change >= 0 ? '+' : '' }}{{ fmt.currency(r.change) }} ({{ fmt.pct(r.change_pct) }})
                </span>
                <span v-else class="text-slate-300">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Rates table -->
    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <span class="section-title">Rate Entries</span>
        <span class="text-xs text-slate-400">{{ store.meta.total ?? 0 }} records</span>
      </div>
      <div v-if="store.loading" class="flex justify-center py-12"><AppSpinner size="lg" /></div>
      <div v-else-if="!store.rates.length" class="py-8">
        <EmptyState title="No rates found" description="Add rates using the button above or adjust your filters." />
      </div>
      <div v-else class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th class="th">Date</th><th class="th">Hotel</th><th class="th">Room Type</th>
              <th class="th text-right">Rate</th><th class="th">Notes</th>
              <th v-if="auth.canEdit('rates')" class="th text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="rate in store.rates" :key="rate.id" class="tr-hover">
              <td class="td text-slate-500 font-mono text-xs">{{ rate.date }}</td>
              <td class="td font-medium text-slate-800">{{ rate.hotel?.name }}</td>
              <td class="td text-slate-500">{{ rate.room_type?.name }}</td>
              <td class="td text-right font-bold text-slate-900">{{ fmt.currency(rate.rate) }}</td>
              <td class="td text-xs text-slate-400 max-w-xs truncate">{{ rate.notes ?? '—' }}</td>
              <td v-if="auth.canEdit('rates')" class="td text-right">
                <div class="flex justify-end gap-1">
                  <button @click="openForm(rate)" class="btn btn-ghost btn-sm">Edit</button>
                  <button @click="confirmDelete(rate)" class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Rate Form Modal -->
    <AppModal v-model="showForm" :title="editTarget ? 'Edit Rate' : 'Add Rate'">
      <form @submit.prevent="handleSave" class="flex flex-col gap-4">
        <AppAlert :message="formError" type="error" />
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Hotel</label>
            <select v-model="form.hotel_id" class="input" required>
              <option value="">Select hotel</option>
              <option v-for="h in store.hotels" :key="h.id" :value="h.id">{{ h.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Room Type</label>
            <select v-model="form.room_type_id" class="input" required>
              <option value="">Select type</option>
              <option v-for="rt in store.roomTypes" :key="rt.id" :value="rt.id">{{ rt.name }}</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Date</label>
            <input v-model="form.date" type="date" class="input" required />
          </div>
          <div>
            <label class="label">Rate (USD)</label>
            <input v-model="form.rate" type="number" step="0.01" min="0" class="input" required />
          </div>
        </div>
        <div>
          <label class="label">Notes (optional)</label>
          <textarea v-model="form.notes" class="input" rows="2" />
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn btn-secondary" @click="showForm = false">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <AppSpinner v-if="saving" size="sm" color="white" />
            {{ editTarget ? 'Save Changes' : 'Add Rate' }}
          </button>
        </div>
      </form>
    </AppModal>

    <!-- Delete confirm -->
    <ConfirmModal
      v-model="showDelete"
      title="Delete Rate"
      :message="`Delete rate for ${deleteTarget?.hotel?.name} on ${deleteTarget?.date}?`"
      hint="This action cannot be undone."
      confirm-label="Delete"
      :danger="true"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRatesStore } from '@/stores/rates'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { fmt } from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppModal from '@/components/ui/AppModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import AppAlert from '@/components/ui/AppAlert.vue'

const store  = useRatesStore()
const auth   = useAuthStore()
const toast  = useToast()

const filters    = reactive({ date: '', hotel_id: '' })
const compare    = reactive({ date: '', compare_date: '' })
const comparison = ref(null)

const showForm   = ref(false)
const showDelete = ref(false)
const editTarget = ref(null)
const deleteTarget = ref(null)
const formError  = ref('')
const saving     = ref(false)

const form = reactive({ hotel_id: '', room_type_id: '', date: '', rate: '', notes: '' })

function openForm(rate = null) {
  editTarget.value = rate
  formError.value  = ''
  Object.assign(form, {
    hotel_id:     rate?.hotel_id     ?? '',
    room_type_id: rate?.room_type_id ?? '',
    date:         rate?.date         ?? '',
    rate:         rate?.rate         ?? '',
    notes:        rate?.notes        ?? '',
  })
  showForm.value = true
}

async function handleSave() {
  saving.value    = true
  formError.value = ''
  try {
    if (editTarget.value) {
      await store.updateRate(editTarget.value.id, form)
      toast.success('Rate updated.')
    } else {
      await store.saveRate(form)
      toast.success('Rate added.')
    }
    showForm.value = false
    loadRates()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save rate.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(rate) {
  deleteTarget.value = rate
  showDelete.value   = true
}

async function handleDelete() {
  try {
    await store.deleteRate(deleteTarget.value.id)
    showDelete.value = false
    toast.success('Rate deleted.')
  } catch {
    toast.error('Failed to delete rate.')
  }
}

async function loadComparison() {
  if (!compare.date) return
  try {
    comparison.value = await store.compare(compare)
  } catch {
    toast.error('Failed to load comparison.')
  }
}

async function loadRates() {
  await store.fetchRates({ date: filters.date || undefined, hotel_id: filters.hotel_id || undefined })
}

function clearFilters() {
  filters.date     = ''
  filters.hotel_id = ''
  loadRates()
}

onMounted(async () => {
  await Promise.all([store.fetchHotels(), store.fetchRoomTypes()])
  loadRates()
})
</script>
