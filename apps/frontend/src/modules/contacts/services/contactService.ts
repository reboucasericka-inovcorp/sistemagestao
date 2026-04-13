import api from '@/shared/services/api'
import type { Contact } from '../types/contact'

export type ListContactsQuery = {
  entity_id?: number
  contact_function_id?: number
  active_only?: boolean
  search?: string
  page?: number
  per_page?: number
}

export type ContactsListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListContactsResult = {
  data: Contact[]
  meta: ContactsListMeta
}

function normalizeListContactsResponse(payload: unknown): ListContactsResult {
  if (Array.isArray(payload)) {
    return {
      data: payload as Contact[],
      meta: {
        current_page: 1,
        per_page: payload.length || 15,
        total: payload.length,
        last_page: 1,
      },
    }
  }

  if (payload && typeof payload === 'object' && 'data' in payload) {
    const paginated = payload as {
      data?: Contact[]
      current_page?: number
      per_page?: number
      total?: number
      last_page?: number
    }
    const data = Array.isArray(paginated.data) ? paginated.data : []
    return {
      data,
      meta: {
        current_page: paginated.current_page ?? 1,
        per_page: paginated.per_page ?? (data.length || 15),
        total: paginated.total ?? data.length,
        last_page: paginated.last_page ?? 1,
      },
    }
  }

  return {
    data: [],
    meta: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
  }
}

export async function listContactsResult(query?: ListContactsQuery): Promise<ListContactsResult> {
  const response = await api.get('/contacts', {
    params: {
      ...(query?.entity_id != null ? { entity_id: query.entity_id } : {}),
      ...(query?.contact_function_id != null ? { contact_function_id: query.contact_function_id } : {}),
      ...(query?.active_only ? { active_only: 1 } : {}),
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  return normalizeListContactsResponse(response.data.data)
}

export async function listContacts(query?: ListContactsQuery): Promise<Contact[]> {
  const result = await listContactsResult(query)
  return result.data
}

export type UpsertContactPayload = {
  entity_id: number
  contact_function_id: number
  name: string
  email?: string | null
  phone?: string | null
  mobile?: string | null
  notes?: string | null
  is_active: boolean
}

export async function getContactById(id: number): Promise<Contact> {
  const response = await api.get(`/contacts/${id}`)
  return response.data.data as Contact
}

export async function createContact(payload: UpsertContactPayload): Promise<Contact> {
  const response = await api.post('/contacts', payload)
  return response.data.data as Contact
}

export async function updateContact(id: number, payload: Partial<UpsertContactPayload>): Promise<Contact> {
  const response = await api.put(`/contacts/${id}`, payload)
  return response.data.data as Contact
}

export async function inactivateContact(id: number): Promise<Contact> {
  const response = await api.delete(`/contacts/${id}`)
  return response.data.data as Contact
}

export async function toggleContactStatus(id: number, isCurrentlyActive: boolean): Promise<Contact> {
  if (isCurrentlyActive) {
    return inactivateContact(id)
  }
  return updateContact(id, { is_active: true })
}
