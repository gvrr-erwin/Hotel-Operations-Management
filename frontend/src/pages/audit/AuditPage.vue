<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Audit Log" subtitle="Track all system actions and changes." />

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
      <div>
        <label class="label">Action</label>
        <select v-model="filters.action" class="input">
          <option value="">All Actions</option>
          <option v-for="a in knownActions" :key="a.value" :value="a.value">{{ a.label }}</option>
        </select>
      </div>
      <div class="flex items-end gap-2">
        <button @click="loadLogs" class="btn btn-primary">Filter</button>
        <button @click="clearFilters" class="btn btn-secondary">Clear</button>
      </div>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <span class="section-title">Activity History</span>
        <span class="text-xs text-slate-400">{{ store.meta.total ?? 0 }} events</span>
      </div>
      <div v-if="store.loading" class="flex justify-center py-12"><AppSpinner size="lg" /></div>
      <EmptyState v-else-if="!store.logs.length" title="No audit logs found" description="System activity will appear here." />
      <div v-else class="table-wrapper">
        <table class="table">
          <thead><tr>
            <th class="th">Time</th>
            <th class="th">User</th>
            <th class="th">Action</th>
            <th class="th">Description</th>
            <th class="th">IP</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="log in store.logs" :key="log.id" class="tr-hover">
              <td class="td text-xs text-slate-400 whitespace-nowrap">{{ fmt.dateTime(log.created_at) }}</td>
              <td class="td">
                <div v-if="log.user" class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 text-[10px] font-bold shrink-0">
                    {{ fmt.initials(log.user.name) }}
                  </div>
                  <span class="text-sm font-medium text-slate-700">{{ log.user.name }}</span>
                </div>
                <span v-else class="text-slate-300 text-xs">System</span>
              </td>
              <td class="td"><AppBadge :variant="actionVariant(log.action)">{{ formatAction(log.action) }}</AppBadge></td>
              <td class="td text-sm text-slate-600 max-w-sm">{{ log.description }}</td>
              <td class="td font-mono text-xs text-slate-400">{{ log.ip_address ?? '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, onMounted } from 'vue'
import { useAuditStore } from '@/stores/audit'
import { fmt } from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const store   = useAuditStore()
const filters = reactive({ date_from: '', date_to: '', action: '' })

const knownActions = [
  { value: 'login',              label: 'Login' },
  { value: 'logout',             label: 'Logout' },
  { value: 'rate_saved',         label: 'Rate Saved' },
  { value: 'rate_updated',       label: 'Rate Updated' },
  { value: 'rate_deleted',       label: 'Rate Deleted' },
  { value: 'tip_created',        label: 'Tip Created' },
  { value: 'tip_updated',        label: 'Tip Updated' },
  { value: 'tip_deleted',        label: 'Tip Deleted' },
  { value: 'time_log_created',   label: 'Time Log Created' },
  { value: 'time_log_updated',   label: 'Time Log Updated' },
  { value: 'time_log_deleted',   label: 'Time Log Deleted' },
  { value: 'user_created',       label: 'User Created' },
  { value: 'user_updated',       label: 'User Updated' },
  { value: 'user_disabled',      label: 'User Disabled' },
  { value: 'user_reactivated',   label: 'User Reactivated' },
]

function formatAction(action) {
  return knownActions.find(a => a.value === action)?.label ?? action.replace(/_/g, ' ')
}

function actionVariant(action) {
  if (['login', 'logout'].includes(action)) return 'primary'
  if (action.includes('created') || action.includes('saved')) return 'emerald'
  if (action.includes('updated')) return 'amber'
  if (action.includes('deleted') || action.includes('disabled')) return 'red'
  return 'slate'
}

async function loadLogs() {
  const params = {}
  if (filters.date_from) params.date_from = filters.date_from
  if (filters.date_to)   params.date_to   = filters.date_to
  if (filters.action)    params.action    = filters.action
  await store.fetchLogs(params)
}

function clearFilters() {
  Object.assign(filters, { date_from: '', date_to: '', action: '' })
  loadLogs()
}

onMounted(loadLogs)
</script>
