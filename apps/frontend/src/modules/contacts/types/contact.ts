/**
 * Modelo alinhado ao `ContactModel` (Laravel) e requisitos em `docs/README.md` / `docs/guide.md`.
 */
export type Contact = {
  id: number
  entity_id: number
  contact_function_id?: number | null
  name: string
  email?: string | null
  phone?: string | null
  mobile?: string | null
  notes?: string | null
  is_active: boolean
}
