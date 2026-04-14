import type { RouteRecordRaw } from 'vue-router'

import CalendarPage from '@/modules/calendar/pages/CalendarPage.vue'

export const calendarRoutes: RouteRecordRaw[] = [
  { path: 'calendar', name: 'calendar.home', component: CalendarPage },
]
