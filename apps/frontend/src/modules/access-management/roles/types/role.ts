import type { PermissionMatrix } from '@/modules/access-management/permissionCatalog'

export type AccessRole = {
  id: number
  name: string
  is_active: boolean
  permissions: string[]
  users_count: number
}

export type UpsertAccessRolePayload = {
  name: string
  is_active: boolean
  permissions: PermissionMatrix
}
