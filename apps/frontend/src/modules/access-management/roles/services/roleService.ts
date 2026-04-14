import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import api from '@/shared/services/api'
import type { AccessRole, UpsertAccessRolePayload } from '../types/role'

export type ListRolesQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type RolesListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListRolesResult = {
  data: AccessRole[]
  meta: RolesListMeta
}

export async function listRolesResult(query?: ListRolesQuery): Promise<ListRolesResult> {
  const response = await api.get(API_ROUTES.roles, {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })

  const normalized = normalizeListResponse(response.data) as {
    data: AccessRole[]
    meta?: Partial<RolesListMeta> | null
  }
  console.log('[listRolesResult] response.data', response.data)
  console.log('[listRolesResult] normalized', normalized)
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

export async function listRoles(): Promise<AccessRole[]> {
  const result = await listRolesResult({ per_page: 100 })
  return result.data
}

export async function getRoleById(id: number): Promise<AccessRole> {
  const response = await api.get(`${API_ROUTES.roles}/${id}`)
  return response.data.data as AccessRole
}

export async function createRole(payload: UpsertAccessRolePayload): Promise<AccessRole> {
  const response = await api.post(API_ROUTES.roles, payload)
  return response.data.data as AccessRole
}

export async function updateRole(id: number, payload: Partial<UpsertAccessRolePayload>): Promise<AccessRole> {
  const response = await api.put(`${API_ROUTES.roles}/${id}`, payload)
  return response.data.data as AccessRole
}

export async function toggleRoleStatus(id: number, isCurrentlyActive: boolean): Promise<AccessRole> {
  if (isCurrentlyActive) {
    const response = await api.delete(`${API_ROUTES.roles}/${id}`)
    return response.data.data as AccessRole
  }

  const response = await api.put(`${API_ROUTES.roles}/${id}`, { is_active: true })
  return response.data.data as AccessRole
}

export async function getPermissionsCatalog(): Promise<Record<string, string[]>> {
  const response = await api.get(API_ROUTES.rolesPermissionsCatalog)
  return (response.data.data ?? {}) as Record<string, string[]>
}
