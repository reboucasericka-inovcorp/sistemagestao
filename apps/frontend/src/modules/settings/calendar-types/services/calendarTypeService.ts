import api from '@/shared/services/api'
import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { CalendarType } from '../types/calendarType'

export type ListCalendarTypesQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type CalendarTypesListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListCalendarTypesResult = {
  data: CalendarType[]
  meta: CalendarTypesListMeta
}

export type UpsertCalendarTypePayload = {
  name: string
  color?: string | null
  is_active: boolean
}

export async function listCalendarTypesResult(
  query?: ListCalendarTypesQuery,
): Promise<ListCalendarTypesResult> {
  const response = await api.get(API_ROUTES.calendarTypes, {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  const normalized = normalizeListResponse(response.data) as {
    data: CalendarType[]
    meta?: Partial<CalendarTypesListMeta> | null
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

export async function getCalendarTypeById(id: number): Promise<CalendarType> {
  const response = await api.get(`${API_ROUTES.calendarTypes}/${id}`)
  return response.data.data as CalendarType
}

export async function createCalendarType(payload: UpsertCalendarTypePayload): Promise<CalendarType> {
  const response = await api.post(API_ROUTES.calendarTypes, payload)
  return response.data.data as CalendarType
}

export async function updateCalendarType(
  id: number,
  payload: Partial<UpsertCalendarTypePayload>,
): Promise<CalendarType> {
  const response = await api.put(`${API_ROUTES.calendarTypes}/${id}`, payload)
  return response.data.data as CalendarType
}

export async function inactivateCalendarType(id: number): Promise<CalendarType> {
  const response = await api.delete(`${API_ROUTES.calendarTypes}/${id}`)
  return response.data.data as CalendarType
}

export async function toggleCalendarTypeStatus(
  id: number,
  isCurrentlyActive: boolean,
): Promise<CalendarType> {
  if (isCurrentlyActive) {
    return inactivateCalendarType(id)
  }
  return updateCalendarType(id, { is_active: true })
}
