<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Dashboard" :subtitle="`Good ${greeting}, ${auth.user?.name?.split(' ')[0]}`" />

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-16">
      <AppSpinner size="xl" />
    </div>

    <template v-else-if="store.data">
      <!-- KPI Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard label="Avg Rate Today" :value="fmt.currency(store.data.kpis.rates.avg_rate_today)" :change="store.data.kpis.rates.avg_rate_change_pct" color="primary">
          <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21" /></svg></template>
        </KpiCard>
        <KpiCard label="Tips Today" :value="fmt.currency(store.data.kpis.tips.today)" :change="store.data.kpis.tips.change_pct" color="emerald">
          <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></template>
        </KpiCard>
        <KpiCard label="Tips This Month" :value="fmt.currency(store.data.kpis.tips.month)" :sub="`${store.data.kpis.rates.today_count} rate entries today`" color="amber">
          <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" /></svg></template>
        </KpiCard>
        <KpiCard label="Active Staff" :value="store.data.kpis.staff.active" :sub="`${store.data.kpis.staff.clocked_in} clocked in today`" color="violet">
          <template #icon><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg></template>
        </KpiCard>
      </div>

      <!-- Ops KPIs -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <KpiCard label="Open Tasks" :value="store.data.kpis.tasks?.open ?? 0" :sub="`${store.data.kpis.tasks?.in_progress ?? 0} in progress`" color="primary" />
        <KpiCard label="Urgent / Overdue" :value="(store.data.kpis.tasks?.urgent_open ?? 0) + (store.data.kpis.tasks?.overdue ?? 0)" :sub="`${store.data.kpis.tasks?.urgent_open ?? 0} urgent · ${store.data.kpis.tasks?.overdue ?? 0} overdue`" color="amber" />
        <KpiCard label="Shifts Today" :value="store.data.kpis.shifts?.scheduled_today ?? 0" color="emerald" />
        <KpiCard label="Pending Time Logs" :value="store.data.kpis.shifts?.pending_logs ?? 0" color="rose" />
      </div>

      <!-- Charts -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Rate Trend -->
        <div class="card p-5">
          <h3 class="section-title mb-4">Rate Trend (30 days)</h3>
          <LineChart v-if="rateChartData" :data="rateChartData" :options="lineOptions" />
        </div>
        <!-- Tip Trend -->
        <div class="card p-5">
          <h3 class="section-title mb-4">Daily Tips (30 days)</h3>
          <BarChart v-if="tipChartData" :data="tipChartData" :options="barOptions" />
        </div>
      </div>

      <!-- Bottom grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Top earners -->
        <div class="card p-5">
          <h3 class="section-title mb-4">Top Earners</h3>
          <ul class="space-y-3">
            <li v-for="(e, i) in store.data.top_earners" :key="i" class="flex items-center gap-3">
              <div class="w-7 h-7 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 text-xs font-bold shrink-0">
                {{ i + 1 }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-slate-700 truncate">{{ e.employee?.name ?? 'Unknown' }}</p>
                <p class="text-xs text-slate-400">{{ e.count }} tips</p>
              </div>
              <span class="text-sm font-bold text-emerald-600">{{ fmt.currency(e.total) }}</span>
            </li>
            <li v-if="!store.data.top_earners.length" class="text-xs text-slate-400">No data yet.</li>
          </ul>
        </div>

        <!-- Shift Summary -->
        <div class="card p-5">
          <h3 class="section-title mb-4">Today's Shifts</h3>
          <div class="space-y-2">
            <div v-for="shift in shifts" :key="shift.key" class="flex items-center gap-3">
              <span :class="['badge text-xs', shift.color]">{{ shift.label }}</span>
              <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-primary-500 rounded-full" :style="{ width: `${shiftPct(shift.key)}%` }" />
              </div>
              <span class="text-sm font-semibold text-slate-700 w-4 text-right">{{ store.data.shift_summary[shift.key] ?? 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="card p-5 lg:col-span-1">
          <h3 class="section-title mb-4">Recent Activity</h3>
          <ul class="space-y-3">
            <li v-for="act in store.data.recent_activity" :key="act.id" class="flex items-start gap-2.5">
              <span class="w-1.5 h-1.5 rounded-full bg-primary-500 mt-2 shrink-0" />
              <div class="min-w-0">
                <p class="text-xs text-slate-600 leading-snug">{{ act.description }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ fmt.relative(act.created_at) }}</p>
              </div>
            </li>
            <li v-if="!store.data.recent_activity.length" class="text-xs text-slate-400">No activity yet.</li>
          </ul>
        </div>
      </div>

      <!-- Operations row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Open tasks -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="section-title">Priority Tasks</h3>
            <RouterLink to="/tasks" class="text-xs font-semibold text-primary-600 hover:text-primary-700">View board →</RouterLink>
          </div>
          <ul v-if="store.data.open_tasks?.length" class="space-y-2">
            <li v-for="t in store.data.open_tasks" :key="t.id" class="flex items-start gap-3 py-2 border-b border-slate-50 last:border-b-0">
              <span :class="['mt-1 w-2 h-2 rounded-full shrink-0', priorityDot(t.priority)]" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 truncate">{{ t.title }}</p>
                <p class="text-[11px] text-slate-400">
                  <span class="capitalize">{{ t.category.replace('_',' ') }}</span>
                  <template v-if="t.room_number"> · Room {{ t.room_number }}</template>
                  <template v-if="t.assignee"> · {{ t.assignee.name }}</template>
                </p>
              </div>
              <span v-if="t.is_overdue" class="badge bg-red-100 text-red-700 text-[10px]">Overdue</span>
              <span v-else class="text-[10px] text-slate-400 self-center">{{ t.due_at ? fmt.relative(t.due_at) : '' }}</span>
            </li>
          </ul>
          <p v-else class="text-xs text-slate-400">All clear — no open tasks.</p>
        </div>

        <!-- Today's shifts -->
        <div class="card p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="section-title">Today's Roster</h3>
            <RouterLink to="/shifts" class="text-xs font-semibold text-primary-600 hover:text-primary-700">View schedule →</RouterLink>
          </div>
          <ul v-if="store.data.today_shifts?.length" class="space-y-2">
            <li v-for="s in store.data.today_shifts" :key="s.id" class="flex items-center gap-3 py-2 border-b border-slate-50 last:border-b-0">
              <div class="w-7 h-7 rounded-full bg-primary-100 text-primary-700 text-[10px] font-bold flex items-center justify-center shrink-0">
                {{ fmt.initials(s.user?.name ?? '') }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-800 truncate">{{ s.user?.name }}</p>
                <p class="text-[11px] text-slate-400 capitalize">{{ s.shift_type }} shift</p>
              </div>
              <span class="font-mono text-[11px] text-slate-500">{{ fmt.time(s.start_time) }} – {{ fmt.time(s.end_time) }}</span>
            </li>
          </ul>
          <p v-else class="text-xs text-slate-400">No shifts scheduled for today.</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Line as LineChart, Bar as BarChart } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement,
  BarElement, Title, Tooltip, Legend, Filler,
} from 'chart.js'
import { useDashboardStore } from '@/stores/dashboard'
import { useAuthStore } from '@/stores/auth'
import KpiCard from '@/components/shared/KpiCard.vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import AppSpinner from '@/components/ui/AppSpinner.vue'
import { fmt } from '@/utils/formatters'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler)

