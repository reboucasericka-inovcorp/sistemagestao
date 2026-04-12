import { createRouter, createWebHistory } from 'vue-router'

import AppLayout from '@/layouts/AppLayout.vue'
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

export default router
