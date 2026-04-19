import type { RouteRecordRaw } from 'vue-router'

import { accessPermissionsRoutes } from '@/modules/access-management/permissions/routes'
import { accessUsersRoutes } from '@/modules/access-management/users/routes'
import { authProfileRoutes } from '@/modules/auth/profileRoutes'
import { articlesRoutes } from '@/modules/settings/articles/routes'
import { calendarRoutes } from '@/modules/calendar/routes'
import { clientOrdersRoutes } from '@/modules/client-orders/routes'
import { companyRoutes } from '@/modules/company/routes'
import { contactRoutes } from '@/modules/contacts/routes'
import { calendarActionsRoutes } from '@/modules/settings/calendar-actions/routes'
import { calendarTypesRoutes } from '@/modules/settings/calendar-types/routes'
import { settingsCompanyRoutes } from '@/modules/settings/company/routes'
import { contactFunctionsRoutes } from '@/modules/settings/contact-functions/routes'
import { countriesRoutes } from '@/modules/settings/countries/routes'
import { entityRoutes } from '@/modules/entities/routes'
import { financeRoutes } from '@/modules/finance/routes'
import { digitalArchiveRoutes } from '@/modules/digital-archive/routes'
import { settingsLogsRoutes } from '@/modules/settings/logs/routes'
import { ordersRoutes } from '@/modules/orders/routes'
import { proposalsRoutes } from '@/modules/proposals/routes'
import { settingsRoutes } from '@/modules/settings/routes'
import { supplierOrdersRoutes } from '@/modules/supplier-orders/routes'
import { workOrdersRoutes } from '@/modules/work-orders/routes'
import { vatRoutes } from '@/modules/settings/vat/routes'

export const layoutChildren: RouteRecordRaw[] = [
  { path: '', redirect: '/clients' },
  { path: 'logs', redirect: '/settings/logs' },
  {
    path: '403',
    name: 'forbidden',
    component: () => import('@/modules/auth/pages/ForbiddenPage.vue'),
    meta: { skipPermissionCheck: true },
  },
  ...entityRoutes,
  ...contactRoutes,
  ...calendarActionsRoutes,
  ...calendarTypesRoutes,
  ...contactFunctionsRoutes,
  ...countriesRoutes,
  ...articlesRoutes,
  ...proposalsRoutes,
  ...clientOrdersRoutes,
  ...supplierOrdersRoutes,
  ...workOrdersRoutes,
  ...ordersRoutes,
  ...financeRoutes,
  ...digitalArchiveRoutes,
  ...calendarRoutes,
  ...authProfileRoutes,
  ...accessUsersRoutes,
  ...accessPermissionsRoutes,
  ...settingsLogsRoutes,
  ...settingsRoutes,
  ...settingsCompanyRoutes,
  ...vatRoutes,
  ...companyRoutes,
  /** Último: rotas inválidas → página inicial da app */
  { path: ':pathMatch(.*)*', redirect: '/clients' },
]
