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

/** Único endpoint do utilizador autenticado (alinha com Laravel `routes/api.php`). */
export const AUTH_ME_ENDPOINT = '/api/v1/me'

/** `undefined` = ainda não carregado; `null` = sessão sem utilizador */
let authenticatedUserCache: AuthenticatedUser | null | undefined

/** Pedido em curso — evita `/me` duplicado em navegação rápida */
let fetchAuthenticatedUserInFlight: Promise<AuthenticatedUser | null> | null = null

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
  permissions?: string[]
}

type AuthenticatedUserPayload =
  | AuthenticatedUser
  | {
      data?: AuthenticatedUser
    }

function normalizeAuthenticatedUser(payload: AuthenticatedUserPayload): AuthenticatedUser | null {
  let user: AuthenticatedUser | null = null

  if ('name' in payload && typeof payload.name === 'string') {
    user = payload
  } else if ('data' in payload && payload.data?.name) {
    user = payload.data
  }

  if (!user) {
    return null
  }

  const permissions = Array.isArray(user.permissions) ? user.permissions : []

  return {
    id: user.id,
    name: user.name,
    email: user.email,
    permissions,
  }
}

export function invalidateAuthenticatedUserCache(): void {
  authenticatedUserCache = undefined
  fetchAuthenticatedUserInFlight = null
}

export async function fetchAuthenticatedUser(options?: { force?: boolean }): Promise<AuthenticatedUser | null> {
  if (!options?.force && authenticatedUserCache !== undefined) {
    return authenticatedUserCache
  }

  if (!options?.force && fetchAuthenticatedUserInFlight !== null) {
    return fetchAuthenticatedUserInFlight
  }

  fetchAuthenticatedUserInFlight = (async (): Promise<AuthenticatedUser | null> => {
    try {
      const response = await sanctumHttp.get<AuthenticatedUserPayload>(AUTH_ME_ENDPOINT)
      const normalized = normalizeAuthenticatedUser(response.data)
      authenticatedUserCache = normalized
      return normalized
    } catch {
      authenticatedUserCache = null
      return null
    } finally {
      fetchAuthenticatedUserInFlight = null
    }
  })()

  return fetchAuthenticatedUserInFlight
}

/** Leitura síncrona do cache (ex.: menu logo após o router guard). Não dispara pedido HTTP. */
export function peekAuthenticatedUser(): AuthenticatedUser | null | undefined {
  return authenticatedUserCache
}

export function hasPermission(permission: string): boolean {
  const user = authenticatedUserCache
  if (!user || !Array.isArray(user.permissions)) {
    return false
  }
  return user.permissions.includes(permission)
}

export async function logout(): Promise<void> {
  await fetchSanctumCsrfCookie()
  await sanctumHttp.post('/logout')
  invalidateAuthenticatedUserCache()
}
