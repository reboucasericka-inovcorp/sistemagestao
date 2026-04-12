/** Dados institucionais / branding (`docs/README.md` — Empresa). */
export type CompanyProfile = {
  legal_name: string
  address: string
  postal_code: string
  city: string
  vat_number: string
  logo_url?: string | null
}
