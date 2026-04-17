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
import { Input } from '@/components/ui/input'
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
  convertWorkOrderFromClientOrder,
  getWorkOrders,
} from '@/modules/work-orders/services/workOrderService'
import type { WorkOrder } from '@/modules/work-orders/types/workOrder'
import { getClientOrders } from '@/modules/client-orders/services/clientOrderService'
import { toast } from 'vue-sonner'

const router = useRouter()
const workOrders = ref<WorkOrder[]>([])
const loading = ref(false)
const errorMessage = ref('')
const convertDialogOpen = ref(false)
const clientOrderIdInput = ref<number | null>(null)
const clientOrders = ref<Array<{ id: number; number: string }>>([])
const convertingFromClientOrder = ref(false)
const loadingClientOrders = ref(false)

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

function statusLabel(status: WorkOrder['status']): string {
  if (status === 'in_progress') return 'Em Curso'
  if (status === 'completed') return 'Concluída'
  return 'Rascunho'
}

async function loadWorkOrders(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    workOrders.value = await getWorkOrders()
  } catch {
    workOrders.value = []
    errorMessage.value = 'Não foi possível carregar as ordens de trabalho.'
  } finally {
    loading.value = false
  }
}

function goToEdit(id: number): void {
  void router.push(`/work-orders/${id}/edit`)
}

function openConvertFromClientOrderDialog(): void {
  clientOrderIdInput.value = null
  convertDialogOpen.value = true
}

async function confirmConvertFromClientOrder(): Promise<void> {
  if (!clientOrderIdInput.value) {
    toast.error('Selecione uma encomenda válida.')
    return
  }

  convertingFromClientOrder.value = true
  try {
    await convertWorkOrderFromClientOrder(clientOrderIdInput.value)
    toast.success('OT criada com sucesso a partir da encomenda.')
    convertDialogOpen.value = false
    await loadWorkOrders()
  } catch (error: unknown) {
    const responseData =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: unknown } }).response?.data
        : undefined
    const message =
      typeof responseData === 'object' && responseData && 'message' in responseData
        ? (responseData as { message?: string }).message
        : undefined
    toast.error(message ?? 'Não foi possível converter a encomenda.')
  } finally {
    convertingFromClientOrder.value = false
  }
}

onMounted(() => {
  void loadWorkOrders()
  void loadClientOrders()
})

async function loadClientOrders(): Promise<void> {
  loadingClientOrders.value = true
  try {
    const orders = await getClientOrders()
    clientOrders.value = orders.map((order) => ({ id: order.id, number: order.number }))
  } catch {
    clientOrders.value = []
  } finally {
    loadingClientOrders.value = false
  }
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-end gap-2">
      <Button variant="outline" @click="openConvertFromClientOrderDialog">Converter Encomenda</Button>
      <Button @click="router.push('/work-orders/create')">Criar OT</Button>
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
            <TableHead>Número</TableHead>
            <TableHead>Data</TableHead>
            <TableHead>Cliente</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead>Total</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="6">A carregar ordens de trabalho...</TableEmpty>
          <TableEmpty v-else-if="!workOrders.length" :colspan="6">Nenhuma OT encontrada.</TableEmpty>
          <TableRow v-for="workOrder in workOrders" v-else :key="workOrder.id">
            <TableCell>{{ workOrder.number }}</TableCell>
            <TableCell>{{ formatDate(workOrder.date) }}</TableCell>
            <TableCell>{{ workOrder.client?.name ?? '-' }}</TableCell>
            <TableCell>{{ statusLabel(workOrder.status) }}</TableCell>
            <TableCell>{{ formatCurrency(workOrder.total_amount) }}</TableCell>
            <TableCell class="text-right">
              <Button size="sm" variant="outline" @click="goToEdit(workOrder.id)">Ver</Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <Dialog :open="convertDialogOpen" @update:open="convertDialogOpen = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Converter encomenda em OT</DialogTitle>
          <DialogDescription>
            Selecione a encomenda de cliente a converter.
          </DialogDescription>
        </DialogHeader>

        <select v-model="clientOrderIdInput" class="w-full rounded-md border px-3 py-2">
          <option :value="null">Selecione uma encomenda</option>
          <option v-for="order in clientOrders" :key="order.id" :value="order.id">
            {{ order.number }}
          </option>
        </select>
        <p v-if="loadingClientOrders" class="text-xs text-muted-foreground">
          A carregar encomendas...
        </p>

        <DialogFooter>
          <Button variant="outline" @click="convertDialogOpen = false">Cancelar</Button>
          <Button :disabled="convertingFromClientOrder" @click="confirmConvertFromClientOrder">
            {{ convertingFromClientOrder ? 'A converter...' : 'Converter' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
