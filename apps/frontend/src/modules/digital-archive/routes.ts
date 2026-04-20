import type { RouteRecordRaw } from 'vue-router'

export const digitalArchiveRoutes: RouteRecordRaw[] = [
  {
    path: 'digital-archive',
    name: 'digital-archive.index',
    component: () => import('./pages/DigitalFilesPage.vue'),
    meta: { requiresAuth: true, permission: 'digital-files.read' },
  },
]
