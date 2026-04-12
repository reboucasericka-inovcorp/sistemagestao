/** Faturas fornecedor — pending → paid (`docs/guide.md`). */
export type SupplierInvoiceState = 'pending' | 'paid'

export type SupplierInvoice = {
  id: number
  number: string
  state: SupplierInvoiceState
  supplier_entity_id: number
  total_amount: number
}
