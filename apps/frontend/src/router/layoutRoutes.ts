import type { RouteRecordRaw } from 'vue-router'

import { articlesRoutes } from '@/modules/articles/routes'
import { calendarRoutes } from '@/modules/calendar/routes'
import { companyRoutes } from '@/modules/company/routes'
import { contactRoutes } from '@/modules/contacts/routes'
import { entityRoutes } from '@/modules/entities/routes'
import { financeRoutes } from '@/modules/finance/routes'
import { logsRoutes } from '@/modules/logs/routes'
import { ordersRoutes } from '@/modules/orders/routes'
import { permissionsRoutes } from '@/modules/permissions/routes'
import { proposalsRoutes } from '@/modules/proposals/routes'
import { settingsRoutes } from '@/modules/settings/routes'
import { usersRoutes } from '@/modules/users/routes'

export const layoutChildren: RouteRecordRaw[] = [
  { path: '', redirect: '/entities' },
  ...entityRoutes,
  ...contactRoutes,
  ...articlesRoutes,
  ...proposalsRoutes,
  ...ordersRoutes,
  ...financeRoutes,
  ...calendarRoutes,
  ...usersRoutes,
  ...permissionsRoutes,
  ...logsRoutes,
  ...settingsRoutes,
  ...companyRoutes,
]
