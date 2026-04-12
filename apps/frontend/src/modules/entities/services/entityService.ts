import api from '@/shared/services/api'
import type { Entity } from '../types/entity'

export type ListEntitiesQuery = {
  clients?: boolean
  suppliers?: boolean
  active_only?: boolean
}

export async function listEntities(query?: ListEntitiesQuery): Promise<Entity[]> {
  const response = await api.get('/entities', {
    params: {
      ...(query?.clients ? { clients: 1 } : {}),
      ...(query?.suppliers ? { suppliers: 1 } : {}),
      ...(query?.active_only ? { active_only: 1 } : {}),
    },
  })
  return response.data.data as Entity[]
}
