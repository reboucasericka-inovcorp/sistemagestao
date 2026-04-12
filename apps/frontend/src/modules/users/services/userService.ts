import api from '@/shared/services/api'
import type { AppUser } from '../types/appUser'

export async function listUsers(): Promise<AppUser[]> {
  const response = await api.get('/users')
  return response.data.data as AppUser[]
}
