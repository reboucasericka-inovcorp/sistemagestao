export type AccessUser = {
  id: number
  name: string
  email: string
  phone?: string | null
  role?: {
    id: number
    name: string
  } | null
  is_active: boolean
}

export type UpsertAccessUserPayload = {
  name: string
  email: string
  phone?: string | null
  role_id: number
  is_active: boolean
}
