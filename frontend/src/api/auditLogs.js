import http from './http'

export const auditLogsApi = {
  list: (params) => http.get('/audit-logs', { params }),
}
