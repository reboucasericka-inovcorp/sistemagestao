import type { RouteRecordRaw } from 'vue-router'

export const settingsCompanyRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/company',
    name: 'settings.company',
    component: () => import('./pages/CompanyPage.vue'),
    meta: { requiresAuth: true, permission: 'company.read' },
  },
]
