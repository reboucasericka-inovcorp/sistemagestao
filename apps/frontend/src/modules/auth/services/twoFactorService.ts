import axios from 'axios'

const backendOrigin = import.meta.env.VITE_BACKEND_URL ?? 'http://127.0.0.1:8000'

const fortifyHttp = axios.create({
  baseURL: backendOrigin,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

type RecoveryCodesPayload =
  | string[]
  | {
      recovery_codes?: string[]
    }

function normalizeRecoveryCodes(payload: RecoveryCodesPayload): string[] {
  if (Array.isArray(payload)) {
    return payload
  }

  return payload.recovery_codes ?? []
}

export async function fetchSanctumCsrfCookie(): Promise<void> {
  await fortifyHttp.get('/sanctum/csrf-cookie')
}

export async function enableTwoFactorAuthentication(): Promise<void> {
  await fetchSanctumCsrfCookie()
  await fortifyHttp.post('/user/two-factor-authentication')
}

export async function confirmPassword(password: string): Promise<void> {
  await fetchSanctumCsrfCookie()
  await fortifyHttp.post('/user/confirm-password', { password })
}

export async function disableTwoFactorAuthentication(): Promise<void> {
  await fetchSanctumCsrfCookie()
  await fortifyHttp.delete('/user/two-factor-authentication')
}

export async function confirmTwoFactorAuthentication(code: string): Promise<void> {
  await fetchSanctumCsrfCookie()
  await fortifyHttp.post('/user/confirmed-two-factor-authentication', { code })
}

export async function getTwoFactorQrCode(): Promise<string> {
  const response = await fortifyHttp.get<{ svg: string }>('/user/two-factor-qr-code')
  return response.data.svg
}

export async function getRecoveryCodes(): Promise<string[]> {
  const response = await fortifyHttp.get<RecoveryCodesPayload>('/user/two-factor-recovery-codes')
  return normalizeRecoveryCodes(response.data)
}

export async function regenerateRecoveryCodes(): Promise<string[]> {
  await fetchSanctumCsrfCookie()
  const response = await fortifyHttp.post<RecoveryCodesPayload>('/user/two-factor-recovery-codes')
  return normalizeRecoveryCodes(response.data)
}
