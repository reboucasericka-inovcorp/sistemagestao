import api from '@/shared/services/api'
import type { Contact } from '../types/contact'

export type ListContactsQuery = {
  entity_id?: number
  active_only?: boolean
  search?: string
}

export async function listContacts(query?: ListContactsQuery): Promise<Contact[]> {
  const response = await api.get('/contacts', {
    params: {
      ...(query?.entity_id != null ? { entity_id: query.entity_id } : {}),
      ...(query?.active_only ? { active_only: 1 } : {}),
      ...(query?.search ? { search: query.search } : {}),
    },
  })
  return response.data.data as Contact[]
}
