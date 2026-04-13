/**
 * Modelo alinhado ao `ContactModel` (Laravel) e requisitos em `docs/README.md` / `docs/guide.md`.
 */
export type Contact = {
  id: number
  number?: string | null
  entity_id: number
  contact_function_id?: number | null
  name: string
  email?: string | null
  phone?: string | null
  mobile?: string | null
  notes?: string | null
  is_active: boolean
  entity?: {
    id: number
    name: string
  } | null
  contactFunction?: {
    id: number
    name: string
  } | null
}
