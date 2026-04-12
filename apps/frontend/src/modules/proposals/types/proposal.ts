/** Estados: draft → closed (`docs/guide.md` §6 Propostas). */
export type ProposalState = 'draft' | 'closed'

export type Proposal = {
  id: number
  number: string
  state: ProposalState
  client_entity_id: number
  valid_until: string
}
