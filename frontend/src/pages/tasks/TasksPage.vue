<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Operations Board" subtitle="Housekeeping, maintenance, and front-desk requests in one place.">
      <button @click="openForm()" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        New Task
      </button>
    </PageHeader>

    <!-- KPIs -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
      <KpiCard label="Open" :value="store.counts.open" color="slate" />
      <KpiCard label="In Progress" :value="store.counts.in_progress" color="primary" />
      <KpiCard label="Completed" :value="store.counts.completed" color="emerald" />
      <KpiCard label="Overdue" :value="store.counts.overdue" color="amber" />
    </div>

    <!-- Filters -->
    <div class="card p-4 flex flex-wrap gap-3 items-end">
      <div>
        <label class="label">Search</label>
        <input v-model="filters.search" @input="debounceLoad" type="text" placeholder="Title or room…" class="input" />
      </div>
      <div>
        <label class="label">Category</label>
        <select v-model="filters.category" @change="load" class="input">
          <option value="">All</option>
          <option v-for="(label, key) in TASK_CATEGORY_LABELS" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
      <div>
        <label class="label">Priority</label>
        <select v-model="filters.priority" @change="load" class="input">
          <option value="">All</option>
          <option value="urgent">Urgent</option>
          <option value="high">High</option>
          <option value="medium">Medium</option>
          <option value="low">Low</option>
        </select>
      </div>
      <div v-if="auth.isManagement">
        <label class="label">Assignee</label>
        <select v-model="filters.assigned_to" @change="load" class="input min-w-[160px]">
          <option value="">Anyone</option>
          <option v-for="e in usersStore.employees" :key="e.id" :value="e.id">{{ e.name }}</option>
        </select>
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-600 ml-auto">
        <input v-model="filters.mine" @change="load" type="checkbox" class="rounded border-slate-300" />
        Only mine
      </label>
    </div>

    <!-- Board -->
    <div v-if="store.loading" class="flex justify-center py-16"><AppSpinner size="lg" /></div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
      <div v-for="col in columns" :key="col.key" class="flex flex-col gap-3">
        <div class="flex items-center justify-between px-1">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" :class="col.dot"></span>
            <h3 class="text-sm font-semibold text-slate-700">{{ col.label }}</h3>
            <span class="text-xs text-slate-400">{{ tasksByStatus(col.key).length }}</span>
          </div>
        </div>
        <div class="flex flex-col gap-3 min-h-[80px]">
          <div v-for="t in tasksByStatus(col.key)" :key="t.id"
               class="card p-4 hover:shadow-card-hover transition cursor-pointer"
               @click="openForm(t)">
            <div class="flex items-start justify-between gap-2 mb-2">
              <h4 class="text-sm font-semibold text-slate-800 leading-tight">{{ t.title }}</h4>
              <AppBadge :variant="priorityVariant(t.priority)" class="shrink-0">{{ t.priority }}</AppBadge>
            </div>
            <div class="flex items-center flex-wrap gap-2 text-[11px] text-slate-500">
              <span :class="['inline-flex items-center px-2 py-0.5 rounded-full font-semibold', TASK_CATEGORY_COLORS[t.category]]">
                {{ TASK_CATEGORY_LABELS[t.category] }}
              </span>
              <span v-if="t.room_number" class="font-mono">Room {{ t.room_number }}</span>
              <span v-if="t.due_at" :class="['flex items-center gap-1', t.is_overdue && t.status !== 'completed' ? 'text-red-600 font-semibold' : '']">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ fmt.relative(t.due_at) }}
              </span>
            </div>
            <div class="mt-3 flex items-center justify-between">
              <div v-if="t.assignee" class="flex items-center gap-1.5">
                <div class="w-5 h-5 rounded-full bg-primary-100 text-primary-700 text-[9px] font-bold flex items-center justify-center">
                  {{ fmt.initials(t.assignee.name) }}
                </div>
                <span class="text-[11px] text-slate-500">{{ t.assignee.name }}</span>
              </div>
              <span v-else class="text-[11px] text-slate-400">Unassigned</span>
              <select :value="t.status" @click.stop @change="changeStatus(t, $event.target.value)"
                      class="text-[10px] font-semibold border border-slate-200 rounded-md px-1.5 py-0.5 bg-white text-slate-600 hover:border-primary-300">
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>
          <EmptyState v-if="!tasksByStatus(col.key).length" :title="col.empty" :description="''" />
        </div>
      </div>
    </div>

    <!-- Form Modal -->
    <AppModal v-model="showForm" :title="editTarget ? 'Edit Task' : 'New Task'">
      <form @submit.prevent="handleSave" class="flex flex-col gap-4">
        <AppAlert :message="formError" type="error" />
        <div>
          <label class="label">Title</label>
          <input v-model="form.title" type="text" class="input" required placeholder="e.g. Clean room 412" />
        </div>
        <div>
          <label class="label">Description</label>
          <textarea v-model="form.description" rows="2" class="input" placeholder="Optional details…" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Category</label>
            <select v-model="form.category" class="input" required>
              <option v-for="(label, key) in TASK_CATEGORY_LABELS" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div>
            <label class="label">Priority</label>
            <select v-model="form.priority" class="input" required>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Room</label>
            <input v-model="form.room_number" type="text" class="input" placeholder="Optional" />
          </div>
          <div>
            <label class="label">Due</label>
            <input v-model="form.due_at" type="datetime-local" class="input" />
          </div>
        </div>
        <div>
          <label class="label">Assign to</label>
          <select v-model="form.assigned_to" class="input">
            <option value="">Unassigned</option>
            <option v-for="e in usersStore.employees" :key="e.id" :value="e.id">{{ e.name }} — {{ DEPARTMENT_LABELS[e.department] }}</option>
          </select>
        </div>
        <div class="flex justify-between pt-1">
          <button v-if="editTarget && auth.canEdit('tasks')" type="button" @click="handleDelete" class="text-xs font-semibold text-red-500 hover:text-red-700">Delete</button>
          <div class="flex gap-2 ml-auto">
            <button type="button" class="btn btn-secondary" @click="showForm = false">Cancel</button>
            <button type="submit" class="btn btn-primary" :disabled="saving">
              <AppSpinner v-if="saving" size="sm" color="white" />
              {{ editTarget ? 'Save' : 'Create Task' }}
            </button>
          </div>
        </div>
      </form>
    </AppModal>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useTasksStore } from '@/stores/tasks'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import {
  fmt, TASK_CATEGORY_LABELS, TASK_CATEGORY_COLORS, DEPARTMENT_LABELS,
} from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import KpiCard from '@/components/shared/KpiCard.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppModal from '@/components/ui/AppModal.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const store      = useTasksStore()
const usersStore = useUsersStore()
const auth       = useAuthStore()
const toast      = useToast()

