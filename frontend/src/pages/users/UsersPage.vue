<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="User Management" subtitle="Manage system users, roles, and access.">
      <button @click="openForm()" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        New User
      </button>
    </PageHeader>

    <!-- Role distribution -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
      <div v-for="role in roles" :key="role.value" class="card p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 mb-1">{{ role.label }}</p>
        <p class="text-2xl font-bold text-slate-900">{{ roleCount(role.value) }}</p>
        <div class="mt-2 h-1 bg-slate-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full" :class="role.bar" :style="{ width: `${rolePercent(role.value)}%` }" />
        </div>
      </div>
    </div>

    <!-- Filters + search -->
    <div class="card p-4 flex flex-wrap gap-3">
      <div class="flex-1 min-w-[200px]">
        <label class="label">Search</label>
        <input v-model="filters.search" type="search" class="input" placeholder="Name or username..." />
      </div>
      <div>
        <label class="label">Role</label>
        <select v-model="filters.role" class="input">
          <option value="">All Roles</option>
          <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
        </select>
      </div>
      <div>
        <label class="label">Status</label>
        <select v-model="filters.is_active" class="input">
          <option value="">All</option>
          <option value="true">Active</option>
          <option value="false">Inactive</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button @click="loadUsers" class="btn btn-primary">Search</button>
        <button @click="clearFilters" class="btn btn-secondary">Clear</button>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <span class="section-title">All Users</span>
        <span class="text-xs text-slate-400">{{ store.meta.total ?? 0 }} accounts</span>
      </div>
      <div v-if="store.loading" class="flex justify-center py-12"><AppSpinner size="lg" /></div>
      <EmptyState v-else-if="!store.users.length" title="No users found" description="Create the first user using the button above." />
      <div v-else class="table-wrapper">
        <table class="table">
          <thead><tr>
            <th class="th">User</th><th class="th">Username</th><th class="th">Role</th>
            <th class="th">Status</th><th class="th">Last Login</th><th class="th text-right">Actions</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="user in store.users" :key="user.id" class="tr-hover" :class="!user.is_active ? 'opacity-50' : ''">
              <td class="td">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold shrink-0">
                    {{ fmt.initials(user.name) }}
                  </div>
                  <div>
                    <p class="font-semibold text-slate-800">{{ user.name }}</p>
                    <p v-if="user.id === auth.user?.id" class="text-[10px] text-primary-600 font-semibold uppercase">You</p>
                  </div>
                </div>
              </td>
              <td class="td font-mono text-xs text-slate-400">{{ user.username }}</td>
              <td class="td"><AppBadge :variant="roleVariant(user.role)">{{ user.role_label }}</AppBadge></td>
              <td class="td">
                <span v-if="user.is_active" class="flex items-center gap-1.5 text-xs font-medium text-emerald-600">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" /> Active
                </span>
                <span v-else class="flex items-center gap-1.5 text-xs font-medium text-slate-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-300" /> Disabled
                </span>
              </td>
              <td class="td text-xs text-slate-400">{{ user.last_login_at ? fmt.dateTime(user.last_login_at) : '—' }}</td>
              <td class="td text-right">
                <div class="flex justify-end gap-1">
                  <button @click="openForm(user)" class="btn btn-ghost btn-sm">Edit</button>
                  <button
                    v-if="user.is_active && user.id !== auth.user?.id"
                    @click="confirmDisable(user)"
                    class="btn btn-ghost btn-sm text-red-500 hover:bg-red-50"
                  >Disable</button>
                  <button
                    v-else-if="!user.is_active"
                    @click="handleReactivate(user)"
                    class="btn btn-ghost btn-sm text-emerald-600 hover:bg-emerald-50"
                  >Reactivate</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Form Modal -->
    <AppModal v-model="showForm" :title="editTarget ? 'Edit User' : 'Create User'">
      <form @submit.prevent="handleSave" class="flex flex-col gap-4">
        <AppAlert :message="formError" type="error" />
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Full Name</label>
            <input v-model="form.name" type="text" class="input" required />
          </div>
          <div>
            <label class="label">Username</label>
            <input v-model="form.username" type="text" class="input" required />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">{{ editTarget ? 'New Password (optional)' : 'Password' }}</label>
            <input v-model="form.password" type="text" class="input" :placeholder="editTarget ? '••••••••' : 'Enter password'" />
          </div>
          <div>
            <label class="label">Role</label>
            <select v-model="form.role" class="input" required>
              <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="label">Department</label>
            <select v-model="form.department" class="input">
              <option v-for="(label, key) in DEPARTMENT_LABELS" :key="key" :value="key">{{ label }}</option>
            </select>
          </div>
          <div>
            <label class="label">Position</label>
            <input v-model="form.position" type="text" class="input" placeholder="e.g. Front Desk Agent" />
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" class="btn btn-secondary" @click="showForm = false">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="saving">
            <AppSpinner v-if="saving" size="sm" color="white" />
            {{ editTarget ? 'Save Changes' : 'Create User' }}
          </button>
        </div>
      </form>
    </AppModal>

    <ConfirmModal
      v-model="showDisable"
      title="Disable User"
      :message="`Disable ${disableTarget?.name}'s login access?`"
      hint="All data is preserved. The account can be reactivated later."
      confirm-label="Disable Account"
      :danger="true"
      @confirm="handleDisable"
    />
  </div>
