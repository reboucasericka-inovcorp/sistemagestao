import api from '@/shared/services/api'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { Entity } from '../types/entity'

export type ListEntitiesQuery = {
  is_client?: boolean
  is_supplier?: boolean
  active_only?: boolean
  search?: string
  page?: number
  per_page?: number
}

export type EntitiesListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListEntitiesResult = {
  data: Entity[]
  meta: EntitiesListMeta
}

export async function listEntitiesResult(query?: ListEntitiesQuery): Promise<ListEntitiesResult> {
  const response = await api.get('/entities', {
    params: {
      ...(query?.is_client ? { is_client: true } : {}),
      ...(query?.is_supplier ? { is_supplier: true } : {}),
      ...(query?.active_only ? { active_only: true } : {}),
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  const normalized = normalizeListResponse(response.data) as {
    data: Entity[]
    meta?: Partial<EntitiesListMeta> | null
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

export async function listEntities(query?: ListEntitiesQuery): Promise<Entity[]> {
  const result = await listEntitiesResult(query)
  return result.data
}

export type UpsertEntityPayload = {
  is_client: boolean
  is_supplier: boolean
  nif: string
  name: string
  address?: string | null
  postal_code?: string | null
  city?: string | null
  country_id?: number | null
  phone?: string | null
  mobile?: string | null
  website?: string | null
  email?: string | null
  gdpr_consent: boolean
  is_active: boolean
  notes?: string | null
}

export async function getEntityById(id: number): Promise<Entity> {
  const response = await api.get(`/entities/${id}`)
  return (response.data?.data ?? response.data) as Entity
}

export async function createEntity(payload: UpsertEntityPayload): Promise<Entity> {
  const response = await api.post('/entities', payload)
  return (response.data?.data ?? response.data) as Entity
}

export async function updateEntity(id: number, payload: Partial<UpsertEntityPayload>): Promise<Entity> {
  const response = await api.put(`/entities/${id}`, payload)
  return (response.data?.data ?? response.data) as Entity
}

export async function toggleEntityStatus(id: number): Promise<Entity> {
  const response = await api.delete(`/entities/${id}`)
  return (response.data?.data ?? response.data) as Entity
}

export async function lookupEntityByVies(
  nif: string,
): Promise<{ name?: string; address?: string; valid: boolean }> {
  const response = await api.get('/vies/validate', {
    params: { nif },
  })
  return (response.data?.data ?? response.data) as { name?: string; address?: string; valid: boolean }
}
