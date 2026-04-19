import { normalizePath, resolvePermissionForPath } from '@/router/routePermissions'

/** Alinhado com `router/index.ts` — rotas só de convidado ou 403. */
export const AUTH_PUBLIC_PATHS = new Set(['/login', '/forgot-password', '/reset-password'])

/** Rotas com `meta.skipPermissionCheck` (alinhar com definições nas rotas). */
export const ROUTE_PATHS_SKIP_PERMISSION = new Set(['/profile/security'])

/**
 * Ordem de preferência para "início" — primeiro caminho onde o utilizador tem acesso.
 * Não é rota fixa: depende das permissões vindas de `/api/v1/me`.
 */
export const HOME_ROUTE_CANDIDATES: readonly string[] = [
  '/clients',
  '/suppliers',
  '/contacts',
  '/proposals',
  '/calendar',
  '/client-orders',
  '/supplier-orders',
  '/work-orders',
  '/supplier-invoices',
  '/bank-accounts',
  '/customer-accounts',
  '/digital-archive',
  '/users',
  '/permissions',
  '/settings/countries',
  '/settings/contact-functions',
  '/settings/calendar-types',
  '/settings/calendar-actions',
  '/settings/articles',
  '/settings/vat',
  '/settings/logs',
  '/settings/company',
  '/company',
]

/**
 * Indica se o utilizador pode abrir este caminho na app autenticada
 * (Spatie + rotas sem check de permissão). Não trata `meta.permission` isoladamente —
 * `resolvePermissionForPath` deve cobrir os mesmos caminhos que o guard.
 */
export function isPathAccessibleForUser(fullPath: string, permissions: readonly string[]): boolean {
  const path = normalizePath(fullPath.split('?')[0] ?? '')

  if (path === '/403' || path.startsWith('/403/')) {
    return false
  }

  if (AUTH_PUBLIC_PATHS.has(path)) {
    return false
  }

  if (ROUTE_PATHS_SKIP_PERMISSION.has(path)) {
    return true
  }

  const required = resolvePermissionForPath(path)
  if (!required) {
    return false
  }

  return permissions.includes(required)
}

/**
 * Primeiro destino da lista {@link HOME_ROUTE_CANDIDATES} acessível com as permissões dadas;
 * caso contrário perfil (sempre permitido para sessão autenticada).
 */
export function resolveHomeByPermission(permissions: readonly string[]): string {
  for (const candidate of HOME_ROUTE_CANDIDATES) {
    if (isPathAccessibleForUser(candidate, permissions)) {
      return candidate
    }
  }

  return '/profile/security'
}

/**
 * Destino seguro para o botão "Voltar" na página 403: só reutiliza `from` se for acessível;
 * caso contrário o mesmo valor que {@link resolveHomeByPermission} (evita loop 403 ↔ rota negada).
 */
export function resolveSafeBackPath(fromQuery: unknown, permissions: readonly string[]): string {
  const raw = typeof fromQuery === 'string' ? fromQuery.trim() : ''
  if (raw === '' || !raw.startsWith('/') || raw.startsWith('//')) {
    return resolveHomeByPermission(permissions)
  }

  const pathOnly = normalizePath(raw.split('?')[0] ?? '')

  if (isPathAccessibleForUser(pathOnly, permissions)) {
    return pathOnly
  }

  return resolveHomeByPermission(permissions)
}
