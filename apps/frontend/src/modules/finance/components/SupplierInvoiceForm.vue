<script setup lang="ts">
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { Button } from '@/components/ui/button'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { handleApiError } from '@/shared/utils/handleApiError'
import { getSupplierOptions } from '@/modules/proposals/services/proposalService'
import type { SupplierOption } from '@/modules/proposals/services/proposalService'
import {
  createSupplierInvoice,
  getSupplierInvoiceById,
  getSupplierOrdersOptions,
  updateSupplierInvoice,
} from '@/modules/finance/services/supplierInvoiceService'
import type { UpsertSupplierInvoicePayload } from '@/modules/finance/types/supplierInvoice'
import type { SupplierInvoiceFile } from '@/modules/finance/types/supplierInvoice'
import { supplierInvoiceSchema, type SupplierInvoiceFormSchema } from '@/modules/finance/schemas/supplierInvoiceSchema'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.params.id === undefined)
const invoiceId = computed(() => Number(route.params.id))
const loading = ref(false)
const submitting = ref(false)
const feedback = ref('')
const supplierOptions = ref<SupplierOption[]>([])
const supplierOrderOptions = ref<Array<{ id: number; number: string }>>([])
const selectedStatus = ref<'pending_payment' | 'paid'>('pending_payment')
const existingFiles = ref<SupplierInvoiceFile[]>([])
const categoryMap: Record<string, string> = {
  'supplier-invoice-document': 'Documento',
  'supplier-invoice-payment-proof': 'Comprovativo de Pagamento',
}

const { setValues, setFieldValue, setErrors } = useForm<SupplierInvoiceFormSchema>({
  validationSchema: toTypedSchema(supplierInvoiceSchema),
  initialValues: {
    number: '',
    invoice_date: new Date().toISOString().slice(0, 10),
    due_date: new Date().toISOString().slice(0, 10),
    supplier_id: '',
    supplier_order_id: '',
    total_amount: '0.00',
    status: 'pending_payment',
    document_file: undefined,
    payment_proof_file: undefined,
  },
})

async function hydrate(): Promise<void> {
  if (isNew.value) {
    return
  }

  const invoice = await getSupplierInvoiceById(invoiceId.value)
  selectedStatus.value = invoice.status
  existingFiles.value = invoice.files ?? []

  setValues({
    number: invoice.number ?? '',
    invoice_date: invoice.invoice_date ?? '',
    due_date: invoice.due_date ?? '',
    supplier_id: invoice.supplier?.id ? String(invoice.supplier.id) : '',
    supplier_order_id: invoice.supplier_order?.id ? String(invoice.supplier_order.id) : '',
    total_amount: String(invoice.total_amount ?? '0.00'),
    status: invoice.status,
    document_file: undefined,
    payment_proof_file: undefined,
  })
}

function onDocumentChange(event: Event): void {
  const input = event.target as HTMLInputElement
  setFieldValue('document_file', input.files?.[0] ?? undefined)
}

function onPaymentReceiptChange(event: Event): void {
  const input = event.target as HTMLInputElement
  setFieldValue('payment_proof_file', input.files?.[0] ?? undefined)
}

function downloadFile(fileId: number): void {
  const baseUrl = String(import.meta.env.VITE_BACKEND_URL ?? 'http://127.0.0.1:8000').replace(/\/+$/, '')
  window.open(`${baseUrl}/api/v1/digital-files/${fileId}/download`, '_blank')
}

