import api from '@/shared/services/api'
import type { Entity } from '../types/entity'

export type ListEntitiesQuery = {
  clients?: boolean
  suppliers?: boolean
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

function normalizeListEntitiesResponse(payload: unknown): ListEntitiesResult {
  if (Array.isArray(payload)) {
    return {
      data: payload as Entity[],
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
      data?: Entity[]
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
    meta: {
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
    },
  }
}

export async function listEntitiesResult(query?: ListEntitiesQuery): Promise<ListEntitiesResult> {
  const response = await api.get('/entities', {
    params: {
      ...(query?.clients ? { clients: 1 } : {}),
      ...(query?.suppliers ? { suppliers: 1 } : {}),
      ...(query?.active_only ? { active_only: 1 } : {}),
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  return normalizeListEntitiesResponse(response.data.data)
}

export async function listEntities(query?: ListEntitiesQuery): Promise<Entity[]> {
  const result = await listEntitiesResult(query)
  return result.data
}

export type UpsertEntityPayload = {
  number: string
  nif: string
  name: string
  address?: string | null
  postal_code?: string | null
  city?: string | null
  country?: string | null
  phone?: string | null
  mobile?: string | null
  website?: string | null
  email?: string | null
  gdpr_consent: boolean
  is_active: boolean
  is_client: boolean
  is_supplier: boolean
  notes?: string | null
}

export async function getEntityById(id: number): Promise<Entity> {
  const response = await api.get(`/entities/${id}`)
  return response.data.data as Entity
}

export async function createEntity(payload: UpsertEntityPayload): Promise<Entity> {
  const response = await api.post('/entities', payload)
  return response.data.data as Entity
}

export async function updateEntity(id: number, payload: Partial<UpsertEntityPayload>): Promise<Entity> {
  const response = await api.put(`/entities/${id}`, payload)
  return response.data.data as Entity
}

export async function toggleEntityStatus(id: number): Promise<Entity> {
  const response = await api.delete(`/entities/${id}`)
  return response.data.data as Entity
}

export async function checkEntityNif(nif: string): Promise<{ available: boolean }> {
  const response = await api.get('/entities/check-nif', {
    params: { nif },
  })
  return response.data.data as { available: boolean }
}

export async function lookupEntityByVies(
  nif: string,
): Promise<{ name?: string; address?: string; country?: string }> {
  const response = await api.get('/entities/vies-lookup', {
    params: { nif },
  })
  return response.data.data as { name?: string; address?: string; country?: string }
}
