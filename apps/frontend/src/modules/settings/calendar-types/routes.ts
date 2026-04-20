import type { RouteRecordRaw } from 'vue-router'

export const calendarTypesRoutes: RouteRecordRaw[] = [
  {
    path: 'settings/calendar-types',
    name: 'calendar-types.index',
    component: () => import('./pages/CalendarTypeHome.vue'),
    meta: { requiresAuth: true, permission: 'calendar-types.read' },
  },
  {
    path: 'settings/calendar-types/new',
    name: 'calendar-types.new',
    component: () => import('./pages/CalendarTypeFormPage.vue'),
    meta: { requiresAuth: true, permission: 'calendar-types.create' },
  },
  {
    path: 'settings/calendar-types/:id/edit',
    name: 'calendar-types.edit',
    component: () => import('./pages/CalendarTypeFormPage.vue'),
    meta: { requiresAuth: true, permission: 'calendar-types.update' },
  },
]
