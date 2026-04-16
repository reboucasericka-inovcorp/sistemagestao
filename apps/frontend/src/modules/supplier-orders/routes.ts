import type { RouteRecordRaw } from 'vue-router'

import SupplierOrderForm from '@/modules/supplier-orders/components/SupplierOrderForm.vue'
import SupplierOrdersPage from '@/modules/supplier-orders/pages/SupplierOrdersPage.vue'

export const supplierOrdersRoutes: RouteRecordRaw[] = [
  { path: 'supplier-orders', name: 'supplier-orders.index', component: SupplierOrdersPage },
  { path: 'supplier-orders/create', name: 'supplier-orders.create', component: SupplierOrderForm },
  { path: 'supplier-orders/:id(\\d+)/edit', name: 'supplier-orders.edit', component: SupplierOrderForm },
]
