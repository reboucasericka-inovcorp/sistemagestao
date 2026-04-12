import axios from 'axios'

import type { LoginCredentials } from '../types/credentials'

/** Mesmo host que o browser (não misturar localhost com 127.0.0.1 — quebra cookies/CSRF). */
const backendOrigin = import.meta.env.VITE_BACKEND_URL ?? 'http://127.0.0.1:8000'

const sanctumHttp = axios.create({
  baseURL: backendOrigin,
  withCredentials: true,
  /** Axios 1.x: sem isto, X-XSRF-TOKEN só é enviado em same-origin; Vite (:5173) ≠ Laravel (:8000). */
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

export async function fetchSanctumCsrfCookie(): Promise<void> {
  await sanctumHttp.get('/sanctum/csrf-cookie')
}

export async function loginWithCredentials(credentials: LoginCredentials): Promise<void> {
  await sanctumHttp.post('/login', credentials)
}
