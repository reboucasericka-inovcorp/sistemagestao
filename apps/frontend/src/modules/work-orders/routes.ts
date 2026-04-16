import type { RouteRecordRaw } from 'vue-router'

import WorkOrderForm from '@/modules/work-orders/components/WorkOrderForm.vue'
import WorkOrdersPage from '@/modules/work-orders/pages/WorkOrdersPage.vue'

export const workOrdersRoutes: RouteRecordRaw[] = [
  { path: 'work-orders', name: 'work-orders.index', component: WorkOrdersPage },
  { path: 'work-orders/create', name: 'work-orders.create', component: WorkOrderForm },
  { path: 'work-orders/:id(\\d+)/edit', name: 'work-orders.edit', component: WorkOrderForm },
]
