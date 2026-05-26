<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Time Clock" subtitle="Clock in when you start your shift, clock out when you're done." />

    <!-- Clock card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 card p-8 flex flex-col items-center text-center bg-gradient-to-br from-primary-600 to-primary-700 text-white">
        <p class="text-xs uppercase tracking-widest text-primary-100 font-semibold">Current Time</p>
        <p class="text-5xl font-bold font-mono tracking-tight mt-2">{{ clockTime }}</p>
        <p class="text-sm text-primary-100 mt-1">{{ clockDate }}</p>

        <div v-if="store.active" class="mt-6 flex flex-col items-center">
          <span class="badge bg-emerald-400/20 text-emerald-100 mb-3">● Clocked in</span>
          <p class="text-sm text-primary-100">
            Since <span class="font-mono font-semibold text-white">{{ fmt.time(store.active.clock_in) }}</span>
            · <span class="capitalize">{{ store.active.shift_type }}</span> shift
          </p>
          <p class="text-xs text-primary-200 mt-1">Working {{ liveDuration }}</p>

          <button @click="handleClockOut" :disabled="busy" class="mt-5 px-8 py-3 rounded-xl bg-white text-primary-700 font-semibold text-sm shadow hover:bg-slate-50 transition disabled:opacity-60">
            <span v-if="busy">…</span><span v-else>Clock Out</span>
          </button>
        </div>

        <div v-else class="mt-6 flex flex-col items-center">
          <span class="badge bg-white/10 text-primary-100 mb-3">○ Not clocked in</span>
          <button @click="handleClockIn" :disabled="busy" class="px-8 py-3 rounded-xl bg-white text-primary-700 font-semibold text-sm shadow hover:bg-slate-50 transition disabled:opacity-60">
            <span v-if="busy">…</span><span v-else>Clock In</span>
          </button>
          <p class="text-xs text-primary-200 mt-3">Your shift will be detected from the current time.</p>
        </div>
      </div>

      <!-- Today summary -->
      <div class="card p-5">
        <h3 class="section-title mb-4">This Week</h3>
        <div v-if="loadingSummary" class="flex justify-center py-6"><AppSpinner /></div>
        <div v-else class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-slate-500">Hours worked</span>
            <span class="font-semibold text-slate-800">{{ weekHours }}h</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-slate-500">Sessions</span>
            <span class="font-semibold text-slate-800">{{ weekSessions }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-slate-500">Pending approval</span>
            <span class="font-semibold text-amber-600">{{ weekPending }}</span>
          </div>
          <div class="border-t border-slate-100 pt-3">
            <p class="text-xs text-slate-400">Average per session</p>
            <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ avgHours }}h</p>
          </div>
        </div>
      </div>
    </div>

    <!-- My recent logs -->
    <div class="card p-0 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <span class="section-title">My Recent Time Logs</span>
        <RouterLink to="/time-logs" class="text-xs font-semibold text-primary-600 hover:text-primary-700">View all →</RouterLink>
      </div>
      <div v-if="logsStore.loading" class="flex justify-center py-10"><AppSpinner /></div>
      <EmptyState v-else-if="!logsStore.logs.length" title="No logs yet" description="Your time logs will appear here after you clock in." />
      <div v-else class="table-wrapper">
        <table class="table">
          <thead><tr>
            <th class="th">Date</th>
            <th class="th">Shift</th>
            <th class="th">In</th>
            <th class="th">Out</th>
            <th class="th">Hours</th>
            <th class="th">Status</th>
          </tr></thead>
          <tbody class="divide-y divide-slate-50">
            <tr v-for="log in logsStore.logs.slice(0, 8)" :key="log.id" class="tr-hover">
              <td class="td font-mono text-xs text-slate-500">{{ log.date }}</td>
              <td class="td"><AppBadge :variant="shiftVariant(log.shift_type)">{{ SHIFT_LABELS[log.shift_type] }}</AppBadge></td>
              <td class="td font-mono text-xs">{{ fmt.time(log.clock_in) }}</td>
              <td class="td font-mono text-xs">{{ log.clock_out ? fmt.time(log.clock_out) : '—' }}</td>
              <td class="td font-semibold">{{ log.hours_worked ? `${log.hours_worked}h` : '—' }}</td>
              <td class="td"><AppBadge :variant="statusVariant(log.status)">{{ STATUS_LABELS[log.status] }}</AppBadge></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { RouterLink } from 'vue-router'
import dayjs from 'dayjs'
import { useTimeLogsStore } from '@/stores/timeLogs'
import { useToast } from '@/composables/useToast'
import { fmt, SHIFT_LABELS, STATUS_LABELS } from '@/utils/formatters'
import PageHeader from '@/components/shared/PageHeader.vue'
import EmptyState from '@/components/shared/EmptyState.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import AppBadge from '@/components/ui/AppBadge.vue'

const store     = useTimeLogsStore()
const logsStore = useTimeLogsStore() // same store; alias for clarity
const toast     = useToast()
const busy      = ref(false)
const now       = ref(new Date())
const loadingSummary = ref(false)

let timer = null

const clockTime = computed(() => dayjs(now.value).format('h:mm:ss A'))
const clockDate = computed(() => dayjs(now.value).format('dddd, MMMM D, YYYY'))

const liveDuration = computed(() => {
  if (!store.active) return ''
  const [h, m] = store.active.clock_in.split(':')
  const start = dayjs(store.active.date).hour(+h).minute(+m).second(0)
  const diffMin = Math.max(0, dayjs(now.value).diff(start, 'minute'))
  return `${Math.floor(diffMin / 60)}h ${diffMin % 60}m`
})

const shiftVariantMap = { morning: 'amber', afternoon: 'sky', evening: 'indigo', night: 'slate' }
const statusVariantMap = { pending: 'amber', approved: 'emerald', flagged: 'red' }
function shiftVariant(s) { return shiftVariantMap[s] ?? 'slate' }
function statusVariant(s) { return statusVariantMap[s] ?? 'slate' }

const weekHours = computed(() =>
  logsStore.logs
    .filter(l => dayjs(l.date).isAfter(dayjs().subtract(7, 'day')))
    .reduce((acc, l) => acc + (l.hours_worked ?? 0), 0)
    .toFixed(1)
)
const weekSessions = computed(() =>
  logsStore.logs.filter(l => dayjs(l.date).isAfter(dayjs().subtract(7, 'day'))).length
)
const weekPending = computed(() =>
  logsStore.logs.filter(l => l.status === 'pending').length
)
const avgHours = computed(() => {
  const n = weekSessions.value
  return n ? (weekHours.value / n).toFixed(1) : '0.0'
})

async function handleClockIn() {
  busy.value = true
  try {
    await store.clockIn()
    toast.success('Clocked in. Have a great shift!')
    await store.fetchLogs()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Could not clock in.')
  } finally { busy.value = false }
}

async function handleClockOut() {
  busy.value = true
  try {
    const res = await store.clockOut()
    toast.success(`Clocked out. ${res.hours_worked ?? 0}h logged.`)
    await store.fetchLogs()
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Could not clock out.')
  } finally { busy.value = false }
}

onMounted(async () => {
  loadingSummary.value = true
  await Promise.all([store.fetchActive(), store.fetchLogs()])
  loadingSummary.value = false
  timer = setInterval(() => { now.value = new Date() }, 1000)
})

onBeforeUnmount(() => { if (timer) clearInterval(timer) })
</script>
