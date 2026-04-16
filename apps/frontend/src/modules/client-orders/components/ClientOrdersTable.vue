<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Table,
  TableBody,
  TableCell,
  TableEmpty,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  convertClientOrderToSupplierOrders,
  getClientOrders,
} from '@/modules/client-orders/services/clientOrderService'
import type { ClientOrder } from '@/modules/client-orders/types/clientOrder'
import { toast } from 'vue-sonner'

const router = useRouter()
const orders = ref<ClientOrder[]>([])
const loading = ref(false)
const errorMessage = ref('')
const convertingId = ref<number | null>(null)
const conversionDialogOpen = ref(false)
const selectedOrder = ref<ClientOrder | null>(null)

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
    orders.value = await getClientOrders()
  } catch {
    orders.value = []
    errorMessage.value = 'Não foi possível carregar as encomendas.'
  } finally {
    loading.value = false
  }
}

function goToEdit(id: number): void {
  void router.push(`/client-orders/${id}/edit`)
}

function openConvertDialog(order: ClientOrder): void {
  selectedOrder.value = order
  conversionDialogOpen.value = true
}

async function confirmConvert(order: ClientOrder): Promise<void> {
  convertingId.value = order.id
  try {
    await convertClientOrderToSupplierOrders(order.id)
    toast.success('Encomendas criadas. Consulte em "Encomendas de Fornecedor".')
    await loadOrders()
    conversionDialogOpen.value = false
    selectedOrder.value = null
    await router.push('/supplier-orders')
  } catch (error: unknown) {
    const responseData =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: unknown } }).response?.data
        : undefined
    const message =
      typeof responseData === 'object' && responseData && 'message' in responseData
        ? (responseData as { message?: string }).message
        : undefined
    console.error('[CONVERT ERROR]', error)
    toast.error(message ?? 'Não foi possível gerar encomendas de fornecedor.')
  } finally {
    convertingId.value = null
  }
}

onMounted(() => {
  void loadOrders()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-end">
      <Button @click="router.push('/client-orders/create')">Criar Encomenda</Button>
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
            <TableHead>Cliente</TableHead>
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
            <TableCell>{{ order.client?.name ?? '-' }}</TableCell>
            <TableCell>{{ formatCurrency(order.total_amount) }}</TableCell>
            <TableCell>
              {{
                order.status === 'closed'
                  ? 'Convertida'
                  : 'Rascunho'
              }}
            </TableCell>
            <TableCell class="text-right">
              <div class="actions">
                <Button size="sm" variant="outline" @click="goToEdit(order.id)">Ver</Button>
                <Button
                  size="sm"
                  :disabled="order.status === 'closed' || convertingId === order.id"
                  @click="openConvertDialog(order)"
                >
                  {{ convertingId === order.id ? 'A gerar...' : 'Gerar Encomendas Fornecedor' }}
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <Dialog :open="conversionDialogOpen" @update:open="conversionDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirmar ação</DialogTitle>
          <DialogDescription>
            Deseja gerar encomendas de fornecedor para esta encomenda?
          </DialogDescription>
        </DialogHeader>

        <DialogFooter>
          <Button variant="outline" @click="conversionDialogOpen = false">Cancelar</Button>
          <Button
            :disabled="!selectedOrder || (selectedOrder && convertingId === selectedOrder.id)"
            @click="selectedOrder && confirmConvert(selectedOrder)"
          >
            {{ selectedOrder && convertingId === selectedOrder.id ? 'A gerar...' : 'Confirmar' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<style scoped>
.actions {
  display: inline-flex;
  gap: 0.5rem;
}
</style>
