import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/auth'
import router from '@/router'

const MANAGEMENT = ['admin', 'general_manager', 'assistant_manager']

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const token = ref(localStorage.getItem('hom_token'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const role            = computed(() => user.value?.role ?? null)
  const isAdmin         = computed(() => role.value === 'admin')
  const isGM            = computed(() => role.value === 'general_manager')
  const isManagement    = computed(() => MANAGEMENT.includes(role.value))

  function hasRole(...roles) {
    return roles.includes(role.value)
  }

  // Page access (what shows in the sidebar / router-guard)
  const ACCESS = {
    dashboard:  ['admin', 'general_manager', 'assistant_manager'],
    rates:      ['admin', 'general_manager', 'assistant_manager'],
    tips:       ['admin', 'general_manager', 'assistant_manager', 'housekeeping_manager', 'employee'],
    time_clock: ['admin', 'general_manager', 'assistant_manager', 'housekeeping_manager', 'employee'],
    time_logs:  ['admin', 'general_manager', 'assistant_manager', 'housekeeping_manager', 'employee'],
    shifts:     ['admin', 'general_manager', 'assistant_manager', 'housekeeping_manager', 'employee'],
    tasks:      ['admin', 'general_manager', 'assistant_manager', 'housekeeping_manager', 'employee'],
    users:      ['admin'],
    audit:      ['admin'],
  }

  // Create / edit / delete permissions
  const EDIT = {
    rates:     ['admin', 'general_manager'],
    tips:      ['admin', 'general_manager', 'assistant_manager'],
    time_logs: ['admin', 'general_manager', 'assistant_manager'],
    shifts:    ['admin', 'general_manager', 'assistant_manager'],
    tasks:     ['admin', 'general_manager', 'assistant_manager', 'housekeeping_manager', 'employee'],
    users:     ['admin'],
  }

  function canAccess(module) {
    return ACCESS[module]?.includes(role.value) ?? false
  }

  function canEdit(module) {
    return EDIT[module]?.includes(role.value) ?? false
  }

  function defaultHome() {
    switch (role.value) {
      case 'admin':                return '/dashboard'
      case 'general_manager':      return '/dashboard'
      case 'assistant_manager':    return '/dashboard'
      case 'housekeeping_manager': return '/tasks'
      case 'employee':             return '/time-clock'
      default:                     return '/login'
    }
  }

  async function login(credentials) {
    loading.value = true
    try {
      const res = await authApi.login(credentials)
      token.value = res.data.token
      user.value  = res.data.user
      localStorage.setItem('hom_token', token.value)
      return { ok: true }
    } catch (err) {
      return { ok: false, message: err.response?.data?.message ?? 'Login failed.' }
    } finally {
      loading.value = false
    }
  }

  async function fetchMe() {
    if (!token.value) return
    try {
      const res = await authApi.me()
      user.value = res.data.user
    } catch {
      clearSession()
    }
  }

  async function logout() {
    try { await authApi.logout() } catch {}
    clearSession()
    router.push('/login')
  }

  function clearSession() {
    token.value = null
    user.value  = null
    localStorage.removeItem('hom_token')
  }

  return {
    user, token, loading,
    isAuthenticated, role, isAdmin, isGM, isManagement,
    hasRole, canAccess, canEdit, defaultHome,
    login, logout, fetchMe, clearSession,
  }
})
