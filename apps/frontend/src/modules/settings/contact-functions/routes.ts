import type { RouteRecordRaw } from 'vue-router'

export const contactFunctionsRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/contact-functions',
    name: 'contact-functions.index',
    component: () => import('./pages/ContactFunctionHome.vue'),
  },
  {
    path: 'settings/contact-functions/new',
    name: 'contact-functions.new',
    component: () => import('./pages/ContactFunctionFormPage.vue'),
  },
  {
    path: 'settings/contact-functions/:id/edit',
    name: 'contact-functions.edit',
    component: () => import('./pages/ContactFunctionFormPage.vue'),
  },
]
