/**
 * Modelo alinhado ao `ContactModel` (Laravel) e requisitos em `docs/README.md` / `docs/guide.md`.
 */
export type Contact = {
  id: number
  number: string
  first_name: string
  last_name: string
  full_name: string
  email: string | null
  phone: string | null
  mobile: string | null
  rgpd_consent: boolean
  is_active: boolean
  notes?: string | null
  entity?: {
    id: number
    name: string
  } | null
  function?: {
    id: number
    name: string
  } | null
}
