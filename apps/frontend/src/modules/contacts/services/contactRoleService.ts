import api from '@/shared/services/api'

export type ContactRole = {
  id: number
  name: string
}

const fallbackRoles: ContactRole[] = [
  { id: 1, name: 'Commercial' },
  { id: 2, name: 'Billing' },
  { id: 3, name: 'Technical' },
  { id: 4, name: 'Administrative' },
]

export async function listContactRoles(): Promise<ContactRole[]> {
  try {
    const response = await api.get('/contact-functions', {
      params: { active_only: 1, per_page: 100 },
    })
    const payload = response.data.data

    if (Array.isArray(payload)) {
      return payload as ContactRole[]
    }

    if (payload && Array.isArray(payload.data)) {
      return payload.data as ContactRole[]
    }
  } catch {
    // Fallback temporário enquanto endpoint dedicado não estiver disponível.
  }

  return fallbackRoles
}
