import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import api from '@/shared/services/api'

export type DigitalFileItem = {
  id: number
  name: string
  category?: string | null
  description?: string | null
  created_at?: string | null
}

export type ListDigitalFilesQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type DigitalFilesListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListDigitalFilesResult = {
  data: DigitalFileItem[]
  meta: DigitalFilesListMeta
}

export async function getDigitalFiles(params?: ListDigitalFilesQuery): Promise<ListDigitalFilesResult> {
  const response = await api.get(API_ROUTES.digitalFiles, { params })
  const normalized = normalizeListResponse(response.data) as {
    data: DigitalFileItem[]
    meta?: Partial<DigitalFilesListMeta> | null
  }
  const data = normalized.data

  return {
    data,
    meta: {
      current_page: normalized.meta?.current_page ?? 1,
      per_page: normalized.meta?.per_page ?? (data.length || 15),
      total: normalized.meta?.total ?? data.length,
      last_page: normalized.meta?.last_page ?? 1,
    },
  }
}

export function uploadDigitalFile(formData: FormData) {
  return api.post(API_ROUTES.digitalFiles, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}

export function deleteDigitalFile(id: number) {
  return api.delete(`${API_ROUTES.digitalFiles}/${id}`)
}

export function downloadDigitalFile(id: number): void {
  const baseUrl = String(api.defaults.baseURL ?? '').replace(/\/+$/, '')
  window.open(`${baseUrl}${API_ROUTES.digitalFiles}/${id}/download`, '_blank')
}