const store = useDashboardStore()
const auth  = useAuthStore()

const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'morning'
  if (h < 18) return 'afternoon'
  return 'evening'
})

const shifts = [
  { key: 'morning',   label: 'Morning',   color: 'bg-amber-100 text-amber-700' },
  { key: 'afternoon', label: 'Afternoon', color: 'bg-sky-100 text-sky-700' },
  { key: 'evening',   label: 'Evening',   color: 'bg-indigo-100 text-indigo-700' },
  { key: 'night',     label: 'Night',     color: 'bg-slate-100 text-slate-600' },
]

const priorityDotMap = { low: 'bg-slate-400', medium: 'bg-sky-500', high: 'bg-amber-500', urgent: 'bg-red-500' }
function priorityDot(p) { return priorityDotMap[p] ?? 'bg-slate-400' }

function shiftPct(key) {
  const sum = Object.values(store.data?.shift_summary ?? {}).reduce((a, b) => a + b, 0)
  return sum > 0 ? Math.round(((store.data.shift_summary[key] ?? 0) / sum) * 100) : 0
}

const lineOptions = {
  responsive: true, maintainAspectRatio: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { maxTicksLimit: 6, color: '#94a3b8', font: { size: 11 } } },
    y: { grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 }, callback: v => `$${v}` } },
  },
}

const barOptions = {
  responsive: true, maintainAspectRatio: true,
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { maxTicksLimit: 6, color: '#94a3b8', font: { size: 11 } } },
    y: { grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 }, callback: v => `$${v}` } },
  },
}

const rateChartData = computed(() => {
  const trend = store.data?.rate_trend ?? []
  return {
    labels: trend.map(r => r.date?.slice(5)),
    datasets: [{
      data:             trend.map(r => r.avg_rate),
      borderColor:      '#4f46e5',
      backgroundColor:  'rgba(79,70,229,0.08)',
      borderWidth:      2,
      fill:             true,
      pointRadius:      0,
      tension:          0.4,
    }],
  }
})

const tipChartData = computed(() => {
  const trend = store.data?.tip_trend ?? []
  return {
    labels: trend.map(r => r.date?.slice(5)),
    datasets: [{
      data:            trend.map(r => r.total),
      backgroundColor: '#10b981',
      borderRadius:    6,
    }],
  }
})

onMounted(() => store.fetch())
</script>
