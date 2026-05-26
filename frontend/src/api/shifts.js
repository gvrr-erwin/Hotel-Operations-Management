import http from './http'

export const shiftsApi = {
  list:   (params)   => http.get('/shifts', { params }),
  create: (data)     => http.post('/shifts', data),
  update: (id, data) => http.put(`/shifts/${id}`, data),
  delete: (id)       => http.delete(`/shifts/${id}`),
}
