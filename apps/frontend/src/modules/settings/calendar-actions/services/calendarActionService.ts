import api from '@/shared/services/api'
import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import type { CalendarAction } from '../types/calendarAction'

export type CalendarTypeOption = {
  id: number
  name: string
}

export type ListCalendarActionsQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type CalendarActionsListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListCalendarActionsResult = {
  data: CalendarAction[]
  meta: CalendarActionsListMeta
}

export type UpsertCalendarActionPayload = {
  name: string
  calendar_type_id?: number | null
  is_active: boolean
}

export async function listCalendarActionsResult(
  query?: ListCalendarActionsQuery,
): Promise<ListCalendarActionsResult> {
  const response = await api.get(API_ROUTES.calendarActions, {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  const normalized = normalizeListResponse(response) as {
    data: CalendarAction[]
    meta?: Partial<CalendarActionsListMeta> | null
  }
  const data = Array.isArray(normalized.data) ? normalized.data : []
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

export async function getCalendarActionById(id: number): Promise<CalendarAction> {
  const response = await api.get(`${API_ROUTES.calendarActions}/${id}`)
  return response.data.data as CalendarAction
}

export async function createCalendarAction(payload: UpsertCalendarActionPayload): Promise<CalendarAction> {
  const response = await api.post(API_ROUTES.calendarActions, payload)
  return response.data.data as CalendarAction
}

export async function updateCalendarAction(
  id: number,
  payload: Partial<UpsertCalendarActionPayload>,
): Promise<CalendarAction> {
  const response = await api.put(`${API_ROUTES.calendarActions}/${id}`, payload)
  return response.data.data as CalendarAction
}

export async function inactivateCalendarAction(id: number): Promise<CalendarAction> {
  const response = await api.delete(`${API_ROUTES.calendarActions}/${id}`)
  return response.data.data as CalendarAction
}

export async function toggleCalendarActionStatus(
  id: number,
  isCurrentlyActive: boolean,
): Promise<CalendarAction> {
  if (isCurrentlyActive) {
    return inactivateCalendarAction(id)
  }
  return updateCalendarAction(id, { is_active: true })
}

export async function listCalendarTypeOptions(): Promise<CalendarTypeOption[]> {
  try {
    const response = await api.get(API_ROUTES.calendarTypes, { params: { active_only: 1, per_page: 100 } })
    const normalized = normalizeListResponse(response) as { data: CalendarTypeOption[] }
    return Array.isArray(normalized.data) ? normalized.data : []
  } catch {
    // calendar_type_id relation is optional.
    return []
  }
}
