import type { RouteRecordRaw } from 'vue-router'

export const accessRolesRoutes: RouteRecordRaw[] = [
  { path: 'permissions', name: 'roles.index', component: () => import('./pages/RolesHome.vue'), meta: { requiresAuth: true, permission: 'roles.read' } },
  { path: 'permissions/new', name: 'roles.new', component: () => import('./pages/RoleFormPage.vue'), meta: { requiresAuth: true, permission: 'roles.create' } },
  { path: 'permissions/:id/edit', name: 'roles.edit', component: () => import('./pages/RoleFormPage.vue'), meta: { requiresAuth: true, permission: 'roles.update' } },
]