const onSubmit = async (values: SupplierInvoiceFormSchema) => {
  submitting.value = true
  feedback.value = ''
  try {
    setErrors({})

    let sendPaymentReceiptEmail = false
    if (values.status === 'paid') {
      sendPaymentReceiptEmail = window.confirm('Pretende enviar o comprovativo ao Fornecedor?')
    }

    const payload: UpsertSupplierInvoicePayload = {
      number: values.number,
      invoice_date: values.invoice_date,
      due_date: values.due_date,
      supplier_id: Number(values.supplier_id),
      supplier_order_id: values.supplier_order_id ? Number(values.supplier_order_id) : null,
      total_amount: Number(values.total_amount),
      status: values.status,
      send_payment_receipt_email: sendPaymentReceiptEmail,
      document_file: values.document_file ?? null,
      payment_proof_file: values.payment_proof_file ?? null,
    }

    if (isNew.value) {
      await createSupplierInvoice(payload)
      feedback.value = 'Fatura criada com sucesso.'
      await router.push('/supplier-invoices')
      return
    }

    await updateSupplierInvoice(invoiceId.value, payload)
    feedback.value = 'Fatura atualizada com sucesso.'
    await hydrate()
    setFieldValue('document_file', undefined)
    setFieldValue('payment_proof_file', undefined)
  } catch (e: unknown) {
    handleApiError(e)
    if (axios.isAxiosError(e)) {
      const status = e.response?.status
      const apiMsg = String((e.response?.data as { message?: unknown })?.message ?? '').trim()
      if (status === 403) {
        feedback.value =
          'Sem permissão para guardar faturas de fornecedor. Peça a um administrador as permissões do módulo (supplier-invoices).'
      } else if (status === 422 && apiMsg) {
        feedback.value = apiMsg
      } else if (apiMsg) {
        feedback.value = apiMsg
      } else {
        feedback.value = 'Não foi possível guardar a fatura.'
      }
    } else {
      feedback.value = 'Não foi possível guardar a fatura.'
    }
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const [suppliers, orders] = await Promise.all([getSupplierOptions(), getSupplierOrdersOptions()])
    supplierOptions.value = suppliers
    supplierOrderOptions.value = orders
    await hydrate()
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Nova Fatura de Fornecedor' : 'Fatura de Fornecedor' }}</h1>
    <p v-if="feedback" class="feedback">{{ feedback }}</p>

    <Form class="form" @submit="onSubmit">
      <FormField v-slot="{ value, handleChange }" name="number">
        <FormItem>
          <FormLabel>Número</FormLabel>
          <FormControl>
            <Input :model-value="value" :disabled="loading || submitting" @update:model-value="handleChange" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="invoice_date">
        <FormItem>
          <FormLabel>Data da Fatura</FormLabel>
          <FormControl>
            <Input
              :model-value="value"
              type="date"
              :disabled="loading || submitting"
              @update:model-value="handleChange"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="due_date">
        <FormItem>
          <FormLabel>Data de Vencimento</FormLabel>
          <FormControl>
            <Input
              :model-value="value"
              type="date"
              :disabled="loading || submitting"
              @update:model-value="handleChange"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="supplier_id">
        <FormItem>
          <FormLabel>Fornecedor</FormLabel>
          <FormControl>
            <select
              class="native-select"
              :value="String(value ?? '')"
              :disabled="loading || submitting"
              @change="handleChange(($event.target as HTMLSelectElement).value)"
            >
              <option value="">Selecione</option>
              <option v-for="supplier in supplierOptions" :key="supplier.id" :value="String(supplier.id)">
                {{ supplier.name }}
              </option>
            </select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="supplier_order_id">
        <FormItem>
          <FormLabel>Encomenda Fornecedor</FormLabel>
          <FormControl>
            <select
              class="native-select"
              :value="String(value ?? '')"
              :disabled="loading || submitting"
              @change="handleChange(($event.target as HTMLSelectElement).value)"
            >
              <option value="">Sem encomenda</option>
              <option v-for="order in supplierOrderOptions" :key="order.id" :value="String(order.id)">
                {{ order.number }}
              </option>
            </select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="total_amount">
        <FormItem>
          <FormLabel>Valor Total</FormLabel>
          <FormControl>
            <Input
              :model-value="value"
              type="number"
              min="0"
              step="0.01"
              :disabled="loading || submitting"
              @update:model-value="handleChange"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="status">
        <FormItem>
          <FormLabel>Estado</FormLabel>
          <FormControl>
            <select
              class="native-select"
              :value="String(value ?? 'pending_payment')"
              :disabled="loading || submitting"
              @change="
                ($event) => {
                  const nextValue = ($event.target as HTMLSelectElement).value as 'pending_payment' | 'paid'
                  selectedStatus = nextValue
                  handleChange(nextValue)
                }
              "
            >
              <option value="pending_payment">Pendente de Pagamento</option>
              <option value="paid">Paga</option>
            </select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="file-field">
        <label>Documento</label>
        <Input type="file" :disabled="loading || submitting" @change="onDocumentChange" />
      </div>

      <div class="file-field">
        <label>Comprovativo de Pagamento</label>
        <Input type="file" :disabled="loading || submitting" @change="onPaymentReceiptChange" />
        <small v-if="selectedStatus === 'paid'" class="hint">
          Estado "Paga" exige comprovativo para concluir o envio.
        </small>
      </div>

      <div v-if="!isNew && existingFiles.length" class="file-field">
        <label>Anexos existentes</label>
        <div class="existing-files">
          <div v-for="file in existingFiles" :key="file.id" class="existing-file-row">
            <span>{{ file.name }} ({{ categoryMap[file.category] ?? file.category }})</span>
            <Button size="sm" variant="outline" type="button" @click="downloadFile(file.id)">Download</Button>
          </div>
        </div>
      </div>

      <div class="footer">
        <Button type="button" variant="outline" @click="router.push('/supplier-invoices')">Voltar</Button>
        <Button type="submit" :disabled="loading || submitting">
          {{ submitting ? 'A guardar...' : isNew ? 'Criar' : 'Guardar' }}
        </Button>
      </div>
    </Form>
  </div>
</template>

<style scoped>
.page {
  width: 100%;
  max-width: 70rem;
}

.form {
  display: grid;
  gap: 0.9rem;
}

.feedback {
  margin-bottom: 1rem;
}

.native-select {
  width: 100%;
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  min-height: 2.5rem;
  padding: 0 0.75rem;
  background: hsl(var(--background));
}

.file-field {
  display: grid;
  gap: 0.35rem;
}

.hint {
  color: hsl(var(--muted-foreground));
}

.footer {
  display: flex;
  gap: 0.8rem;
}

.existing-files {
  display: grid;
  gap: 0.45rem;
}

.existing-file-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}
</style>
