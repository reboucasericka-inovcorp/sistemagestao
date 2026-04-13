import type { RouteRecordRaw } from 'vue-router'

import { articlesRoutes } from '@/modules/settings/articles/routes'
import { calendarRoutes } from '@/modules/calendar/routes'
import { companyRoutes } from '@/modules/company/routes'
import { contactRoutes } from '@/modules/contacts/routes'
import { calendarActionsRoutes } from '@/modules/settings/calendar-actions/routes'
import { calendarTypesRoutes } from '@/modules/settings/calendar-types/routes'
import { settingsCompanyRoutes } from '@/modules/settings/company/routes'
import { contactFunctionsRoutes } from '@/modules/settings/contact-functions/routes'
import { countriesRoutes } from '@/modules/settings/countries/routes'
import { entityRoutes } from '@/modules/entities/routes'
import { financeRoutes } from '@/modules/finance/routes'
import { settingsLogsRoutes } from '@/modules/settings/logs/routes'
import { ordersRoutes } from '@/modules/orders/routes'
import { permissionsRoutes } from '@/modules/permissions/routes'
import { proposalsRoutes } from '@/modules/proposals/routes'
import { settingsRoutes } from '@/modules/settings/routes'
import { vatRoutes } from '@/modules/settings/vat/routes'
import { usersRoutes } from '@/modules/users/routes'

export const layoutChildren: RouteRecordRaw[] = [
  { path: '', redirect: '/entities' },
  ...entityRoutes,
  ...contactRoutes,
  ...calendarActionsRoutes,
  ...calendarTypesRoutes,
  ...contactFunctionsRoutes,
  ...countriesRoutes,
  ...articlesRoutes,
  ...proposalsRoutes,
  ...ordersRoutes,
  ...financeRoutes,
  ...calendarRoutes,
  ...usersRoutes,
  ...permissionsRoutes,
  ...settingsLogsRoutes,
  ...settingsRoutes,
  ...settingsCompanyRoutes,
  ...vatRoutes,
  ...companyRoutes,
]
