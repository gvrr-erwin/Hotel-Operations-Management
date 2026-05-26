import { defineStore } from 'pinia'
import { ref } from 'vue'
import { auditLogsApi } from '@/api/auditLogs'

export const useAuditStore = defineStore('audit', () => {
  const logs    = ref([])
  const meta    = ref({})
  const loading = ref(false)
  const error   = ref(null)

  async function fetchLogs(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res  = await auditLogsApi.list(params)
      logs.value = res.data.data
      meta.value = res.data.meta
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load audit logs.'
    } finally {
      loading.value = false
    }
  }

  return { logs, meta, loading, error, fetchLogs }
})
