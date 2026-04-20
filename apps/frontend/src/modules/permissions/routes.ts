import type { RouteRecordRaw } from 'vue-router'

import PermissionsHome from '@/modules/permissions/pages/PermissionsHome.vue'

export const permissionsRoutes: RouteRecordRaw[] = [
  { path: 'permissions', name: 'permissions.home', component: PermissionsHome, meta: { requiresAuth: true, permission: 'roles.read' } },
]
