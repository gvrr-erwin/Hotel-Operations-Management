import { ref } from 'vue'

const toasts = ref([])
let id = 0

export function useToast() {
  function add(message, type = 'success', duration = 4000) {
    const toast = { id: ++id, message, type }
    toasts.value.push(toast)
    setTimeout(() => remove(toast.id), duration)
  }

  function remove(toastId) {
    const idx = toasts.value.findIndex(t => t.id === toastId)
    if (idx >= 0) toasts.value.splice(idx, 1)
  }

  return {
    toasts,
    success: (msg) => add(msg, 'success'),
    error:   (msg) => add(msg, 'error'),
    info:    (msg) => add(msg, 'info'),
    warning: (msg) => add(msg, 'warning'),
    remove,
  }
}
