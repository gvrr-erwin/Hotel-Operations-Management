import http from './http'

export const tipsApi = {
  list:      (params) => http.get('/tips', { params }),
  create:    (data)   => http.post('/tips', data),
  update:    (id, data) => http.put(`/tips/${id}`, data),
  delete:    (id)     => http.delete(`/tips/${id}`),
  analytics: (params) => http.get('/tips/analytics', { params }),
}
