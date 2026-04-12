import axios from 'axios'

const api = axios.create({
  baseURL: `${import.meta.env.VITE_BACKEND_URL ?? 'http://127.0.0.1:8000'}/api/v1`,
  withCredentials: true,
  withXSRFToken: true,
})

export default api
