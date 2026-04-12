import api from '@/shared/services/api'
import type { CalendarEvent } from '../types/calendarEvent'

export async function listCalendarEvents(): Promise<CalendarEvent[]> {
  const response = await api.get('/calendar-events')
  return response.data.data as CalendarEvent[]
}
