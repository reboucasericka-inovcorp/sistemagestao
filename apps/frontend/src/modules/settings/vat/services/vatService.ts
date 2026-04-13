import api from '@/shared/services/api'
import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { Vat } from '../types/vat'

export type ListVatQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type VatListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListVatResult = {
  data: Vat[]
  meta: VatListMeta
}

export type UpsertVatPayload = {
  name: string
  percentage: number
  is_active: boolean
}

export async function listVatResult(query?: ListVatQuery): Promise<ListVatResult> {
  const response = await api.get(API_ROUTES.vat, {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  const normalized = normalizeListResponse(response) as {
    data: Vat[]
    meta?: Partial<VatListMeta> | null
  }
  const data = Array.isArray(normalized.data) ? normalized.data : []
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

export async function getVatById(id: number): Promise<Vat> {
  const response = await api.get(`${API_ROUTES.vat}/${id}`)
  return response.data.data as Vat
}

export async function createVat(payload: UpsertVatPayload): Promise<Vat> {
  const response = await api.post(API_ROUTES.vat, payload)
  return response.data.data as Vat
}

export async function updateVat(id: number, payload: Partial<UpsertVatPayload>): Promise<Vat> {
  const response = await api.put(`${API_ROUTES.vat}/${id}`, payload)
  return response.data.data as Vat
}

export async function inactivateVat(id: number): Promise<Vat> {
  const response = await api.delete(`${API_ROUTES.vat}/${id}`)
  return response.data.data as Vat
}

export async function toggleVatStatus(id: number, isCurrentlyActive: boolean): Promise<Vat> {
  if (isCurrentlyActive) {
    return inactivateVat(id)
  }
  return updateVat(id, { is_active: true })
}
