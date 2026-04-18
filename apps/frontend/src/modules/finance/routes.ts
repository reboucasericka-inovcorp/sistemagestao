import type { RouteRecordRaw } from 'vue-router'

import BankAccountForm from '@/modules/finance/bank-accounts/BankAccountForm.vue'
import BankAccountsPage from '@/modules/finance/bank-accounts/BankAccountsPage.vue'
import CustomerAccountsPage from '@/modules/finance/customer-accounts/CustomerAccountsPage.vue'
import SupplierInvoiceFormPage from '@/modules/finance/pages/SupplierInvoiceFormPage.vue'
import SupplierInvoicesHome from '@/modules/finance/pages/SupplierInvoicesHome.vue'

export const financeRoutes: RouteRecordRaw[] = [
  { path: 'bank-accounts', name: 'finance.bankAccounts', component: BankAccountsPage },
  { path: 'bank-accounts/create', name: 'finance.bankAccounts.create', component: BankAccountForm },
  { path: 'bank-accounts/:id(\\d+)/edit', name: 'finance.bankAccounts.edit', component: BankAccountForm },
  { path: 'customer-accounts', name: 'finance.customerAccounts', component: CustomerAccountsPage },
  { path: 'supplier-invoices', name: 'finance.supplierInvoices', component: SupplierInvoicesHome },
  { path: 'supplier-invoices/create', name: 'finance.supplierInvoices.create', component: SupplierInvoiceFormPage },
  {
    path: 'supplier-invoices/:id(\\d+)/edit',
    name: 'finance.supplierInvoices.edit',
    component: SupplierInvoiceFormPage,
  },
]
