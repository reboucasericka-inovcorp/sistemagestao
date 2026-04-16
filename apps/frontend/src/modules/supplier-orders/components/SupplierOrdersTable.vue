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
import { getSupplierOrders } from '@/modules/supplier-orders/services/supplierOrderService'
import type { SupplierOrder } from '@/modules/supplier-orders/types/supplierOrder'

const router = useRouter()
const orders = ref<SupplierOrder[]>([])
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

async function loadOrders(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    orders.value = await getSupplierOrders()
  } catch {
    orders.value = []
    errorMessage.value = 'Não foi possível carregar as encomendas.'
  } finally {
    loading.value = false
  }
}

function goToEdit(id: number): void {
  void router.push(`/supplier-orders/${id}/edit`)
}

onMounted(() => {
  void loadOrders()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-end">
      <Button @click="router.push('/supplier-orders/create')">Criar Encomenda</Button>
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
            <TableHead>Valor Total</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="6">A carregar encomendas...</TableEmpty>
          <TableEmpty v-else-if="!orders.length" :colspan="6">Nenhuma encomenda encontrada.</TableEmpty>
          <TableRow v-for="order in orders" v-else :key="order.id">
            <TableCell>{{ formatDate(order.order_date) }}</TableCell>
            <TableCell>{{ order.number }}</TableCell>
            <TableCell>{{ order.supplier?.name ?? '-' }}</TableCell>
            <TableCell>{{ formatCurrency(order.total_amount) }}</TableCell>
            <TableCell>{{ order.status === 'confirmed' ? 'Confirmada' : 'Rascunho' }}</TableCell>
            <TableCell class="text-right">
              <Button size="sm" variant="outline" @click="goToEdit(order.id)">Ver</Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
