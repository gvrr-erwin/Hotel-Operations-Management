import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    component: () => import('@/layouts/AuthLayout.vue'),
    children: [{ path: '', name: 'login', component: () => import('@/pages/auth/LoginPage.vue') }],
    meta: { public: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/DashboardLayout.vue'),
    children: [
      { path: 'dashboard',  name: 'dashboard',  component: () => import('@/pages/dashboard/DashboardPage.vue'),    meta: { module: 'dashboard' } },
      { path: 'rates',      name: 'rates',      component: () => import('@/pages/rates/RatesPage.vue'),            meta: { module: 'rates' } },
      { path: 'tips',       name: 'tips',       component: () => import('@/pages/tips/TipsPage.vue'),              meta: { module: 'tips' } },
      { path: 'time-clock', name: 'time-clock', component: () => import('@/pages/time-logs/TimeClockPage.vue'),    meta: { module: 'time_clock' } },
      { path: 'time-logs',  name: 'time-logs',  component: () => import('@/pages/time-logs/TimeLogsPage.vue'),     meta: { module: 'time_logs' } },
      { path: 'shifts',     name: 'shifts',     component: () => import('@/pages/shifts/ShiftsPage.vue'),          meta: { module: 'shifts' } },
      { path: 'tasks',      name: 'tasks',      component: () => import('@/pages/tasks/TasksPage.vue'),            meta: { module: 'tasks' } },
      { path: 'users',      name: 'users',      component: () => import('@/pages/users/UsersPage.vue'),            meta: { module: 'users' } },
      { path: 'audit',      name: 'audit',      component: () => import('@/pages/audit/AuditPage.vue'),            meta: { module: 'audit' } },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) {
    await auth.fetchMe()
  }

  const isPublic = to.meta.public
  if (isPublic && auth.isAuthenticated) return auth.defaultHome()
  if (!isPublic && !auth.isAuthenticated) return '/login'
  if (to.path === '/') return auth.isAuthenticated ? auth.defaultHome() : '/login'
  if (to.meta.module && !auth.canAccess(to.meta.module)) return auth.defaultHome()
})

export default router
