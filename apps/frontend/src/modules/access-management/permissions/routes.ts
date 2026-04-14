import type { RouteRecordRaw } from 'vue-router'

export const accessPermissionsRoutes: RouteRecordRaw[] = [
  { path: 'permissions', name: 'permissions.index', component: () => import('./pages/PermissionsHome.vue') },
  { path: 'permissions/new', name: 'permissions.new', component: () => import('./pages/PermissionGroupFormPage.vue') },
  { path: 'permissions/:id/edit', name: 'permissions.edit', component: () => import('./pages/PermissionGroupFormPage.vue') },
]
