import type { RouteRecordRaw } from 'vue-router'

export const contactFunctionsRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/contact-functions',
    name: 'contact-functions.index',
    component: () => import('./pages/ContactFunctionHome.vue'),
    meta: { requiresAuth: true, permission: 'contact-functions.read' },
  },
  {
    path: 'settings/contact-functions/new',
    name: 'contact-functions.new',
    component: () => import('./pages/ContactFunctionFormPage.vue'),
    meta: { requiresAuth: true, permission: 'contact-functions.create' },
  },
  {
    path: 'settings/contact-functions/:id/edit',
    name: 'contact-functions.edit',
    component: () => import('./pages/ContactFunctionFormPage.vue'),
    meta: { requiresAuth: true, permission: 'contact-functions.update' },
  },
]
