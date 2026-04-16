export type ClientOrderStatus = 'draft' | 'closed'

export type ClientOrderClient = {
  id: number
  name: string
  number?: string
}

export type ClientOrder = {
  id: number
  number: string
  order_date: string
  status: ClientOrderStatus
  total_amount: string
  client: ClientOrderClient | null
  items?: Array<{
    id?: number
    article_id: number
    article?: {
      id: number
      name: string
      reference?: string
    } | null
    supplier_id: number
    supplier?: {
      id: number
      name: string
      number?: string
    } | null
    quantity: string
    cost_price: string
    total: string
  }>
}

export type UpsertClientOrderPayload = {
  order_date: string
  client_id: number
  status?: ClientOrderStatus
  items?: Array<{
    article_id: number
    supplier_id: number
    quantity: number
    cost_price: number
    total: number
  }>
}
