/** Catálogo — campos em `docs/guide.md` §6 (Artigos) / `docs/README.md`. */
export type Article = {
  id: number
  reference: string
  name: string
  description?: string | null
  price: number
  vat_percent: number
  photo_url?: string | null
  is_active: boolean
}
