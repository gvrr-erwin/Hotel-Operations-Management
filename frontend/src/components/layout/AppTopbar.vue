<template>
  <header class="sticky top-0 z-10 flex items-center h-16 px-4 sm:px-6 bg-white/80 backdrop-blur border-b border-slate-100 gap-4">
    <!-- Hamburger -->
    <button
      @click="$emit('toggle-sidebar')"
      class="lg:hidden flex items-center justify-center w-9 h-9 rounded-xl text-slate-500 hover:bg-slate-100 transition-colors"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
      </svg>
    </button>

    <!-- Page title (from slot or route) -->
    <div class="flex-1 min-w-0">
      <h2 class="text-sm font-semibold text-slate-700 truncate">{{ pageTitle }}</h2>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-3">
      <!-- Date -->
      <span class="hidden sm:block text-xs text-slate-400 font-medium">{{ today }}</span>

      <!-- Avatar -->
      <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white text-xs font-bold">
        {{ initials }}
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { fmt } from '@/utils/formatters'
import dayjs from 'dayjs'

defineEmits(['toggle-sidebar'])

const auth    = useAuthStore()
const route   = useRoute()
const initials = computed(() => fmt.initials(auth.user?.name ?? ''))
const today    = computed(() => dayjs().format('dddd, MMMM D'))

const TITLES = {
  '/dashboard': 'Dashboard',
  '/rates':     'Hotel Rate Management',
  '/tips':      'Tip Tracker',
  '/time-logs': 'Time Logging',
  '/users':     'User Management',
  '/audit':     'Audit Log',
}

const pageTitle = computed(() => TITLES[route.path] ?? 'Hotel Operations')
</script>
