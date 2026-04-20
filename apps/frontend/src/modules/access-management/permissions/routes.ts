import type { RouteRecordRaw } from 'vue-router'

export const accessPermissionsRoutes: RouteRecordRaw[] = [
  { path: 'permissions', name: 'permissions.index', component: () => import('./pages/PermissionsHome.vue'), meta: { requiresAuth: true, permission: 'roles.read' } },
  { path: 'permissions/new', name: 'permissions.new', component: () => import('./pages/PermissionGroupFormPage.vue'), meta: { requiresAuth: true, permission: 'roles.create' } },
  { path: 'permissions/:id/edit', name: 'permissions.edit', component: () => import('./pages/PermissionGroupFormPage.vue'), meta: { requiresAuth: true, permission: 'roles.update' } },
]
