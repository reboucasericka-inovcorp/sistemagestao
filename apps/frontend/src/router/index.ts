import { createRouter, createWebHistory } from 'vue-router'
import type { RouteLocationNormalized } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import {
  fetchAuthenticatedUser,
  hasPermission,
} from '@/modules/auth/services/authService'
import { authRoutes } from '@/modules/auth/routes'
import { AUTH_PUBLIC_PATHS, resolveHomeByPermission } from '@/router/resolveHomeByPermission'
import { layoutChildren } from '@/router/layoutRoutes'
import { resolvePermissionForPath } from '@/router/routePermissions'

function isPublicRoute(to: RouteLocationNormalized): boolean {
  if (to.meta.public) {
    return true
  }
  if (AUTH_PUBLIC_PATHS.has(to.path)) {
    return true
  }
  return false
}

const routes = [
  ...authRoutes,
  { path: '/finance', redirect: '/supplier-invoices' },
  { path: '/digital-files', redirect: '/digital-archive' },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: layoutChildren,
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const backofficeEmails = new Set(
  String(import.meta.env.VITE_BACKOFFICE_EMAILS ?? '')
    .split(',')
    .map((value) => value.trim().toLowerCase())
    .filter((value) => value.length > 0),
)

async function getCurrentUserEmail(): Promise<string | null> {
  const user = await fetchAuthenticatedUser()
  return user?.email?.toLowerCase() ?? null
}

router.beforeEach(async (to) => {
  const requiresBackoffice = to.matched.some((record) => record.meta?.backofficeOnly)
  if (requiresBackoffice) {
    const email = await getCurrentUserEmail()
    if (email && backofficeEmails.has(email)) {
      return true
    }

    const user = await fetchAuthenticatedUser()
    return { path: resolveHomeByPermission(user?.permissions ?? []) }
  }

  if (isPublicRoute(to)) {
    if (to.path === '/login') {
      const user = await fetchAuthenticatedUser()
      if (user) {
        return { path: resolveHomeByPermission(user.permissions ?? []) }
      }
    }

    return true
  }

  const user = await fetchAuthenticatedUser()
  if (!user) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }

  if (to.meta.skipPermissionCheck === true) {
    return true
  }

  const metaPermission = to.meta.permission
  let requiredPermission: string | undefined
  if (typeof metaPermission === 'string' && metaPermission.length > 0) {
    requiredPermission = metaPermission
  } else {
    requiredPermission = resolvePermissionForPath(to.path)
  }

  if (requiredPermission && !hasPermission(requiredPermission)) {
    return {
      path: resolveHomeByPermission(user.permissions ?? []),
      replace: true,
    }
  }

  return true
})

export default router
