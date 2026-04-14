import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import api from '@/shared/services/api'
import type { AccessUser, UpsertAccessUserPayload } from '../types/accessUser'

export type ListUsersQuery = {
  search?: string
  role?: number
  page?: number
  per_page?: number
}

export type UsersListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListUsersResult = {
  data: AccessUser[]
  meta: UsersListMeta
}

export async function listUsersResult(query?: ListUsersQuery): Promise<ListUsersResult> {
  const response = await api.get(API_ROUTES.users, {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.role ? { role: query.role } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })

  const normalized = normalizeListResponse(response.data) as {
    data: AccessUser[]
    meta?: Partial<UsersListMeta> | null
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

export async function getUserById(id: number): Promise<AccessUser> {
  const response = await api.get(`${API_ROUTES.users}/${id}`)
  return response.data.data as AccessUser
}

export async function createUser(payload: UpsertAccessUserPayload): Promise<AccessUser> {
  const response = await api.post(API_ROUTES.users, payload)
  return response.data.data as AccessUser
}

export async function updateUser(id: number, payload: Partial<UpsertAccessUserPayload>): Promise<AccessUser> {
  const response = await api.put(`${API_ROUTES.users}/${id}`, payload)
  return response.data.data as AccessUser
}

export async function toggleUserStatus(id: number, isCurrentlyActive: boolean): Promise<AccessUser> {
  if (isCurrentlyActive) {
    const response = await api.delete(`${API_ROUTES.users}/${id}`)
    return response.data.data as AccessUser
  }

  const response = await api.put(`${API_ROUTES.users}/${id}`, { is_active: true })
  return response.data.data as AccessUser
}
