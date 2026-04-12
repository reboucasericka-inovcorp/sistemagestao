import api from '@/shared/services/api'
import type { SupplierInvoice } from '../types/supplierInvoice'

export async function listSupplierInvoices(): Promise<SupplierInvoice[]> {
  const response = await api.get('/supplier-invoices')
  return response.data.data as SupplierInvoice[]
}
