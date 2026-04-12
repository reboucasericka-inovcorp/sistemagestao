import api from '@/shared/services/api'
import type { ClientOrder } from '../types/clientOrder'

export async function listClientOrders(): Promise<ClientOrder[]> {
  const response = await api.get('/client-orders')
  return response.data.data as ClientOrder[]
}
