import api from '@/shared/services/api'
import type { PermissionGroup } from '../types/permissionGroup'

export async function listPermissionGroups(): Promise<PermissionGroup[]> {
  const response = await api.get('/permission-groups')
  return response.data.data as PermissionGroup[]
}
