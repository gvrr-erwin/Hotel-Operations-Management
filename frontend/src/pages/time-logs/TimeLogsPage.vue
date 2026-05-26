<template>
  <div class="flex flex-col gap-6">
    <PageHeader
      :title="auth.isManagement ? 'Team Time Logs' : 'My Time Logs'"
      :subtitle="auth.isManagement
        ? 'Review attendance, approve entries, and correct logs when needed.'
        : 'Your shift history and approval status.'"
    >
      <button v-if="auth.canEdit('time_logs')" @click="openForm()" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Manual Entry
      </button>
    </PageHeader>

    <div v-if="auth.isManagement" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <KpiCard label="Currently Clocked In" :value="store.summary.meta?.currently_clocked_in ?? 0" color="emerald">
        <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg></template>
      </KpiCard>
      <KpiCard label="Pending Approval" :value="store.summary.meta?.pending_approval ?? 0" color="amber">
        <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
      </KpiCard>
      <KpiCard label="Total Entries" :value="store.meta.total ?? 0" color="primary">
        <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" /></svg></template>
      </KpiCard>
      <KpiCard label="Staff (last 7 days)" :value="store.summary.data?.length ?? 0" color="violet">
        <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493" /></svg></template>
      </KpiCard>
    </div>

    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="label">Date</label>
        <input v-model="filters.date" type="date" class="input" />
      </div>
      <div v-if="auth.isManagement">
        <label class="label">Employee</label>
        <select v-model="filters.user_id" class="input min-w-[180px]">
          <option value="">All Employees</option>
          <option v-for="e in usersStore.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
        </select>
      </div>
      <div v-if="auth.isManagement">
        <label class="label">Department</label>
        <select v-model="filters.department" class="input">
          <option value="">All</option>
          <option v-for="(label, key) in DEPARTMENT_LABELS" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
      <div>
        <label class="label">Shift</label>
        <select v-model="filters.shift_type" class="input">
          <option value="">All Shifts</option>
          <option v-for="s in shifts" :key="s.value" :value="s.value">{{ s.label }}</option>
        </select>
      </div>
      <div v-if="auth.isManagement">
        <label class="label">Status</label>
        <select v-model="filters.status" class="input">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="flagged">Flagged</option>
        </select>
      </div>
      <div class="flex gap-2">
        <button @click="loadLogs" class="btn btn-primary">Filter</button>
        <button @click="clearFilters" class="btn btn-secondary">Clear</button>
      </div>
    </div>

    <div v-if="auth.isManagement && store.summary.data?.length" class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100">
        <span class="section-title">Weekly Attendance</span>
        <span class="ml-2 text-xs text-slate-400">{{ store.summary.meta?.date_from }} → {{ store.summary.meta?.date_to }}</span>
      </div>
      <div class="table-wrapper">
        <table class="table">
          <thead><tr>
            <th class="th">Employee</th>
            <th class="th">Department</th>
            <th class="th">Sessions</th>
            <th class="th">Hours</th>
            <th class="th">Pending</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="(row, i) in store.summary.data" :key="i" class="tr-hover">
              <td class="td font-medium text-slate-800">{{ row.user?.name }}</td>
              <td class="td text-slate-500">{{ DEPARTMENT_LABELS[row.user?.department] ?? '—' }}</td>
              <td class="td">{{ row.sessions }}</td>
              <td class="td font-semibold">{{ row.total_hours }}h</td>
              <td class="td">
                <AppBadge v-if="row.pending" variant="amber">{{ row.pending }} pending</AppBadge>
                <span v-else class="text-slate-400 text-xs">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <span class="section-title">Time Logs</span>
        <span class="text-xs text-slate-400">{{ store.meta.total ?? 0 }} records</span>
      </div>
      <div v-if="store.loading" class="flex justify-center py-12"><AppSpinner size="lg" /></div>
      <EmptyState v-else-if="!store.logs.length" title="No time logs found" description="Try clearing your filters or come back after staff clock in." />
      <div v-else class="table-wrapper">
        <table class="table">
          <thead><tr>
            <th class="th">Date</th>
            <th v-if="auth.isManagement" class="th">Employee</th>
            <th class="th">Shift</th>
            <th class="th">In</th>
            <th class="th">Out</th>
            <th class="th">Hours</th>
            <th class="th">Status</th>
            <th class="th">Logged By</th>
            <th v-if="auth.canEdit('time_logs')" class="th text-right">Actions</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="log in store.logs" :key="log.id" class="tr-hover">
              <td class="td text-slate-500 font-mono text-xs">{{ log.date }}</td>
              <td v-if="auth.isManagement" class="td">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-[10px] font-bold">
                    {{ fmt.initials(log.user?.name ?? '') }}
                  </div>
                  <div>
                    <div class="font-medium text-slate-800 text-sm">{{ log.user?.name }}</div>
                    <div class="text-[10px] text-slate-400">{{ DEPARTMENT_LABELS[log.user?.department] }}</div>
                  </div>
                </div>
              </td>
              <td class="td"><AppBadge :variant="shiftVariant(log.shift_type)">{{ SHIFT_LABELS[log.shift_type] }}</AppBadge></td>
              <td class="td font-mono text-xs text-slate-600">{{ fmt.time(log.clock_in) }}</td>
              <td class="td font-mono text-xs text-slate-600">{{ log.clock_out ? fmt.time(log.clock_out) : '—' }}</td>
              <td class="td text-sm font-semibold" :class="log.hours_worked ? 'text-slate-800' : 'text-slate-300'">
                {{ log.hours_worked ? `${log.hours_worked}h` : '—' }}
              </td>
              <td class="td"><AppBadge :variant="statusVariant(log.status)">{{ STATUS_LABELS[log.status] }}</AppBadge></td>
              <td class="td text-xs text-slate-500">
                <span v-if="log.is_self" class="text-emerald-600 font-medium">Self</span>
                <span v-else>{{ log.logged_by?.name ?? '—' }}</span>
              </td>
              <td v-if="auth.canEdit('time_logs')" class="td text-right">
                <div class="flex justify-end gap-1">
                  <button v-if="log.status === 'pending'" @click="approve(log)" class="btn btn-ghost btn-sm text-emerald-600 hover:bg-emerald-50">Approve</button>
                  <button @click="openForm(log)" class="btn btn-ghost btn-sm">Edit</button>
                  <button @click="confirmDelete(log)" class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <AppModal v-model="showForm" :title="editTarget ? 'Edit Time Log' : 'Manual Time Log'">
      <form @submit.prevent="handleSave" class="flex flex-col gap-4">
        <AppAlert :message="formError" type="error" />
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Employee</label>
            <select v-model="form.user_id" class="input" required>
              <option value="">Select employee</option>
              <option v-for="e in usersStore.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
            </select>
          </div>
          <div>
            <label class="label">Date</label>
            <input v-model="form.date" type="date" class="input" required />
          </div>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="label">Shift</label>
            <select v-model="form.shift_type" class="input" required>
              <option v-for="s in shifts" :key="s.value" :value="s.value">{{ s.label }}</option>
            </select>
          </div>
          <div>
            <label class="label">Clock In</label>
            <input v-model="form.clock_in" type="time" class="input" required />
          </div>
          <div>
            <label class="label">Clock Out</label>
            <input v-model="form.clock_out" type="time" class="input" />
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
            {{ editTarget ? 'Save Changes' : 'Create Log' }}
          </button>
        </div>
      </form>
    </AppModal>

    <ConfirmModal v-model="showDelete" title="Delete Time Log" message="Delete this time log entry?"
                  confirm-label="Delete" :danger="true" @confirm="handleDelete" />
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useTimeLogsStore } from '@/stores/timeLogs'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { fmt, SHIFT_LABELS, STATUS_LABELS, DEPARTMENT_LABELS } from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import KpiCard from '@/components/shared/KpiCard.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppModal from '@/components/ui/AppModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const store      = useTimeLogsStore()
const usersStore = useUsersStore()
const auth       = useAuthStore()
const toast      = useToast()

