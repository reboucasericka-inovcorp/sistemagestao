import type { RouteRecordRaw } from 'vue-router'

export const accessUsersRoutes: RouteRecordRaw[] = [
  { path: 'users', name: 'users.index', component: () => import('./pages/UsersHome.vue'), meta: { requiresAuth: true, permission: 'users.read' } },
  { path: 'users/new', name: 'users.new', component: () => import('./pages/UserFormPage.vue'), meta: { requiresAuth: true, permission: 'users.create' } },
  { path: 'users/:id/edit', name: 'users.edit', component: () => import('./pages/UserFormPage.vue'), meta: { requiresAuth: true, permission: 'users.update' } },
]
