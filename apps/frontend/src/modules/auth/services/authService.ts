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

export type LoginResponsePayload = {
  two_factor?: boolean
}

export async function loginWithCredentials(credentials: LoginCredentials): Promise<LoginResponsePayload> {
  const response = await sanctumHttp.post<LoginResponsePayload>('/login', credentials)
  return response.data
}

export async function completeTwoFactorLogin(payload: {
  code?: string
  recovery_code?: string
}): Promise<LoginResponsePayload> {
  const response = await sanctumHttp.post<LoginResponsePayload>('/two-factor-challenge', payload)
  return response.data
}

export async function sendPasswordResetLink(email: string): Promise<void> {
  await fetchSanctumCsrfCookie()
  await sanctumHttp.post('/forgot-password', { email })
}

export async function resetPassword(payload: {
  token: string
  email: string
  password: string
  password_confirmation: string
}): Promise<void> {
  await fetchSanctumCsrfCookie()
  await sanctumHttp.post('/reset-password', payload)
}

export type AuthenticatedUser = {
  id: number
  name: string
  email: string
}

type AuthenticatedUserPayload =
  | AuthenticatedUser
  | {
      data?: AuthenticatedUser
    }

function normalizeAuthenticatedUser(payload: AuthenticatedUserPayload): AuthenticatedUser | null {
  if ('name' in payload && typeof payload.name === 'string') {
    return payload
  }

  if ('data' in payload && payload.data?.name) {
    return payload.data
  }

  return null
}

export async function fetchAuthenticatedUser(): Promise<AuthenticatedUser | null> {
  try {
    const response = await sanctumHttp.get<AuthenticatedUserPayload>('/api/v1/me')
    const normalized = normalizeAuthenticatedUser(response.data)
    return normalized
  } catch {
    return null
  }
}

export async function logout(): Promise<void> {
  await fetchSanctumCsrfCookie()
  await sanctumHttp.post('/logout')
}
