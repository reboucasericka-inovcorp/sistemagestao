import { API_ROUTES } from '@/core/api/apiRoutes'
import {
  isCrudAction,
  normalizePermissionNameFromApi,
  splitPermissionName,
} from '@/modules/access-management/permissions/permissionParsing'
import api from '@/shared/services/api'
import { type PermissionGroup, type PermissionMatrix, type UpsertPermissionGroupPayload } from '../types/permissionGroup'

function normalizePermissionGroup(payload: any): PermissionGroup {
  const usersCount = Number(payload?.users_count ?? payload?.related_users_count ?? payload?.related_users ?? 0)
  const rawPerms = payload?.permissions
  const permissions: string[] = Array.isArray(rawPerms)
    ? rawPerms
        .map((p: unknown) => normalizePermissionNameFromApi(p))
        .filter((p): p is string => p !== null)
    : []

  return {
    id: Number(payload?.id ?? 0),
    name: String(payload?.name ?? ''),
    is_active: Boolean(payload?.is_active),
    permissions,
    users_count: Number.isFinite(usersCount) ? usersCount : 0,
  }
}

export function buildMatrixFromCatalog(catalog: Record<string, string[]>): PermissionMatrix {
  const matrix: PermissionMatrix = {}
  for (const [moduleName, actions] of Object.entries(catalog)) {
    matrix[moduleName] = {
      create: actions.includes('create'),
      read: actions.includes('read'),
      update: actions.includes('update'),
      delete: actions.includes('delete'),
    }
  }
  return matrix
}

export function buildEmptyMatrix(modules: string[]): PermissionMatrix {
  const matrix: PermissionMatrix = {}
  for (const moduleName of modules) {
    matrix[moduleName] = { create: false, read: false, update: false, delete: false }
  }
  return matrix
}

export function permissionListToMatrix(permissions: string[], modules: string[]): PermissionMatrix {
  const matrix = buildEmptyMatrix(modules)
  for (const permission of permissions) {
    const parsed = splitPermissionName(permission)
    if (!parsed || !(parsed.module in matrix)) {
      continue
    }
    if (isCrudAction(parsed.action)) {
      matrix[parsed.module][parsed.action] = true
    }
  }
  return matrix
}

export async function getPermissionGroupById(id: number): Promise<PermissionGroup> {
  const response = await api.get(`${API_ROUTES.roles}/${id}`)
  return normalizePermissionGroup(response.data.data)
}

export async function createPermissionGroup(payload: UpsertPermissionGroupPayload): Promise<PermissionGroup> {
  const response = await api.post(API_ROUTES.roles, payload)
  return normalizePermissionGroup(response.data.data)
}

export async function updatePermissionGroup(id: number, payload: Partial<UpsertPermissionGroupPayload>): Promise<PermissionGroup> {
  const response = await api.put(`${API_ROUTES.roles}/${id}`, payload)
  return normalizePermissionGroup(response.data.data)
}

export async function togglePermissionGroupStatus(id: number, isCurrentlyActive: boolean): Promise<PermissionGroup> {
  if (isCurrentlyActive) {
    const response = await api.delete(`${API_ROUTES.roles}/${id}`)
    return normalizePermissionGroup(response.data.data)
  }

  const response = await api.put(`${API_ROUTES.roles}/${id}`, { is_active: true })
  return normalizePermissionGroup(response.data.data)
}

export async function getPermissionsCatalog(): Promise<Record<string, string[]>> {
  const response = await api.get(API_ROUTES.rolesPermissionsCatalog)
  return (response.data.data ?? {}) as Record<string, string[]>
}
