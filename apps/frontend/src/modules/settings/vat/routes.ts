import type { RouteRecordRaw } from 'vue-router'

export const vatRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/vat',
    name: 'vat.index',
    component: () => import('./pages/VatHome.vue'),
  },
  {
    path: 'settings/vat/new',
    name: 'vat.new',
    component: () => import('./pages/VatForm.vue'),
  },
  {
    path: 'settings/vat/:id/edit',
    name: 'vat.edit',
    component: () => import('./pages/VatForm.vue'),
  },
]
