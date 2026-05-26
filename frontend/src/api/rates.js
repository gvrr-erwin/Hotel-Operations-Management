import http from './http'

export const ratesApi = {
  list:       (params) => http.get('/rates', { params }),
  create:     (data)   => http.post('/rates', data),
  update:     (id, data) => http.put(`/rates/${id}`, data),
  delete:     (id)     => http.delete(`/rates/${id}`),
  compare:    (params) => http.get('/rates/compare', { params }),
  historical: (params) => http.get('/rates/historical', { params }),
  hotels:     ()       => http.get('/rates/hotels'),
  roomTypes:  ()       => http.get('/rates/room-types'),
}
