import { API_ROUTES } from '@/core/api/apiRoutes'
import { normalizeListResponse } from '@/core/utils/normalizeResponse'
import api from '@/shared/services/api'
import type {
  CalendarActionOption,
  CalendarEntityOption,
  CalendarEvent,
  CalendarEventItem,
  CalendarFilters,
  CalendarTypeOption,
  CalendarUserOption,
  UpsertCalendarEventPayload,
} from '@/modules/calendar/types/calendar'

type RawCalendarEvent = Record<string, unknown>

function toIsoDate(value: unknown): string {
  const parsed = String(value ?? '').trim()
  if (/^\d{4}-\d{2}-\d{2}$/.test(parsed)) {
    return parsed
  }
  const date = new Date(parsed)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toISOString().slice(0, 10)
}

function toTime(value: unknown): string {
  const parsed = String(value ?? '').trim()
  if (/^\d{2}:\d{2}/.test(parsed)) {
    return parsed.slice(0, 5)
  }
  const date = new Date(parsed)
  if (Number.isNaN(date.getTime())) {
    return ''
  }
  return date.toISOString().slice(11, 16)
}

function addMinutes(isoStart: string, minutes: number): string {
  const start = new Date(isoStart)
  if (Number.isNaN(start.getTime())) {
    return isoStart
  }
  start.setMinutes(start.getMinutes() + Math.max(minutes, 0))
  return start.toISOString()
}

function normalizeEvent(item: RawCalendarEvent): CalendarEventItem {
  const startsAt = String(item.starts_at ?? item.start_at ?? '').trim()
  const date = toIsoDate(item.date ?? startsAt)
  const time = toTime(item.time ?? startsAt)
  const duration = Number(item.duration ?? item.duration_minutes ?? 0)

  const start = startsAt || (date && time ? `${date}T${time}:00` : '')
  const end = start ? addMinutes(start, duration) : start
  const title = String(
    item.title ??
      item.entity_name ??
      item.action_name ??
      item.type_name ??
      item.description ??
      `Evento #${String(item.id ?? '')}`,
  )

  return {
    id: Number(item.id ?? 0),
    date,
    time,
    duration: Math.max(duration, 0),
    entity_id: Number(item.entity_id ?? 0),
    type_id: item.type_id != null ? Number(item.type_id) : undefined,
    action_id: item.action_id != null ? Number(item.action_id) : undefined,
    description: item.description != null ? String(item.description) : undefined,
    is_active: Boolean(item.is_active ?? true),
    user_id: item.user_id != null ? Number(item.user_id) : undefined,
    title,
    start,
    end,
    color: item.color != null ? String(item.color) : undefined,
  }
}

export async function listEvents(filters?: CalendarFilters): Promise<CalendarEventItem[]> {
  const response = await api.get(API_ROUTES.calendarEvents, {
    params: {
      ...(filters?.user_id ? { user_id: filters.user_id } : {}),
      ...(filters?.entity_id ? { entity_id: filters.entity_id } : {}),
    },
  })
  const normalized = normalizeListResponse(response.data) as { data: RawCalendarEvent[] }
  return normalized.data
    .map(normalizeEvent)
    .filter((eventItem) => eventItem.is_active)
}

export async function createEvent(payload: UpsertCalendarEventPayload): Promise<CalendarEvent> {
  const response = await api.post(API_ROUTES.calendarEvents, payload)
  return response.data.data as CalendarEvent
}

export async function updateEvent(id: number, payload: Partial<UpsertCalendarEventPayload>): Promise<CalendarEvent> {
  const response = await api.put(`${API_ROUTES.calendarEvents}/${id}`, payload)
  return response.data.data as CalendarEvent
}

export async function deleteCalendarEvent(id: number): Promise<void> {
  await api.delete(`${API_ROUTES.calendarEvents}/${id}`)
}

export async function listEntityOptions(): Promise<CalendarEntityOption[]> {
  const response = await api.get(API_ROUTES.entities, { params: { active_only: 1, per_page: 100 } })
  const normalized = normalizeListResponse(response.data) as { data: CalendarEntityOption[] }
  return normalized.data.map((item) => ({ id: Number(item.id), name: String(item.name) }))
}

export async function listUserOptions(): Promise<CalendarUserOption[]> {
  const response = await api.get(API_ROUTES.users, { params: { active_only: 1, per_page: 100 } })
  const normalized = normalizeListResponse(response.data) as { data: CalendarUserOption[] }
  return normalized.data.map((item) => ({ id: Number(item.id), name: String(item.name) }))
}

export async function listTypeOptions(): Promise<CalendarTypeOption[]> {
  const response = await api.get(API_ROUTES.calendarTypes, { params: { active_only: 1, per_page: 100 } })
  const normalized = normalizeListResponse(response.data) as { data: CalendarTypeOption[] }
  return normalized.data.map((item) => ({
    id: Number(item.id),
    name: String(item.name),
    color: item.color != null ? String(item.color) : null,
  }))
}

export async function listActionOptions(): Promise<CalendarActionOption[]> {
  const response = await api.get(API_ROUTES.calendarActions, { params: { active_only: 1, per_page: 100 } })
  const normalized = normalizeListResponse(response.data) as { data: CalendarActionOption[] }
  return normalized.data.map((item) => ({
    id: Number(item.id),
    name: String(item.name),
    calendar_type_id: item.calendar_type_id != null ? Number(item.calendar_type_id) : null,
  }))
}
