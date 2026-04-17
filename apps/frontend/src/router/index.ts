import { createRouter, createWebHistory } from 'vue-router'
import type { RouteLocationNormalized } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import {
  fetchAuthenticatedUser,
  hasPermission,
} from '@/modules/auth/services/authService'
import ForbiddenPage from '@/modules/auth/pages/ForbiddenPage.vue'
import { authRoutes } from '@/modules/auth/routes'
import { layoutChildren } from '@/router/layoutRoutes'
import { resolvePermissionForPath } from '@/router/routePermissions'

const PUBLIC_PATHS = new Set(['/login', '/forgot-password', '/reset-password', '/403'])

function isPublicRoute(to: RouteLocationNormalized): boolean {
  if (to.meta.public) {
    return true
  }
  if (PUBLIC_PATHS.has(to.path)) {
    return true
  }
  return false
}

const routes = [
  ...authRoutes,
  { path: '/403', name: 'forbidden', component: ForbiddenPage, meta: { public: true } },
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

    return { path: '/clients' }
  }

  if (isPublicRoute(to)) {
    if (to.path === '/login') {
      const user = await fetchAuthenticatedUser()
      if (user) {
        return { path: '/clients' }
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
  const requiredPermission =
    typeof metaPermission === 'string' && metaPermission.length > 0
      ? metaPermission
      : resolvePermissionForPath(to.path)

  if (requiredPermission && !hasPermission(requiredPermission)) {
    return { name: 'forbidden', query: { from: to.fullPath } }
  }

  return true
})

export default router
