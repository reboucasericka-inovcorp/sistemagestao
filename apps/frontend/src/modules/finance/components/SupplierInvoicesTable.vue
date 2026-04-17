<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableEmpty,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { downloadInvoiceFile, listSupplierInvoices } from '@/modules/finance/services/supplierInvoiceService'
import type { SupplierInvoice } from '@/modules/finance/types/supplierInvoice'

const router = useRouter()
const invoices = ref<SupplierInvoice[]>([])
const loading = ref(false)
const errorMessage = ref('')

function formatDate(value: string): string {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('pt-PT')
}

function formatCurrency(value: string): string {
  const parsedValue = Number(value)
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
  }).format(Number.isNaN(parsedValue) ? 0 : parsedValue)
}

function formatStatus(status: string): string {
  if (status === 'paid') return 'Paga'
  return 'Pendente de Pagamento'
}

async function loadInvoices(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    invoices.value = await listSupplierInvoices()
  } catch {
    invoices.value = []
    errorMessage.value = 'Não foi possível carregar as faturas.'
  } finally {
    loading.value = false
  }
}

function goToCreate(): void {
  void router.push('/supplier-invoices/create')
}

function goToEdit(id: number): void {
  void router.push(`/supplier-invoices/${id}/edit`)
}

function downloadDocument(invoice: SupplierInvoice): void {
  const documentFile = invoice.files?.find((file) => file.category === 'supplier-invoice-document')
  if (!documentFile) return
  downloadInvoiceFile(invoice.id, documentFile.id)
}

onMounted(() => {
  void loadInvoices()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-end">
      <Button @click="goToCreate">Criar Fatura</Button>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
    >
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Data</TableHead>
            <TableHead>Número</TableHead>
            <TableHead>Fornecedor</TableHead>
            <TableHead>Encomenda</TableHead>
            <TableHead>Documento</TableHead>
            <TableHead>Valor Total</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="8">A carregar faturas...</TableEmpty>
          <TableEmpty v-else-if="!invoices.length" :colspan="8">Nenhuma fatura encontrada.</TableEmpty>
          <TableRow v-for="invoice in invoices" v-else :key="invoice.id">
            <TableCell>{{ formatDate(invoice.invoice_date) }}</TableCell>
            <TableCell>{{ invoice.number }}</TableCell>
            <TableCell>{{ invoice.supplier?.name ?? '-' }}</TableCell>
            <TableCell>{{ invoice.supplier_order?.number ?? '-' }}</TableCell>
            <TableCell>
              <Button
                v-if="invoice.files?.some((file) => file.category === 'supplier-invoice-document')"
                size="sm"
                variant="outline"
                @click="downloadDocument(invoice)"
              >
                Download
              </Button>
              <span v-else>-</span>
            </TableCell>
            <TableCell>{{ formatCurrency(invoice.total_amount) }}</TableCell>
            <TableCell>{{ formatStatus(invoice.status) }}</TableCell>
            <TableCell class="text-right">
              <Button size="sm" variant="outline" @click="goToEdit(invoice.id)">Ver</Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
