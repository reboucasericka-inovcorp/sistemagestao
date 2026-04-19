import { CRUD_ACTIONS, type CrudAction } from '@/modules/access-management/permissions/types/permissionGroup'

/**
 * Converte entrada da API (string ou `{ name: string }`) para o nome da permissão Spatie.
 */
export function normalizePermissionNameFromApi(permission: unknown): string | null {
  if (typeof permission === 'string' && permission.trim().length > 0) {
    return permission.trim()
  }
  if (
    permission &&
    typeof permission === 'object' &&
    'name' in permission &&
    typeof (permission as { name: unknown }).name === 'string'
  ) {
    const name = (permission as { name: string }).name.trim()
    return name.length > 0 ? name : null
  }
  return null
}

/**
 * Extrai módulo e ação de `modulo.acao`. O módulo pode conter hífenes (ex.: `calendar-types.read`);
 * não usar `split('.')` — quebraria esses nomes.
 */
export function splitPermissionName(permission: string): { module: string; action: string } | null {
  const trimmed = permission.trim()
  const lastDot = trimmed.lastIndexOf('.')
  if (lastDot <= 0 || lastDot === trimmed.length - 1) {
    return null
  }
  const module = trimmed.slice(0, lastDot)
  const action = trimmed.slice(lastDot + 1)
  if (!module || !action) {
    return null
  }
  return { module, action }
}

export function isCrudAction(action: string): action is CrudAction {
  return (CRUD_ACTIONS as readonly string[]).includes(action)
}
