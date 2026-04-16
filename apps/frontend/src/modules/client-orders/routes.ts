import type { RouteRecordRaw } from 'vue-router'

import ClientOrderForm from '@/modules/client-orders/components/ClientOrderForm.vue'
import ClientOrdersPage from '@/modules/client-orders/pages/ClientOrdersPage.vue'

export const clientOrdersRoutes: RouteRecordRaw[] = [
  { path: 'client-orders', name: 'client-orders.index', component: ClientOrdersPage },
  { path: 'client-orders/create', name: 'client-orders.create', component: ClientOrderForm },
  { path: 'client-orders/:id(\\d+)/edit', name: 'client-orders.edit', component: ClientOrderForm },
]
