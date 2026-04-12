import type { RouteRecordRaw } from 'vue-router'

import LogsHome from '@/modules/logs/pages/LogsHome.vue'

export const logsRoutes: RouteRecordRaw[] = [{ path: 'logs', name: 'logs.home', component: LogsHome }]
