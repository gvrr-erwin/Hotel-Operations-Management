import http from './http'

export const timeLogsApi = {
  list:      (params)   => http.get('/time-logs', { params }),
  active:    ()         => http.get('/time-logs/active'),
  clockIn:   (data)     => http.post('/time-logs/clock-in', data ?? {}),
  clockOut:  (data)     => http.post('/time-logs/clock-out', data ?? {}),
  summary:   (params)   => http.get('/time-logs/summary', { params }),
  create:    (data)     => http.post('/time-logs', data),
  update:    (id, data) => http.put(`/time-logs/${id}`, data),
  approve:   (id)       => http.post(`/time-logs/${id}/approve`),
  delete:    (id)       => http.delete(`/time-logs/${id}`),
}
