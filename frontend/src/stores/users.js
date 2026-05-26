import { defineStore } from 'pinia'
import { ref } from 'vue'
import { usersApi } from '@/api/users'

export const useUsersStore = defineStore('users', () => {
  const users     = ref([])
  const employees = ref([])
  const meta      = ref({})
  const loading   = ref(false)
  const error     = ref(null)

  async function fetchUsers(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res   = await usersApi.list(params)
      users.value = res.data.data
      meta.value  = res.data.meta
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load users.'
    } finally {
      loading.value = false
    }
  }

  async function fetchEmployees() {
    const res       = await usersApi.employees()
    employees.value = res.data.data
  }

  async function createUser(data) {
    const res = await usersApi.create(data)
    users.value.push(res.data.data)
    return res.data.data
  }

  async function updateUser(id, data) {
    const res     = await usersApi.update(id, data)
    const updated = res.data.data
    const idx     = users.value.findIndex(u => u.id === id)
    if (idx >= 0) users.value.splice(idx, 1, updated)
    return updated
  }

  async function disableUser(id) {
    await usersApi.delete(id)
    const idx = users.value.findIndex(u => u.id === id)
    if (idx >= 0) users.value[idx].is_active = false
  }

  async function reactivateUser(id) {
    const res     = await usersApi.reactivate(id)
    const updated = res.data.data
    const idx     = users.value.findIndex(u => u.id === id)
    if (idx >= 0) users.value.splice(idx, 1, updated)
    return updated
  }

  return {
    users, employees, meta, loading, error,
    fetchUsers, fetchEmployees,
    createUser, updateUser, disableUser, reactivateUser,
  }
})
