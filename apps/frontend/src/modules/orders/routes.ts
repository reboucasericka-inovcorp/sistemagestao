import type { RouteRecordRaw } from 'vue-router'

import ClientOrdersHome from '@/modules/orders/pages/ClientOrdersHome.vue'
import SupplierOrdersHome from '@/modules/orders/pages/SupplierOrdersHome.vue'

export const ordersRoutes: RouteRecordRaw[] = [
  { path: 'client-orders', name: 'orders.client', component: ClientOrdersHome },
  { path: 'supplier-orders', name: 'orders.supplier', component: SupplierOrdersHome },
]
