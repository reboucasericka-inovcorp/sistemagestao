import api from '@/shared/services/api'
import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { Contact } from '../types/contact'

export type ListContactsQuery = {
  entity_id?: number
  is_active?: boolean
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
  const normalized = normalizeListResponse(payload) as {
    data: Contact[]
    meta?: Partial<ContactsListMeta> | null
  }
  const data = normalized.data
  return {
    data,
    meta: {
      current_page: normalized.meta?.current_page ?? 1,
      per_page: normalized.meta?.per_page ?? (data.length || 15),
      total: normalized.meta?.total ?? data.length,
      last_page: normalized.meta?.last_page ?? 1,
    },
  }
}

export async function listContactsResult(query?: ListContactsQuery): Promise<ListContactsResult> {
  const response = await api.get(API_ROUTES.contacts, {
    params: {
      ...(query?.entity_id != null ? { entity_id: query.entity_id } : {}),
      ...(query?.is_active != null ? { is_active: query.is_active } : {}),
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  return normalizeListContactsResponse(response.data)
}

export async function listContacts(query?: ListContactsQuery): Promise<Contact[]> {
  const result = await listContactsResult(query)
  return result.data
}

export type UpsertContactPayload = {
  entity_id: number
  contact_function_id?: number | null
  first_name: string
  last_name: string
  email?: string | null
  phone?: string | null
  mobile?: string | null
  rgpd_consent: boolean
  notes?: string | null
  is_active: boolean
}

export async function getContactById(id: number): Promise<Contact> {
  const response = await api.get(`${API_ROUTES.contacts}/${id}`)
  return (response.data?.data ?? response.data) as Contact
}

export async function createContact(payload: UpsertContactPayload): Promise<Contact> {
  const response = await api.post(API_ROUTES.contacts, payload)
  return (response.data?.data ?? response.data) as Contact
}

export async function updateContact(id: number, payload: Partial<UpsertContactPayload>): Promise<Contact> {
  const response = await api.put(`${API_ROUTES.contacts}/${id}`, payload)
  return (response.data?.data ?? response.data) as Contact
}

export async function inactivateContact(id: number): Promise<Contact> {
  const response = await api.delete(`${API_ROUTES.contacts}/${id}`)
  return (response.data?.data ?? response.data) as Contact
}

export async function toggleContactStatus(id: number, isCurrentlyActive: boolean): Promise<Contact> {
  if (isCurrentlyActive) {
    return inactivateContact(id)
  }
  return updateContact(id, { is_active: true })
}
