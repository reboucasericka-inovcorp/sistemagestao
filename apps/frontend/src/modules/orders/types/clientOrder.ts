/** Encomendas de cliente — estados draft → closed (`docs/guide.md`). */
export type ClientOrderState = 'draft' | 'closed'

export type ClientOrder = {
  id: number
  number: string
  state: ClientOrderState
  client_entity_id: number
}
