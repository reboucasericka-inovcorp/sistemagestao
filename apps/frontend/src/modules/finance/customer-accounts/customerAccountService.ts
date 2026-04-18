import api from '@/shared/services/api'

export type CustomerAccountMovement = {
  id: number
  customer_account_id: number
  type: 'debit' | 'credit'
  amount: string
  description?: string | null
  date: string
}

export type CustomerAccount = {
  id: number
  entity_id: number
  balance: string
  entity: {
    id: number
    name: string
    number?: string | null
    email?: string | null
  } | null
}

export type CreateMovementPayload = {
  type: 'debit' | 'credit'
  amount: number
  description?: string
  date: string
}

export async function listCustomerAccounts(params?: {
  search?: string
  page?: number
  per_page?: number
}): Promise<CustomerAccount[]> {
  const response = await api.get('/customer-accounts', { params })
  return (response.data?.data ?? response.data) as CustomerAccount[]
}

export async function listCustomerAccountMovements(entityId: number, params?: {
  page?: number
  per_page?: number
}): Promise<CustomerAccountMovement[]> {
  const response = await api.get(`/customer-accounts/${entityId}/movements`, { params })
  return (response.data?.data ?? response.data) as CustomerAccountMovement[]
}

export async function createCustomerAccountMovement(
  entityId: number,
  payload: CreateMovementPayload,
): Promise<void> {
  await api.post(`/customer-accounts/${entityId}/movements`, payload)
}
