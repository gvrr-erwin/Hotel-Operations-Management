import http from './http'

export const usersApi = {
  list:       (params)   => http.get('/users', { params }),
  create:     (data)     => http.post('/users', data),
  get:        (id)       => http.get(`/users/${id}`),
  update:     (id, data) => http.put(`/users/${id}`, data),
  delete:     (id)       => http.delete(`/users/${id}`),
  reactivate: (id)       => http.patch(`/users/${id}/reactivate`),
  employees:  ()         => http.get('/employees'),
}
