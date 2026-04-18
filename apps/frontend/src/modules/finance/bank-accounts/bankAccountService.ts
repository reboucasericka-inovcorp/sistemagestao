import api from '@/shared/services/api'

export type BankAccount = {
  id: number
  bank_name: string
  iban: string
  account_holder: string
  is_active: boolean
  created_at?: string
  updated_at?: string
}

export type UpsertBankAccountPayload = {
  bank_name: string
  iban: string
  account_holder: string
  is_active?: boolean
}

export async function listBankAccounts(params?: {
  search?: string
  is_active?: boolean
  page?: number
  per_page?: number
}): Promise<BankAccount[]> {
  const response = await api.get('/bank-accounts', { params })
  return (response.data?.data ?? response.data) as BankAccount[]
}

export async function getBankAccountById(id: number): Promise<BankAccount> {
  const response = await api.get(`/bank-accounts/${id}`)
  return (response.data?.data ?? response.data) as BankAccount
}

export async function createBankAccount(payload: UpsertBankAccountPayload): Promise<BankAccount> {
  const response = await api.post('/bank-accounts', payload)
  return (response.data?.data ?? response.data) as BankAccount
}

export async function updateBankAccount(id: number, payload: UpsertBankAccountPayload): Promise<BankAccount> {
  const response = await api.put(`/bank-accounts/${id}`, payload)
  return (response.data?.data ?? response.data) as BankAccount
}
