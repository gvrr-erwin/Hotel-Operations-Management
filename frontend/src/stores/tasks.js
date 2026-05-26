import { defineStore } from 'pinia'
import { ref } from 'vue'
import { tasksApi } from '@/api/tasks'

export const useTasksStore = defineStore('tasks', () => {
  const tasks   = ref([])
  const counts  = ref({ open: 0, in_progress: 0, completed: 0, overdue: 0 })
  const loading = ref(false)
  const error   = ref(null)

  async function fetchTasks(params = {}) {
    loading.value = true
    error.value   = null
    try {
      const res = await tasksApi.list(params)
      tasks.value  = res.data.data
      counts.value = res.data.meta?.counts ?? counts.value
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Failed to load tasks.'
    } finally {
      loading.value = false
    }
  }

  async function createTask(data) {
    const res = await tasksApi.create(data)
    tasks.value.unshift(res.data.data)
    return res.data.data
  }

  async function updateTask(id, data) {
    const res = await tasksApi.update(id, data)
    const idx = tasks.value.findIndex(t => t.id === id)
    if (idx >= 0) tasks.value.splice(idx, 1, res.data.data)
    return res.data.data
  }

  async function setStatus(id, status) {
    const res = await tasksApi.updateStatus(id, status)
    const idx = tasks.value.findIndex(t => t.id === id)
    if (idx >= 0) tasks.value.splice(idx, 1, res.data.data)
    return res.data.data
  }

  async function deleteTask(id) {
    await tasksApi.delete(id)
    tasks.value = tasks.value.filter(t => t.id !== id)
  }

  return { tasks, counts, loading, error, fetchTasks, createTask, updateTask, setStatus, deleteTask }
})
