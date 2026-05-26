<template>
  <!-- Mobile overlay -->
  <Transition name="fade">
    <div v-if="open" class="fixed inset-0 z-20 bg-slate-900/50 lg:hidden" @click="$emit('close')" />
  </Transition>

  <!-- Sidebar -->
  <aside :class="[
    'fixed inset-y-0 left-0 z-30 flex flex-col w-64 bg-slate-900 transition-transform duration-300',
    open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
  ]">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 h-16 border-b border-slate-800">
      <div class="w-8 h-8 rounded-lg bg-primary-600 flex items-center justify-center">
        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
        </svg>
      </div>
      <div class="leading-tight">
        <span class="text-sm font-bold text-white">Hotel Ops</span>
        <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wide">Management</p>
      </div>
    </div>

    <!-- Nav -->
    <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">
      <template v-for="item in visibleNav" :key="item.path">
        <RouterLink
          :to="item.path"
          @click="$emit('close')"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors"
          :class="isActive(item.path)
            ? 'bg-primary-600 text-white'
            : 'text-slate-400 hover:text-white hover:bg-slate-800'"
        >
          <component :is="item.icon" class="w-4 h-4 shrink-0" />
          {{ item.label }}
        </RouterLink>
      </template>
    </nav>

    <!-- User info -->
    <div class="px-4 py-4 border-t border-slate-800">
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs font-bold shrink-0">
          {{ initials }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-white truncate">{{ user?.name }}</p>
          <p class="text-xs text-slate-400 truncate">{{ ROLE_LABELS[user?.role] }}</p>
        </div>
        <button @click="handleLogout" class="text-slate-400 hover:text-white transition-colors" title="Logout">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
          </svg>
        </button>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, defineComponent, h } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { ROLE_LABELS, fmt } from '@/utils/formatters'

defineProps({ open: { type: Boolean, default: false } })
defineEmits(['close'])

const auth  = useAuthStore()
const route = useRoute()
const user  = computed(() => auth.user)

const initials = computed(() => fmt.initials(user.value?.name ?? ''))

function handleLogout() {
  auth.logout()
}

function isActive(path) {
  return route.path.startsWith(path)
}

// ── Icon helpers ───────────────────────────────────────────────────────────
function icon(d) {
  return defineComponent({
    render: () => h('svg', { class: 'w-4 h-4', fill: 'none', viewBox: '0 0 24 24', stroke: 'currentColor', 'stroke-width': '2' },
      [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d })]
    )
  })
}

const NAV = [
  {
    path:   '/dashboard',
    label:  'Dashboard',
    module: 'dashboard',
    icon:   icon('M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'),
  },
  {
    path:   '/rates',
    label:  'Hotel Rates',
    module: 'rates',
    icon:   icon('M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z'),
  },
  {
    path:   '/tips',
    label:  'Tip Tracker',
    module: 'tips',
    icon:   icon('M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
  },
  {
    path:   '/time-clock',
    label:  'Time Clock',
    module: 'time_clock',
    icon:   icon('M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z'),
  },
  {
    path:   '/time-logs',
    label:  'Time Logs',
    module: 'time_logs',
    icon:   icon('M9 17v-2a4 4 0 014-4h6m0 0l-3-3m3 3l-3 3M5 21h6a2 2 0 002-2v-2'),
  },
  {
    path:   '/shifts',
    label:  'Shift Schedule',
    module: 'shifts',
    icon:   icon('M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'),
  },
  {
    path:   '/tasks',
    label:  'Task Board',
    module: 'tasks',
    icon:   icon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'),
  },
  {
    path:   '/users',
    label:  'User Management',
    module: 'users',
    icon:   icon('M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'),
  },
  {
    path:   '/audit',
    label:  'Audit Log',
    module: 'audit',
    icon:   icon('M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z'),
  },
]

const visibleNav = computed(() => NAV.filter(item => auth.canAccess(item.module)))
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s }
.fade-enter-from, .fade-leave-to { opacity: 0 }
</style>
