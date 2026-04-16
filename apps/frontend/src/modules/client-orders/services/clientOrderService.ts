import api from '@/shared/services/api'
import type { UpsertClientOrderPayload, ClientOrder } from '../types/clientOrder'

export async function getClientOrders(): Promise<ClientOrder[]> {
  const response = await api.get('/client-orders')
  return (response.data?.data ?? response.data) as ClientOrder[]
}

export async function getClientOrderById(id: number): Promise<ClientOrder> {
  const response = await api.get(`/client-orders/${id}`)
  return (response.data?.data ?? response.data) as ClientOrder
}

export async function createClientOrder(data: UpsertClientOrderPayload): Promise<ClientOrder> {
  const response = await api.post('/client-orders', data)
  return (response.data?.data ?? response.data) as ClientOrder
}

export async function updateClientOrder(id: number, data: UpsertClientOrderPayload): Promise<ClientOrder> {
  const response = await api.put(`/client-orders/${id}`, data)
  return (response.data?.data ?? response.data) as ClientOrder
}

export async function convertClientOrderToSupplierOrders(id: number): Promise<unknown> {
  const response = await api.post(`/client-orders/${id}/convert-suppliers`)
  return response.data?.data ?? response.data
}
