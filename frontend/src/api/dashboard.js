import http from './http'

export const dashboardApi = {
  get: () => http.get('/dashboard'),
}
