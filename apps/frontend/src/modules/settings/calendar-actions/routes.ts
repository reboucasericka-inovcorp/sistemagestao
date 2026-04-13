import type { RouteRecordRaw } from 'vue-router'

export const calendarActionsRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/calendar-actions',
    name: 'calendar-actions.index',
    component: () => import('./pages/CalendarActionHome.vue'),
  },
  {
    path: 'settings/calendar-actions/new',
    name: 'calendar-actions.new',
    component: () => import('./pages/CalendarActionFormPage.vue'),
  },
  {
    path: 'settings/calendar-actions/:id/edit',
    name: 'calendar-actions.edit',
    component: () => import('./pages/CalendarActionFormPage.vue'),
  },
]
