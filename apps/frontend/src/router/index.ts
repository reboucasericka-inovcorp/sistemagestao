import { createRouter, createWebHistory } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
import { fetchAuthenticatedUser } from '@/modules/auth/services/authService'
import { authRoutes } from '@/modules/auth/routes'
import { layoutChildren } from '@/router/layoutRoutes'

const routes = [
  ...authRoutes,
  {
    path: '/',
    component: AppLayout,
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
  if (!requiresBackoffice) {
    return true
  }

  const email = await getCurrentUserEmail()
  if (email && backofficeEmails.has(email)) {
    return true
  }

  return { path: '/clients' }
})

export default router
