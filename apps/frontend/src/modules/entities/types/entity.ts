export type EntityCountry = {
  id: number
  name: string
}

export type Entity = {
  id: number
  number: string
  nif: string
  name: string
  address: string | null
  postal_code: string | null
  city: string | null
  country: EntityCountry | null
  phone: string | null
  mobile: string | null
  website: string | null
  email: string | null
  gdpr_consent: boolean
  is_active: boolean
  notes: string | null
  type: 'client' | 'supplier' | 'both'
}
