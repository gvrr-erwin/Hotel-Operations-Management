<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Tip Tracker" subtitle="Record and review staff tip history.">
      <button v-if="auth.canEdit('tips')" @click="openForm()" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Record Tip
      </button>
    </PageHeader>

    <!-- Summary KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
      <KpiCard label="Total in Range" :value="fmt.currency(store.meta.total_amount ?? 0)" color="emerald">
        <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
      </KpiCard>
      <KpiCard label="Total Entries" :value="store.meta.total ?? 0" color="primary">
        <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12z" /></svg></template>
      </KpiCard>
      <KpiCard v-if="avgTip" label="Avg per Entry" :value="fmt.currency(avgTip)" color="amber">
        <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" /></svg></template>
      </KpiCard>
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-3">
      <div>
        <label class="label">From</label>
        <input v-model="filters.date_from" type="date" class="input" />
      </div>
      <div>
        <label class="label">To</label>
        <input v-model="filters.date_to" type="date" class="input" />
      </div>
      <div v-if="auth.canEdit('tips')">
        <label class="label">Employee</label>
        <select v-model="filters.employee_id" class="input">
          <option value="">All Employees</option>
          <option v-for="e in usersStore.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button @click="loadTips" class="btn btn-primary">Filter</button>
        <button @click="clearFilters" class="btn btn-secondary">Clear</button>
      </div>
    </div>

    <!-- Tips Table -->
    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <span class="section-title">Tip Records</span>
        <span class="text-xs text-slate-400">{{ store.meta.total ?? 0 }} entries</span>
      </div>
      <div v-if="store.loading" class="flex justify-center py-12"><AppSpinner size="lg" /></div>
      <EmptyState v-else-if="!store.tips.length" title="No tips found" description="Record the first tip using the button above." />
      <div v-else class="table-wrapper">
        <table class="table">
          <thead><tr>
            <th class="th">Date</th>
            <th class="th">Employee</th>
            <th class="th text-right">Amount</th>
            <th class="th">Note</th>
            <th class="th">Recorded By</th>
            <th v-if="auth.canEdit('tips')" class="th text-right">Actions</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="tip in store.tips" :key="tip.id" class="tr-hover">
              <td class="td text-slate-500 font-mono text-xs">{{ tip.date }}</td>
              <td class="td">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-[10px] font-bold">
                    {{ fmt.initials(tip.employee?.name ?? '') }}
                  </div>
                  <span class="font-medium text-slate-800">{{ tip.employee?.name }}</span>
                </div>
              </td>
              <td class="td text-right font-bold text-emerald-700">{{ fmt.currency(tip.amount) }}</td>
              <td class="td text-xs text-slate-400 max-w-[200px] truncate">{{ tip.note ?? '—' }}</td>
              <td class="td text-xs text-slate-400">{{ tip.recorded_by?.name ?? '—' }}</td>
              <td v-if="auth.canEdit('tips')" class="td text-right">
                <div class="flex justify-end gap-1">
                  <button @click="openForm(tip)" class="btn btn-ghost btn-sm">Edit</button>
                  <button @click="confirmDelete(tip)" class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tip Form Modal -->
    <AppModal v-model="showForm" :title="editTarget ? 'Edit Tip' : 'Record Tip'">
      <form @submit.prevent="handleSave" class="flex flex-col gap-4">
        <AppAlert :message="formError" type="error" />
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Employee</label>
            <select v-model="form.employee_id" class="input" required>
              <option value="">Select employee</option>
              <option v-for="e in usersStore.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Date</label>
            <input v-model="form.date" type="date" class="input" required />
          </div>
        </div>
        <div>
          <label class="label">Amount (USD)</label>
          <input v-model="form.amount" type="number" step="0.01" min="0" class="input" required />
        </div>
        <div>
          <label class="label">Note (optional)</label>
          <textarea v-model="form.note" class="input" rows="2" />
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn btn-secondary" @click="showForm = false">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <AppSpinner v-if="saving" size="sm" color="white" />
            {{ editTarget ? 'Save Changes' : 'Record Tip' }}
          </button>
        </div>
      </form>
    </AppModal>

    <ConfirmModal
      v-model="showDelete"
      title="Delete Tip"
      message="Delete this tip entry?"
      hint="This action cannot be undone."
      confirm-label="Delete"
      :danger="true"
      @confirm="handleDelete"
    />
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useTipsStore } from '@/stores/tips'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { fmt } from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import KpiCard from '@/components/shared/KpiCard.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppModal from '@/components/ui/AppModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import AppAlert from '@/components/ui/AppAlert.vue'

const store      = useTipsStore()
const usersStore = useUsersStore()
const auth       = useAuthStore()
const toast      = useToast()

const filters = reactive({ date_from: '', date_to: '', employee_id: '' })
const showForm    = ref(false)
const showDelete  = ref(false)
const editTarget  = ref(null)
const deleteTarget= ref(null)
const formError   = ref('')
const saving      = ref(false)

const form = reactive({ employee_id: '', amount: '', date: '', note: '' })

const avgTip = computed(() => {
  const total = store.meta.total_amount ?? 0
  const count = store.meta.total ?? 0
  return count > 0 ? (total / count).toFixed(2) : null
})

function openForm(tip = null) {
  editTarget.value = tip
  formError.value  = ''
  Object.assign(form, {
    employee_id: tip?.employee_id ?? '',
    amount:      tip?.amount      ?? '',
    date:        tip?.date        ?? '',
    note:        tip?.note        ?? '',
  })
  showForm.value = true
}

async function handleSave() {
  saving.value    = true
  formError.value = ''
  try {
    if (editTarget.value) {
      await store.updateTip(editTarget.value.id, form)
      toast.success('Tip updated.')
    } else {
      await store.createTip(form)
      toast.success('Tip recorded.')
    }
    showForm.value = false
    loadTips()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save tip.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(tip) {
  deleteTarget.value = tip
  showDelete.value   = true
}

async function handleDelete() {
  try {
    await store.deleteTip(deleteTarget.value.id)
    showDelete.value = false
    toast.success('Tip deleted.')
  } catch {
    toast.error('Failed to delete tip.')
  }
}

async function loadTips() {
  const params = {}
  if (filters.date_from) params.date_from = filters.date_from
  if (filters.date_to)   params.date_to   = filters.date_to
  if (filters.employee_id) params.employee_id = filters.employee_id
  await store.fetchTips(params)
}

function clearFilters() {
  Object.assign(filters, { date_from: '', date_to: '', employee_id: '' })
  loadTips()
}

onMounted(async () => {
  await usersStore.fetchEmployees()
  loadTips()
})
</script>
