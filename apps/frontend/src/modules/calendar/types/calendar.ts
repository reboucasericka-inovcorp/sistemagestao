export type CalendarEvent = {
  id: number
  date: string
  time: string
  duration: number
  entity_id: number
  type_id?: number
  action_id?: number
  description?: string
  is_active: boolean
}

export type CalendarFilters = {
  user_id?: number
  entity_id?: number
}

export type UpsertCalendarEventPayload = {
  date: string
  time: string
  duration: number
  entity_id: number
  type_id?: number | null
  action_id?: number | null
  description?: string | null
  is_active: boolean
}

export type CalendarEventItem = CalendarEvent & {
  user_id?: number | null
  title: string
  start: string
  end: string
  color?: string | null
}

export type CalendarEntityOption = {
  id: number
  name: string
}

export type CalendarUserOption = {
  id: number
  name: string
}

export type CalendarTypeOption = {
  id: number
  name: string
  color?: string | null
}

export type CalendarActionOption = {
  id: number
  name: string
  calendar_type_id?: number | null
}
