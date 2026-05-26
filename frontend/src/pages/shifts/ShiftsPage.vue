<template>
  <div class="flex flex-col gap-6">
    <PageHeader
      :title="auth.isManagement ? 'Shift Schedule' : 'My Schedule'"
      :subtitle="auth.isManagement ? 'Plan and publish staff shifts across the week.' : 'Your upcoming shifts.'"
    >
      <button v-if="auth.canEdit('shifts')" @click="openForm()" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        Schedule Shift
      </button>
    </PageHeader>

    <!-- Week nav -->
    <div class="card p-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <button @click="shiftWeek(-1)" class="btn btn-secondary btn-sm">←</button>
        <div class="text-sm font-semibold text-slate-700 min-w-[230px] text-center">
          {{ fmt.date(weekStart, 'MMM D') }} — {{ fmt.date(weekEnd, 'MMM D, YYYY') }}
        </div>
        <button @click="shiftWeek(1)" class="btn btn-secondary btn-sm">→</button>
        <button @click="goToToday" class="btn btn-ghost btn-sm">This week</button>
      </div>
      <div v-if="auth.isManagement" class="flex gap-2 items-end">
        <div>
          <label class="label">Department</label>
          <select v-model="filters.department" @change="load" class="input">
            <option value="">All departments</option>
            <option v-for="(label, key) in DEPARTMENT_LABELS" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Calendar grid -->
    <div class="card p-0 overflow-hidden">
      <div v-if="store.loading" class="flex justify-center py-16"><AppSpinner size="lg" /></div>
      <div v-else class="overflow-x-auto">
        <div class="min-w-[900px] grid grid-cols-8 border-b border-slate-100">
          <div class="th !bg-white sticky left-0 z-10 border-r border-slate-100">Employee</div>
          <div v-for="d in weekDays" :key="d.iso" class="th text-center">
            <div class="font-bold text-slate-700">{{ d.label }}</div>
            <div class="text-[10px] text-slate-400 font-normal mt-0.5">{{ d.dateLabel }}</div>
          </div>
        </div>
        <div v-if="!people.length" class="py-12">
          <EmptyState title="No shifts scheduled" description="Create your first shift using the button above." />
        </div>
        <div v-else>
          <div v-for="(person, pi) in people" :key="pi" class="grid grid-cols-8 border-b border-slate-50 hover:bg-slate-50/30">
            <div class="td sticky left-0 bg-white border-r border-slate-100">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-[10px] font-bold flex items-center justify-center">
                  {{ fmt.initials(person.name) }}
                </div>
                <div class="min-w-0">
                  <div class="text-sm font-semibold text-slate-800 truncate">{{ person.name }}</div>
                  <div class="text-[10px] text-slate-400">{{ DEPARTMENT_LABELS[person.department] ?? '' }}</div>
                </div>
              </div>
            </div>
            <div v-for="d in weekDays" :key="d.iso" class="td !py-2 border-r border-slate-50 last:border-r-0">
              <div v-for="s in cellShifts(person.id, d.iso)" :key="s.id"
                   @click="auth.canEdit('shifts') && openForm(s)"
                   :class="['rounded-lg px-2 py-1.5 mb-1 text-xs', shiftCellColor(s.shift_type), auth.canEdit('shifts') ? 'cursor-pointer hover:ring-2 hover:ring-primary-300' : '']">
                <div class="font-semibold capitalize">{{ s.shift_type }}</div>
                <div class="font-mono text-[10px] opacity-80">{{ fmt.time(s.start_time) }} – {{ fmt.time(s.end_time) }}</div>
              </div>
              <button v-if="auth.canEdit('shifts') && !cellShifts(person.id, d.iso).length"
                      @click="openForm(null, person.id, d.iso)"
                      class="w-full text-[10px] text-slate-300 hover:text-primary-600 hover:bg-primary-50 rounded-lg py-1.5 transition">
                + Add
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Modal -->
    <AppModal v-model="showForm" :title="editTarget ? 'Edit Shift' : 'Schedule Shift'">
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
              <option value="morning">Morning</option>
              <option value="afternoon">Afternoon</option>
              <option value="evening">Evening</option>
              <option value="night">Night</option>
            </select>
          </div>
          <div>
            <label class="label">Start</label>
            <input v-model="form.start_time" type="time" class="input" required />
          </div>
          <div>
            <label class="label">End</label>
            <input v-model="form.end_time" type="time" class="input" required />
          </div>
        </div>
        <div>
          <label class="label">Notes</label>
          <textarea v-model="form.notes" rows="2" class="input" />
        </div>
        <div class="flex justify-between items-center pt-1">
          <button v-if="editTarget" type="button" @click="handleDelete" class="text-xs font-semibold text-red-500 hover:text-red-700">Delete shift</button>
          <div class="flex gap-2 ml-auto">
            <button type="button" class="btn btn-secondary" @click="showForm = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <AppSpinner v-if="saving" size="sm" color="white" />
              {{ editTarget ? 'Save Changes' : 'Schedule' }}
            </button>
          </div>
        </div>
      </form>
    </AppModal>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import dayjs from 'dayjs'
