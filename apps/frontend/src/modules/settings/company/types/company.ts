export type Company = {
  id: number
  name: string
  tax_number: string
  address?: string | null
  postal_code?: string | null
  city?: string | null
  country_id?: number | null
  phone?: string | null
  mobile?: string | null
  email?: string | null
  website?: string | null
  logo?: string | null
  logo_url?: string | null
  is_active: boolean
}

export type UpsertCompanyPayload = {
  name: string
  tax_number: string
  address?: string | null
  postal_code?: string | null
  city?: string | null
  country_id?: number | null
  phone?: string | null
  mobile?: string | null
  email?: string | null
  website?: string | null
  is_active: boolean
  logo?: File | null
}
