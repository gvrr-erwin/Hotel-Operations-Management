import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ratesApi } from '@/api/rates'

export const useRatesStore = defineStore('rates', () => {
  const rates     = ref([])
  const hotels    = ref([])
  const roomTypes = ref([])
  const meta      = ref({})
  const loading   = ref(false)
  const error     = ref(null)

  async function fetchRates(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res   = await ratesApi.list(params)
      rates.value = res.data.data
      meta.value  = res.data.meta
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load rates.'
    } finally {
      loading.value = false
    }
  }

  async function fetchHotels() {
    const res   = await ratesApi.hotels()
    hotels.value = res.data.data
  }

  async function fetchRoomTypes() {
    const res      = await ratesApi.roomTypes()
    roomTypes.value = res.data.data
  }

  async function saveRate(data) {
    const res = await ratesApi.create(data)
    return res.data.data
  }

  async function updateRate(id, data) {
    const res = await ratesApi.update(id, data)
    return res.data.data
  }

  async function deleteRate(id) {
    await ratesApi.delete(id)
    rates.value = rates.value.filter(r => r.id !== id)
  }

  async function compare(params) {
    const res = await ratesApi.compare(params)
    return res.data
  }

  async function historical(params) {
    const res = await ratesApi.historical(params)
    return res.data.data
  }

  return {
    rates, hotels, roomTypes, meta, loading, error,
    fetchRates, fetchHotels, fetchRoomTypes,
    saveRate, updateRate, deleteRate,
    compare, historical,
  }
})
