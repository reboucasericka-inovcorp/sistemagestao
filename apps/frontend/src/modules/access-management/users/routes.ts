import type { RouteRecordRaw } from 'vue-router'

export const accessUsersRoutes: RouteRecordRaw[] = [
  { path: 'users', name: 'users.index', component: () => import('./pages/UsersHome.vue') },
  { path: 'users/new', name: 'users.new', component: () => import('./pages/UserFormPage.vue') },
  { path: 'users/:id/edit', name: 'users.edit', component: () => import('./pages/UserFormPage.vue') },
]
