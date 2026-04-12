/** Utilizadores — gestão de acessos (`docs/README.md`). */
export type AppUser = {
  id: number
  name: string
  email: string
  mobile?: string | null
  is_active: boolean
}
