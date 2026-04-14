import api from '@/shared/services/api'
import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { ContactFunction } from '../types/contactFunction'

export type ListContactFunctionsQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type ContactFunctionsListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListContactFunctionsResult = {
  data: ContactFunction[]
  meta: ContactFunctionsListMeta
}

export type UpsertContactFunctionPayload = {
  name: string
  is_active: boolean
}

export async function listContactFunctionsResult(
  query?: ListContactFunctionsQuery,
): Promise<ListContactFunctionsResult> {
  const response = await api.get(API_ROUTES.contactFunctions, {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  const normalized = normalizeListResponse(response.data) as {
    data: ContactFunction[]
    meta?: Partial<ContactFunctionsListMeta> | null
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

export async function getContactFunctionById(id: number): Promise<ContactFunction> {
  const response = await api.get(`${API_ROUTES.contactFunctions}/${id}`)
  return response.data.data as ContactFunction
}

export async function createContactFunction(payload: UpsertContactFunctionPayload): Promise<ContactFunction> {
  const response = await api.post(API_ROUTES.contactFunctions, payload)
  return response.data.data as ContactFunction
}

export async function updateContactFunction(
  id: number,
  payload: Partial<UpsertContactFunctionPayload>,
): Promise<ContactFunction> {
  const response = await api.put(`${API_ROUTES.contactFunctions}/${id}`, payload)
  return response.data.data as ContactFunction
}

export async function inactivateContactFunction(id: number): Promise<ContactFunction> {
  const response = await api.delete(`${API_ROUTES.contactFunctions}/${id}`)
  return response.data.data as ContactFunction
}

export async function toggleContactFunctionStatus(
  id: number,
  isCurrentlyActive: boolean,
): Promise<ContactFunction> {
  if (isCurrentlyActive) {
    return inactivateContactFunction(id)
  }
  return updateContactFunction(id, { is_active: true })
}
