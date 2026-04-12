import type { RouteRecordRaw } from 'vue-router'

import CalendarHome from '@/modules/calendar/pages/CalendarHome.vue'

export const calendarRoutes: RouteRecordRaw[] = [
  { path: 'calendar', name: 'calendar.home', component: CalendarHome },
]
