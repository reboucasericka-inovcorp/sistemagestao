import api from '@/shared/services/api'
import type { UpsertWorkOrderPayload, WorkOrder } from '../types/workOrder'

export async function getWorkOrders(): Promise<WorkOrder[]> {
  const response = await api.get('/work-orders')
  return (response.data?.data ?? response.data) as WorkOrder[]
}

export async function getWorkOrderById(id: number): Promise<WorkOrder> {
  const response = await api.get(`/work-orders/${id}`)
  return (response.data?.data ?? response.data) as WorkOrder
}

export async function createWorkOrder(data: UpsertWorkOrderPayload): Promise<WorkOrder> {
  const response = await api.post('/work-orders', data)
  return (response.data?.data ?? response.data) as WorkOrder
}

export async function updateWorkOrder(id: number, data: UpsertWorkOrderPayload): Promise<WorkOrder> {
  const response = await api.put(`/work-orders/${id}`, data)
  return (response.data?.data ?? response.data) as WorkOrder
}

export async function convertWorkOrderFromClientOrder(id: number): Promise<WorkOrder> {
  const response = await api.post(`/work-orders/from-client-order/${id}`)
  return (response.data?.data ?? response.data) as WorkOrder
}
