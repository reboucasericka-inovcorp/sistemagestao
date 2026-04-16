export type WorkOrderStatus = 'draft' | 'in_progress' | 'completed'

export type WorkOrderClient = {
  id: number
  name: string
  number?: string
}

export type WorkOrder = {
  id: number
  number: string
  date: string
  status: WorkOrderStatus
  description: string | null
  total_amount: string
  client: WorkOrderClient | null
  items?: Array<{
    id?: number
    article_id: number
    article?: {
      id: number
      name: string
      reference?: string
    } | null
    quantity: string
    price: string
    total: string
  }>
}

export type UpsertWorkOrderPayload = {
  date: string
  client_id: number
  status?: WorkOrderStatus
  description?: string | null
  items?: Array<{
    article_id: number
    quantity: number
    price: number
    total: number
  }>
}
