import { defineStore } from 'pinia'
import { ref } from 'vue'
import { timeLogsApi } from '@/api/timeLogs'

export const useTimeLogsStore = defineStore('timeLogs', () => {
  const logs    = ref([])
  const meta    = ref({})
  const active  = ref(null)
  const summary = ref({ data: [], meta: {} })
  const loading = ref(false)
  const error   = ref(null)

  async function fetchLogs(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res  = await timeLogsApi.list(params)
      logs.value = res.data.data
      meta.value = res.data.meta
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load time logs.'
    } finally {
      loading.value = false
    }
  }

  async function fetchActive() {
    const res = await timeLogsApi.active()
    active.value = res.data.data
    return active.value
  }

  async function clockIn(payload = {}) {
    const res = await timeLogsApi.clockIn(payload)
    active.value = res.data.data
    return res.data.data
  }

  async function clockOut(payload = {}) {
    const res = await timeLogsApi.clockOut(payload)
    active.value = null
    return res.data.data
  }

  async function fetchSummary(params = {}) {
    const res = await timeLogsApi.summary(params)
    summary.value = { data: res.data.data, meta: res.data.meta }
    return summary.value
  }

  async function createLog(data) {
    const res = await timeLogsApi.create(data)
    logs.value.unshift(res.data.data)
    return res.data.data
  }

  async function updateLog(id, data) {
    const res     = await timeLogsApi.update(id, data)
    const updated = res.data.data
    const idx     = logs.value.findIndex(l => l.id === id)
    if (idx >= 0) logs.value.splice(idx, 1, updated)
    return updated
  }

  async function approveLog(id) {
    const res = await timeLogsApi.approve(id)
    const updated = res.data.data
    const idx = logs.value.findIndex(l => l.id === id)
    if (idx >= 0) logs.value.splice(idx, 1, updated)
    return updated
  }

  async function deleteLog(id) {
    await timeLogsApi.delete(id)
    logs.value = logs.value.filter(l => l.id !== id)
  }

  return {
    logs, meta, active, summary, loading, error,
    fetchLogs, fetchActive, clockIn, clockOut, fetchSummary,
    createLog, updateLog, approveLog, deleteLog,
  }
})
