export type SupplierInvoiceStatus = 'pending_payment' | 'paid'

export type SupplierInvoiceSupplier = {
  id: number
  name: string
  number?: string | null
  email?: string | null
}

export type SupplierInvoiceOrder = {
  id: number
  number: string
}

export type SupplierInvoiceFile = {
  id: number
  name: string
  category: 'supplier-invoice-document' | 'supplier-invoice-payment-proof'
  download_url: string
  created_at?: string
}

export type SupplierInvoice = {
  id: number
  number: string
  invoice_date: string
  due_date: string
  status: SupplierInvoiceStatus
  total_amount: string
  supplier: SupplierInvoiceSupplier | null
  supplier_order: SupplierInvoiceOrder | null
  files: SupplierInvoiceFile[]
}

export type UpsertSupplierInvoicePayload = {
  number?: string
  invoice_date: string
  due_date: string
  supplier_id: number
  supplier_order_id?: number | null
  total_amount: number
  status: SupplierInvoiceStatus
  send_payment_receipt_email?: boolean
  document_file?: File | null
  payment_proof_file?: File | null
}