const filters = reactive({ date: '', user_id: '', department: '', shift_type: '', status: '' })
const showForm    = ref(false)
const showDelete  = ref(false)
const editTarget  = ref(null)
const deleteTarget= ref(null)
const formError   = ref('')
const saving      = ref(false)

const form = reactive({ user_id: '', date: '', clock_in: '', clock_out: '', shift_type: 'morning', notes: '' })

const shifts = [
  { value: 'morning',   label: 'Morning' },
  { value: 'afternoon', label: 'Afternoon' },
  { value: 'evening',   label: 'Evening' },
  { value: 'night',     label: 'Night' },
]

const shiftVariantMap  = { morning: 'amber', afternoon: 'sky', evening: 'indigo', night: 'slate' }
const statusVariantMap = { pending: 'amber', approved: 'emerald', flagged: 'red' }
function shiftVariant(s)  { return shiftVariantMap[s] ?? 'slate' }
function statusVariant(s) { return statusVariantMap[s] ?? 'slate' }

function openForm(log = null) {
  editTarget.value = log
  formError.value  = ''
  Object.assign(form, {
    user_id:    log?.user_id    ?? '',
    date:       log?.date       ?? new Date().toISOString().slice(0, 10),
    clock_in:   log?.clock_in   ?? '',
    clock_out:  log?.clock_out  ?? '',
    shift_type: log?.shift_type ?? 'morning',
    notes:      log?.notes      ?? '',
  })
  showForm.value = true
}

async function handleSave() {
  saving.value    = true
  formError.value = ''
  try {
    if (editTarget.value) {
      await store.updateLog(editTarget.value.id, form)
      toast.success('Time log updated.')
    } else {
      await store.createLog(form)
      toast.success('Time log created.')
    }
    showForm.value = false
    await loadLogs()
    if (auth.isManagement) await store.fetchSummary()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save log.'
  } finally { saving.value = false }
}

async function approve(log) {
  try {
    await store.approveLog(log.id)
    toast.success('Time log approved.')
    if (auth.isManagement) await store.fetchSummary()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to approve.')
  }
}

function confirmDelete(log) {
  deleteTarget.value = log
  showDelete.value   = true
}

async function handleDelete() {
  try {
    await store.deleteLog(deleteTarget.value.id)
    showDelete.value = false
    toast.success('Log deleted.')
  } catch { toast.error('Failed to delete log.') }
}

async function loadLogs() {
  const params = {}
  if (filters.date)       params.date       = filters.date
  if (filters.user_id)    params.user_id    = filters.user_id
  if (filters.department) params.department = filters.department
  if (filters.shift_type) params.shift_type = filters.shift_type
  if (filters.status)     params.status     = filters.status
  await store.fetchLogs(params)
}

function clearFilters() {
  Object.assign(filters, { date: '', user_id: '', department: '', shift_type: '', status: '' })
  loadLogs()
}

onMounted(async () => {
  if (auth.isManagement) await usersStore.fetchEmployees()
  await loadLogs()
  if (auth.isManagement) await store.fetchSummary()
})
</script>
