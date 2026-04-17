import type { RouteRecordRaw } from 'vue-router'

import BankAccountsHome from '@/modules/finance/pages/BankAccountsHome.vue'
import CustomerAccountsHome from '@/modules/finance/pages/CustomerAccountsHome.vue'
import SupplierInvoiceFormPage from '@/modules/finance/pages/SupplierInvoiceFormPage.vue'
import SupplierInvoicesHome from '@/modules/finance/pages/SupplierInvoicesHome.vue'

export const financeRoutes: RouteRecordRaw[] = [
  { path: 'bank-accounts', name: 'finance.bankAccounts', component: BankAccountsHome },
  { path: 'customer-accounts', name: 'finance.customerAccounts', component: CustomerAccountsHome },
  { path: 'supplier-invoices', name: 'finance.supplierInvoices', component: SupplierInvoicesHome },
  { path: 'supplier-invoices/create', name: 'finance.supplierInvoices.create', component: SupplierInvoiceFormPage },
  {
    path: 'supplier-invoices/:id(\\d+)/edit',
    name: 'finance.supplierInvoices.edit',
    component: SupplierInvoiceFormPage,
  },
]
