import http from './http'

export const tasksApi = {
  list:         (params)         => http.get('/tasks', { params }),
  create:       (data)           => http.post('/tasks', data),
  update:       (id, data)       => http.put(`/tasks/${id}`, data),
  updateStatus: (id, status)     => http.patch(`/tasks/${id}/status`, { status }),
  delete:       (id)             => http.delete(`/tasks/${id}`),
}
