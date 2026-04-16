export type SupplierOrderStatus = 'draft' | 'confirmed'

export type SupplierOrderSupplier = {
  id: number
  name: string
  number?: string
}

export type SupplierOrder = {
  id: number
  number: string
  order_date: string
  status: SupplierOrderStatus
  total_amount: string
  supplier: SupplierOrderSupplier | null
  items?: Array<{
    id?: number
    article_id: number
    article?: {
      id: number
      name: string
      reference?: string
    } | null
    quantity: string
    cost_price: string
    total: string
  }>
}

export type UpsertSupplierOrderPayload = {
  order_date: string
  supplier_id: number
  status?: SupplierOrderStatus
  items?: Array<{
    article_id: number
    quantity: number
    cost_price: number
    total: number
  }>
}
