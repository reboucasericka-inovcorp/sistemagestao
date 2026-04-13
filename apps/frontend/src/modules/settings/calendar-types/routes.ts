import type { RouteRecordRaw } from 'vue-router'

export const calendarTypesRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/calendar-types',
    name: 'calendar-types.index',
    component: () => import('./pages/CalendarTypeHome.vue'),
  },
  {
    path: 'settings/calendar-types/new',
    name: 'calendar-types.new',
    component: () => import('./pages/CalendarTypeFormPage.vue'),
  },
  {
    path: 'settings/calendar-types/:id/edit',
    name: 'calendar-types.edit',
    component: () => import('./pages/CalendarTypeFormPage.vue'),
  },
]
