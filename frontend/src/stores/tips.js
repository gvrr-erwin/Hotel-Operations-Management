import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tipsApi } from '@/api/tips'

export const useTipsStore = defineStore('tips', () => {
  const tips    = ref([])
  const meta    = ref({})
  const loading = ref(false)
  const error   = ref(null)

  async function fetchTips(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res  = await tipsApi.list(params)
      tips.value = res.data.data
      meta.value = res.data.meta
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load tips.'
    } finally {
      loading.value = false
    }
  }

  async function createTip(data) {
    const res = await tipsApi.create(data)
    tips.value.unshift(res.data.data)
    return res.data.data
  }

  async function updateTip(id, data) {
    const res     = await tipsApi.update(id, data)
    const updated = res.data.data
    const idx     = tips.value.findIndex(t => t.id === id)
    if (idx >= 0) tips.value.splice(idx, 1, updated)
    return updated
  }

  async function deleteTip(id) {
    await tipsApi.delete(id)
    tips.value = tips.value.filter(t => t.id !== id)
  }

  async function getAnalytics(params) {
    const res = await tipsApi.analytics(params)
    return res.data
  }

  return { tips, meta, loading, error, fetchTips, createTip, updateTip, deleteTip, getAnalytics }
})
