export const CRUD_ACTIONS = ['create', 'read', 'update', 'delete'] as const

export type CrudAction = (typeof CRUD_ACTIONS)[number]
export type PermissionMatrix = Record<string, Record<CrudAction, boolean>>

export type PermissionGroup = {
  id: number
  name: string
  is_active: boolean
  permissions: string[]
  users_count: number
}

export type UpsertPermissionGroupPayload = {
  name: string
  is_active: boolean
  permissions: PermissionMatrix
}
