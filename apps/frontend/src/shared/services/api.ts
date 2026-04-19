import axios, { isAxiosError } from 'axios'

const api = axios.create({
  baseURL: `${import.meta.env.VITE_BACKEND_URL ?? 'http://127.0.0.1:8000'}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
})

if (import.meta.env.DEV) {
  api.interceptors.response.use(
    (response) => response,
    (error: unknown) => {
      if (isAxiosError(error) && error.response?.status === 403) {
        console.warn('[api] 403 Forbidden', error.config?.url ?? '')
      }
      return Promise.reject(error)
    },
  )
}

export default api
