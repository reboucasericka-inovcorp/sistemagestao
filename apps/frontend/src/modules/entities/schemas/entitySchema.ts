import { z } from 'zod'

export const entitySchema = z.object({
  nif: z.string().min(1),
  name: z.string().min(1),
  address: z.string().nullable(),
  postal_code: z.string().nullable(),
  city: z.string().nullable(),
  country_id: z.string().nullable(),
  phone: z.string().nullable(),
  mobile: z.string().nullable(),
  website: z.string().nullable(),
  email: z.string().nullable(),
  gdpr_consent: z.boolean(),
  is_active: z.boolean(),
  is_client: z.boolean(),
  is_supplier: z.boolean(),
  notes: z.string().nullable(),
})
