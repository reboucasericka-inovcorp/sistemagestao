import api from '@/shared/services/api'
import type { SupplierOrder, UpsertSupplierOrderPayload } from '../types/supplierOrder'

export async function getSupplierOrders(): Promise<SupplierOrder[]> {
  const response = await api.get('/supplier-orders')
  return (response.data?.data ?? response.data) as SupplierOrder[]
}

export async function getSupplierOrderById(id: number): Promise<SupplierOrder> {
  const response = await api.get(`/supplier-orders/${id}`)
  return (response.data?.data ?? response.data) as SupplierOrder
}

export async function createSupplierOrder(data: UpsertSupplierOrderPayload): Promise<SupplierOrder> {
  const response = await api.post('/supplier-orders', data)
  return (response.data?.data ?? response.data) as SupplierOrder
}

export async function updateSupplierOrder(id: number, data: UpsertSupplierOrderPayload): Promise<SupplierOrder> {
  const response = await api.put(`/supplier-orders/${id}`, data)
  return (response.data?.data ?? response.data) as SupplierOrder
}
