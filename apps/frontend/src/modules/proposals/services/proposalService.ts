import api from '@/shared/services/api'
import type { Proposal } from '../types/proposal'

export async function listProposals(): Promise<Proposal[]> {
  const response = await api.get('/proposals')
  return response.data.data as Proposal[]
}
