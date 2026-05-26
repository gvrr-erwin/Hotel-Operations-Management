import { defineStore } from 'pinia'
import { ref } from 'vue'
import { shiftsApi } from '@/api/shifts'

export const useShiftsStore = defineStore('shifts', () => {
  const shifts  = ref([])
  const loading = ref(false)
  const error   = ref(null)

  async function fetchShifts(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res = await shiftsApi.list(params)
      shifts.value = res.data.data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load shifts.'
    } finally {
      loading.value = false
    }
  }

  async function createShift(data) {
    const res = await shiftsApi.create(data)
    shifts.value.push(res.data.data)
    return res.data.data
  }

  async function updateShift(id, data) {
    const res = await shiftsApi.update(id, data)
    const updated = res.data.data
    const idx = shifts.value.findIndex(s => s.id === id)
    if (idx >= 0) shifts.value.splice(idx, 1, updated)
    return updated
  }

  async function deleteShift(id) {
    await shiftsApi.delete(id)
    shifts.value = shifts.value.filter(s => s.id !== id)
  }

  return { shifts, loading, error, fetchShifts, createShift, updateShift, deleteShift }
})
