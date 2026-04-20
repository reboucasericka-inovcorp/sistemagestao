import type { RouteRecordRaw } from 'vue-router'

export const settingsLogsRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/logs',
    name: 'settings.logs',
    component: () => import('./pages/LogsPage.vue'),
    meta: { requiresAuth: true, permission: 'logs.read' },
  },
]
