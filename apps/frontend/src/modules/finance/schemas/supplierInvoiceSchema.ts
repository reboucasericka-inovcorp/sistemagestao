import { z } from 'zod'

export const supplierInvoiceSchema = z.object({
  number: z.string().min(1, 'Número é obrigatório'),
  invoice_date: z.string().min(1, 'Data da fatura é obrigatória'),
  due_date: z.string().min(1, 'Data de vencimento é obrigatória'),
  supplier_id: z.string().min(1, 'Fornecedor é obrigatório'),
  supplier_order_id: z.string().optional(),
  total_amount: z.string().min(1, 'Valor total é obrigatório'),
  status: z.enum(['pending_payment', 'paid']),
  document_file: z.any().optional(),
  payment_proof_file: z.any().optional(),
})

export type SupplierInvoiceFormSchema = z.infer<typeof supplierInvoiceSchema>
