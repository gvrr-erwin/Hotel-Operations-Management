import axios from 'axios'

const http = axios.create({
  // In dev: VITE_API_URL is undefined → '' → '/api' → proxied by Vite
  // In prod: VITE_API_URL=https://xxx.up.railway.app → full URL
  baseURL: `${import.meta.env.VITE_API_URL ?? ''}/api`,
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
  withCredentials: true,
})

http.interceptors.request.use((config) => {
  const token = localStorage.getItem('hom_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

http.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      localStorage.removeItem('hom_token')
      window.location.href = '/login'
    }
    return Promise.reject(err)
  }
)

export default http
