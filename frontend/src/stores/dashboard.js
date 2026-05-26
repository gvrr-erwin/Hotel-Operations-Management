import { defineStore } from 'pinia'
import { ref } from 'vue'
import { dashboardApi } from '@/api/dashboard'

export const useDashboardStore = defineStore('dashboard', () => {
  const data    = ref(null)
  const loading = ref(false)
  const error   = ref(null)

  async function fetch() {
    loading.value = true
    error.value   = null
    try {
      const res = await dashboardApi.get()
      data.value = res.data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load dashboard.'
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, fetch }
})
