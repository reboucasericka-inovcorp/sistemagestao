export type Company = {
  name: string
  address?: string | null
  postal_code?: string | null
  city?: string | null
  tax_number: string
  logo_url?: string | null
}

export type UpsertCompanyPayload = {
  name: string
  address?: string | null
  postal_code?: string | null
  city?: string | null
  tax_number: string
  logo?: File | null
}
