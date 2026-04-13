export type CalendarAction = {
  id: number
  name: string
  calendar_type_id?: number | null
  calendar_type?: {
    id: number
    name: string
  } | null
  is_active: boolean
}
