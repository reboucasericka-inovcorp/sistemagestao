import api from '@/shared/services/api'
import type { SupplierOrder } from '@/modules/supplier-orders/types/supplierOrder'
import type { SupplierInvoice, UpsertSupplierInvoicePayload } from '../types/supplierInvoice'

export async function listSupplierInvoices(params?: {
  search?: string
  status?: string
  supplier_id?: number
  supplier_order_id?: number
  page?: number
  per_page?: number
}): Promise<SupplierInvoice[]> {
  const response = await api.get('/supplier-invoices', { params })
  return (response.data?.data ?? response.data) as SupplierInvoice[]
}

export async function getSupplierInvoiceById(id: number): Promise<SupplierInvoice> {
  const response = await api.get(`/supplier-invoices/${id}`)
  return (response.data?.data ?? response.data) as SupplierInvoice
}

function buildFormData(payload: UpsertSupplierInvoicePayload): FormData {
  const formData = new FormData()

  if (payload.number) formData.append('number', payload.number)
  formData.append('invoice_date', payload.invoice_date)
  formData.append('due_date', payload.due_date)
  formData.append('supplier_id', String(payload.supplier_id))
  if (payload.supplier_order_id != null) {
    formData.append('supplier_order_id', String(payload.supplier_order_id))
  }
  formData.append('total_amount', String(payload.total_amount))
  formData.append('status', payload.status)
  if (payload.send_payment_receipt_email !== undefined) {
    formData.append('send_payment_receipt_email', payload.send_payment_receipt_email ? '1' : '0')
  }
  if (payload.document_file) {
    formData.append('document_file', payload.document_file)
  }
  if (payload.payment_proof_file) {
    formData.append('payment_proof_file', payload.payment_proof_file)
  }

  return formData
}

export async function createSupplierInvoice(payload: UpsertSupplierInvoicePayload): Promise<SupplierInvoice> {
  const response = await api.post('/supplier-invoices', buildFormData(payload), {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
  return (response.data?.data ?? response.data) as SupplierInvoice
}

export async function updateSupplierInvoice(
  id: number,
  payload: UpsertSupplierInvoicePayload,
): Promise<SupplierInvoice> {
  const formData = buildFormData(payload)
  formData.append('_method', 'PUT')

  const response = await api.post(`/supplier-invoices/${id}`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })

  return (response.data?.data ?? response.data) as SupplierInvoice
}

export async function getSupplierOrdersOptions(): Promise<Array<{ id: number; number: string }>> {
  const response = await api.get('/supplier-orders', {
    params: { per_page: 100 },
  })
  const payload = (response.data?.data ?? response.data) as SupplierOrder[]

  return payload.map((item) => ({ id: item.id, number: item.number }))
}

export function downloadInvoiceFile(invoiceId: number, fileId: number): void {
  const baseUrl = String(api.defaults.baseURL ?? '').replace(/\/+$/, '')
  window.open(`${baseUrl}/supplier-invoices/${invoiceId}/files/${fileId}/download`, '_blank')
}
