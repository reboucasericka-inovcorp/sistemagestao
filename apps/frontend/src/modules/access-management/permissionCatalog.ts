export const ACCESS_MODULES = [
  'entities',
  'contacts',
  'countries',
  'vat',
  'contact-functions',
  'calendar-types',
  'calendar-actions',
  'articles',
  'company',
  'logs',
  'users',
] as const

export const ACCESS_ACTIONS = ['create', 'read', 'update', 'delete'] as const

export type AccessModule = (typeof ACCESS_MODULES)[number]
export type AccessAction = (typeof ACCESS_ACTIONS)[number]

export type PermissionMatrix = Record<AccessModule, Record<AccessAction, boolean>>

export function buildEmptyPermissionMatrix(): PermissionMatrix {
  const matrix = {} as PermissionMatrix
  for (const moduleName of ACCESS_MODULES) {
    matrix[moduleName] = {
      create: false,
      read: false,
      update: false,
      delete: false,
    }
  }
  return matrix
}

export function permissionListToMatrix(permissions: string[]): PermissionMatrix {
  const matrix = buildEmptyPermissionMatrix()
  for (const permission of permissions) {
    const [moduleName, action] = permission.split('.')
    if (!moduleName || !action) {
      continue
    }
    if (moduleName in matrix && action in matrix[moduleName as AccessModule]) {
      matrix[moduleName as AccessModule][action as AccessAction] = true
    }
  }
  return matrix
}
