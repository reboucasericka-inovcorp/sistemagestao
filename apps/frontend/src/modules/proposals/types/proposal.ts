export type ProposalStatus = 'draft' | 'closed'

export type ProposalClient = {
  id: number
  name: string
  number?: string
}

export type Proposal = {
  id: number
  number: string
  proposal_date: string
  valid_until: string
  client: ProposalClient | null
  total_amount: string
  status: ProposalStatus
}

export type UpsertProposalPayload = {
  proposal_date: string
  client_id: number
  status?: ProposalStatus
  items: Array<{
    article_id: number
    supplier_id: number
    quantity: number
    cost_price: number
    total: number
  }>
}
