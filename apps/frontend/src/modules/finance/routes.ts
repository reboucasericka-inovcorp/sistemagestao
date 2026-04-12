import type { RouteRecordRaw } from 'vue-router'

import FinanceHome from '@/modules/finance/pages/FinanceHome.vue'
import SupplierInvoicesHome from '@/modules/finance/pages/SupplierInvoicesHome.vue'

export const financeRoutes: RouteRecordRaw[] = [
  { path: 'finance', name: 'finance.home', component: FinanceHome },
  { path: 'supplier-invoices', name: 'finance.supplierInvoices', component: SupplierInvoicesHome },
]