import { useShiftsStore } from '@/stores/shifts'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { fmt, DEPARTMENT_LABELS } from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppAlert from '@/components/ui/AppAlert.vue'

const store      = useShiftsStore()
const usersStore = useUsersStore()
const auth       = useAuthStore()
const toast      = useToast()

const weekStart = ref(dayjs().startOf('week').format('YYYY-MM-DD'))
const weekEnd   = computed(() => dayjs(weekStart.value).add(6, 'day').format('YYYY-MM-DD'))
const filters   = reactive({ department: '' })

const weekDays = computed(() =>
  Array.from({ length: 7 }, (_, i) => {
    const d = dayjs(weekStart.value).add(i, 'day')
    return { iso: d.format('YYYY-MM-DD'), label: d.format('ddd'), dateLabel: d.format('MMM D') }
  })
)

const people = computed(() => {
  if (!auth.isManagement) {
    return auth.user ? [{ id: auth.user.id, name: auth.user.name, department: auth.user.department }] : []
  }
  const list = filters.department
    ? usersStore.employees.filter(e => e.department === filters.department)
    : usersStore.employees
  // Only include people who have a shift this week OR all active users (to allow scheduling)
  return list
})

function cellShifts(userId, iso) {
  return store.shifts.filter(s => s.user_id === userId && s.date === iso)
}

const shiftColors = {
  morning:   'bg-amber-50 text-amber-800 border border-amber-200',
  afternoon: 'bg-sky-50 text-sky-800 border border-sky-200',
  evening:   'bg-indigo-50 text-indigo-800 border border-indigo-200',
  night:     'bg-slate-100 text-slate-700 border border-slate-200',
}
function shiftCellColor(type) { return shiftColors[type] ?? shiftColors.morning }

const showForm    = ref(false)
const saving      = ref(false)
const formError   = ref('')
const editTarget  = ref(null)
const form        = reactive({ user_id: '', date: '', shift_type: 'morning', start_time: '08:00', end_time: '16:00', notes: '' })

function openForm(shift = null, userId = null, date = null) {
  editTarget.value = shift
  formError.value  = ''
  Object.assign(form, {
    user_id:    shift?.user_id    ?? userId ?? '',
    date:       shift?.date       ?? date   ?? weekStart.value,
    shift_type: shift?.shift_type ?? 'morning',
    start_time: shift?.start_time ?? '08:00',
    end_time:   shift?.end_time   ?? '16:00',
    notes:      shift?.notes      ?? '',
  })
  showForm.value = true
}

async function handleSave() {
  saving.value = true
  formError.value = ''
  try {
    if (editTarget.value) {
      await store.updateShift(editTarget.value.id, form)
      toast.success('Shift updated.')
    } else {
      await store.createShift(form)
      toast.success('Shift scheduled.')
    }
    showForm.value = false
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save shift.'
  } finally { saving.value = false }
}

async function handleDelete() {
  if (!editTarget.value) return
  if (!confirm('Remove this shift?')) return
  try {
    await store.deleteShift(editTarget.value.id)
    toast.success('Shift removed.')
    showForm.value = false
  } catch {
    toast.error('Failed to remove shift.')
  }
}

function shiftWeek(d) {
  weekStart.value = dayjs(weekStart.value).add(d, 'week').format('YYYY-MM-DD')
  load()
}
function goToToday() {
  weekStart.value = dayjs().startOf('week').format('YYYY-MM-DD')
  load()
}

async function load() {
  await store.fetchShifts({
    date_from:  weekStart.value,
    date_to:    weekEnd.value,
    department: filters.department || undefined,
  })
}

onMounted(async () => {
  if (auth.isManagement) await usersStore.fetchEmployees()
  await load()
})
</script>
