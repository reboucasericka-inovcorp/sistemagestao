/** Campos mínimos do calendário (`docs/guide.md` §6 Calendário). */
export type CalendarEvent = {
  id: number
  starts_at: string
  duration_minutes: number
  entity_id?: number | null
  event_type_id?: number | null
  action_id?: number | null
  description?: string | null
  is_active: boolean
}