</template>

<script setup>
import { reactive, ref, onMounted, computed } from 'vue'
import { useUsersStore } from '@/stores/users'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { fmt, DEPARTMENT_LABELS } from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppModal from '@/components/ui/AppModal.vue'
import ConfirmModal from '@/components/ui/ConfirmModal.vue'
import AppAlert from '@/components/ui/AppAlert.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const store = useUsersStore()
const auth  = useAuthStore()
const toast = useToast()

const roles = [
  { value: 'admin',               label: 'Admin',               bar: 'bg-violet-500',  variant: 'violet' },
  { value: 'general_manager',     label: 'General Manager',     bar: 'bg-primary-500', variant: 'primary' },
  { value: 'assistant_manager',   label: 'Asst. Manager',       bar: 'bg-amber-500',   variant: 'amber' },
  { value: 'housekeeping_manager',label: 'Housekeeping Mgr',    bar: 'bg-rose-500',    variant: 'rose' },
  { value: 'employee',            label: 'Employee',            bar: 'bg-emerald-500', variant: 'emerald' },
]

const filters = reactive({ search: '', role: '', is_active: '' })
const showForm   = ref(false)
const showDisable= ref(false)
const editTarget = ref(null)
const disableTarget = ref(null)
const formError  = ref('')
const saving     = ref(false)

const form = reactive({ name: '', username: '', password: '', role: 'employee', department: 'other', position: '' })

function roleCount(role) {
  return store.users.filter(u => u.role === role && u.is_active).length
}

const activeTotal = computed(() => store.users.filter(u => u.is_active).length)

function rolePercent(role) {
  return activeTotal.value > 0 ? Math.round((roleCount(role) / activeTotal.value) * 100) : 0
}

function roleVariant(role) {
  return roles.find(r => r.value === role)?.variant ?? 'slate'
}

function openForm(user = null) {
  editTarget.value = user
  formError.value  = ''
  Object.assign(form, {
    name:       user?.name       ?? '',
    username:   user?.username   ?? '',
    password:   '',
    role:       user?.role       ?? 'employee',
    department: user?.department ?? 'other',
    position:   user?.position   ?? '',
  })
  showForm.value = true
}

async function handleSave() {
  saving.value    = true
  formError.value = ''
  try {
    if (editTarget.value) {
      await store.updateUser(editTarget.value.id, form)
      toast.success('User updated.')
    } else {
      await store.createUser(form)
      toast.success('User created.')
    }
    showForm.value = false
    loadUsers()
  } catch (e) {
    formError.value = e.response?.data?.message
      ?? Object.values(e.response?.data?.errors ?? {})[0]?.[0]
      ?? 'Failed to save user.'
  } finally {
    saving.value = false
  }
}

function confirmDisable(user) {
  disableTarget.value = user
  showDisable.value   = true
}

async function handleDisable() {
  try {
    await store.disableUser(disableTarget.value.id)
    showDisable.value = false
    toast.success('User disabled.')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Failed to disable user.')
    showDisable.value = false
  }
}

async function handleReactivate(user) {
  try {
    await store.reactivateUser(user.id)
    toast.success('User reactivated.')
  } catch {
    toast.error('Failed to reactivate user.')
  }
}

async function loadUsers() {
  const params = {}
  if (filters.search)    params.search    = filters.search
  if (filters.role)      params.role      = filters.role
  if (filters.is_active) params.is_active = filters.is_active
  await store.fetchUsers(params)
}

function clearFilters() {
  Object.assign(filters, { search: '', role: '', is_active: '' })
  loadUsers()
}

onMounted(loadUsers)
</script>
