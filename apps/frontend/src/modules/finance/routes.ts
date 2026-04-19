import type { RouteRecordRaw } from 'vue-router'

import BankAccountForm from '@/modules/finance/bank-accounts/BankAccountForm.vue'
import BankAccountsPage from '@/modules/finance/bank-accounts/BankAccountsPage.vue'
import CustomerAccountsPage from '@/modules/finance/customer-accounts/CustomerAccountsPage.vue'
import SupplierInvoiceFormPage from '@/modules/finance/pages/SupplierInvoiceFormPage.vue'
import SupplierInvoicesHome from '@/modules/finance/pages/SupplierInvoicesHome.vue'

export const financeRoutes: RouteRecordRaw[] = [
  {
    path: 'bank-accounts',
    name: 'finance.bankAccounts',
    component: BankAccountsPage,
    meta: { requiresAuth: true, permission: 'bank-accounts.read' },
  },
  {
    path: 'bank-accounts/create',
    name: 'finance.bankAccounts.create',
    component: BankAccountForm,
    meta: { requiresAuth: true, permission: 'bank-accounts.create' },
  },
  {
    path: 'bank-accounts/:id(\\d+)/edit',
    name: 'finance.bankAccounts.edit',
    component: BankAccountForm,
    meta: { requiresAuth: true, permission: 'bank-accounts.update' },
  },
  {
    path: 'customer-accounts',
    name: 'finance.customerAccounts',
    component: CustomerAccountsPage,
    meta: { requiresAuth: true, permission: 'customer-accounts.read' },
  },
  {
    path: 'supplier-invoices',
    name: 'finance.supplierInvoices',
    component: SupplierInvoicesHome,
    meta: { requiresAuth: true, permission: 'supplier-invoices.read' },
  },
  {
    path: 'supplier-invoices/create',
    name: 'finance.supplierInvoices.create',
    component: SupplierInvoiceFormPage,
    meta: { requiresAuth: true, permission: 'supplier-invoices.create' },
  },
  {
    path: 'supplier-invoices/:id(\\d+)/edit',
    name: 'finance.supplierInvoices.edit',
    component: SupplierInvoiceFormPage,
    meta: { requiresAuth: true, permission: 'supplier-invoices.update' },
  },
]
