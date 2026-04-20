import type { RouteRecordRaw } from 'vue-router'

import CompanyHome from '@/modules/company/pages/CompanyHome.vue'

export const companyRoutes: RouteRecordRaw[] = [
  { path: 'company', name: 'company.home', component: CompanyHome, meta: { requiresAuth: true, permission: 'company.read' } },
]
