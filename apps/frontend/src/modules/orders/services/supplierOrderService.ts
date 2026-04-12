import api from '@/shared/services/api'
import type { SupplierOrder } from '../types/supplierOrder'

export async function listSupplierOrders(): Promise<SupplierOrder[]> {
  const response = await api.get('/supplier-orders')
  return response.data.data as SupplierOrder[]
}
