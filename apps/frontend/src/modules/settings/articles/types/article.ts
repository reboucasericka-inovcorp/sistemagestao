/** Catálogo — campos em `docs/guide.md` §6 (Artigos) / `docs/README.md`. */
export type Article = {
  id: number
  reference: string
  name: string
  description?: string | null
  price: string
  vat_id?: number | null
  vat?: {
    id: number
    name: string
    rate: string
  } | null
  photo_url?: string | null
  notes?: string | null
  is_active: boolean
}