const filters = reactive({ search: '', category: '', priority: '', assigned_to: '', mine: false })

const columns = [
  { key: 'open',        label: 'Open',        dot: 'bg-slate-400',   empty: 'No open tasks' },
  { key: 'in_progress', label: 'In Progress', dot: 'bg-sky-500',     empty: 'Nothing in progress' },
  { key: 'completed',   label: 'Completed',   dot: 'bg-emerald-500', empty: 'Nothing completed yet' },
  { key: 'cancelled',   label: 'Cancelled',   dot: 'bg-slate-300',   empty: 'No cancelled tasks' },
]

function tasksByStatus(status) {
  return store.tasks.filter(t => t.status === status)
}

const priorityVariantMap = { low: 'slate', medium: 'sky', high: 'amber', urgent: 'red' }
function priorityVariant(p) { return priorityVariantMap[p] ?? 'slate' }

let debounceTimer = null
function debounceLoad() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(load, 250)
}

async function load() {
  const params = {}
  if (filters.search)       params.search      = filters.search
  if (filters.category)     params.category    = filters.category
  if (filters.priority)     params.priority    = filters.priority
  if (filters.assigned_to)  params.assigned_to = filters.assigned_to
  if (filters.mine)         params.mine        = 1
  await store.fetchTasks(params)
}

const showForm   = ref(false)
const saving     = ref(false)
const formError  = ref('')
const editTarget = ref(null)
const form       = reactive({
  title: '', description: '', category: 'housekeeping', priority: 'medium',
  room_number: '', assigned_to: '', due_at: '',
})

function openForm(task = null) {
  editTarget.value = task
  formError.value  = ''
  Object.assign(form, {
    title:        task?.title       ?? '',
    description:  task?.description ?? '',
    category:     task?.category    ?? 'housekeeping',
    priority:     task?.priority    ?? 'medium',
    room_number:  task?.room_number ?? '',
    assigned_to:  task?.assignee?.id ?? '',
    due_at:       task?.due_at ? task.due_at.slice(0, 16) : '',
  })
  showForm.value = true
}

async function handleSave() {
  saving.value = true
  formError.value = ''
  try {
    const payload = { ...form, assigned_to: form.assigned_to || null, due_at: form.due_at || null }
    if (editTarget.value) {
      await store.updateTask(editTarget.value.id, payload)
      toast.success('Task updated.')
    } else {
      await store.createTask(payload)
      toast.success('Task created.')
    }
    showForm.value = false
    await load()
  } catch (e) {
    formError.value = e.response?.data?.message ?? 'Failed to save task.'
  } finally { saving.value = false }
}

async function handleDelete() {
  if (!editTarget.value || !confirm('Delete this task?')) return
  try {
    await store.deleteTask(editTarget.value.id)
    toast.success('Task deleted.')
    showForm.value = false
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to delete.')
  }
}

async function changeStatus(task, status) {
  try {
    await store.setStatus(task.id, status)
    toast.success('Status updated.')
    await load()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to update status.')
  }
}

onMounted(async () => {
  await usersStore.fetchEmployees()
  await load()
})
</script>
