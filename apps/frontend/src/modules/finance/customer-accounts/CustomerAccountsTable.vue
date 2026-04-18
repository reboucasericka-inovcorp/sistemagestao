<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
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
import CustomerAccountForm from '@/modules/finance/customer-accounts/CustomerAccountForm.vue'
import { hasPermission } from '@/modules/auth/services/authService'
import {
  listCustomerAccountMovements,
  listCustomerAccounts,
  type CustomerAccount,
  type CustomerAccountMovement,
} from '@/modules/finance/customer-accounts/customerAccountService'
import { listEntitiesResult } from '@/modules/entities/services/entityService'
import type { Entity } from '@/modules/entities/types/entity'

const accounts = ref<CustomerAccount[]>([])
const clients = ref<Entity[]>([])
const loadingAccounts = ref(false)
const accountError = ref('')
const selectedAccount = ref<CustomerAccount | null>(null)
const movements = ref<CustomerAccountMovement[]>([])
const loadingMovements = ref(false)
const movementError = ref('')
const canCreateMovement = computed(() => hasPermission('customer-accounts.create'))
const selectedEntityId = ref<number | null>(null)
const loadingClients = ref(false)

/** Converte valor do <select> (string) para entity id numérico. */
function parseEntityId(raw: string): number | null {
  if (!raw) return null
  const n = Number(raw)
  return Number.isFinite(n) ? n : null
}

const selectedClientLabel = computed(() => {
  const id = selectedEntityId.value
  if (id == null) return ''
  return clients.value.find((c) => c.id === id)?.name ?? selectedAccount.value?.entity?.name ?? ''
})

function onClientChange(event: Event): void {
  const raw = (event.target as HTMLSelectElement).value
  selectedEntityId.value = parseEntityId(raw)
}

function formatCurrency(value: string): string {
  const parsed = Number(value)
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
  }).format(Number.isNaN(parsed) ? 0 : parsed)
}

function formatDate(value: string): string {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('pt-PT')
}

async function loadClients(): Promise<void> {
  loadingClients.value = true
  try {
    const res = await listEntitiesResult({ is_client: true, active_only: true, per_page: 100 })
    clients.value = res.data.filter((e) => e.is_client && e.is_active)
  } catch {
    clients.value = []
  } finally {
    loadingClients.value = false
  }
}

async function loadAccounts(): Promise<void> {
  loadingAccounts.value = true
  accountError.value = ''
  try {
    accounts.value = await listCustomerAccounts()
  } catch {
    accountError.value = 'Não foi possível carregar as contas correntes.'
    accounts.value = []
  } finally {
    loadingAccounts.value = false
    const id = selectedEntityId.value
    if (id != null) {
      selectedAccount.value = accounts.value.find((a) => a.entity_id === id) ?? null
    }
  }
}

async function loadMovements(accountId: number): Promise<void> {
  loadingMovements.value = true
  movementError.value = ''
  try {
    movements.value = await listCustomerAccountMovements(accountId)
  } catch {
    movementError.value = 'Não foi possível carregar os movimentos.'
    movements.value = []
  } finally {
    loadingMovements.value = false
  }
}

function selectAccount(account: CustomerAccount): void {
  const id = Number(account.entity_id)
  selectedEntityId.value = Number.isFinite(id) ? id : null
}

async function onMovementCreated(): Promise<void> {
  await loadAccounts()
  const id = selectedEntityId.value
  if (id == null) return
  await loadMovements(id)
}

watch(selectedEntityId, async (id) => {
  if (id == null) {
    selectedAccount.value = null
    movements.value = []
    movementError.value = ''
    return
  }
  const acc = accounts.value.find((a) => a.entity_id === id) ?? null
  selectedAccount.value = acc
  await loadMovements(id)
})

onMounted(() => {
  void Promise.all([loadAccounts(), loadClients()])
})
</script>

<template>
  <div class="grid gap-4 lg:grid-cols-[1.1fr_1fr]">
    <div class="space-y-4">
      <div
        v-if="accountError"
        class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
      >
        {{ accountError }}
      </div>

      <div class="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Cliente</TableHead>
              <TableHead>Número</TableHead>
              <TableHead>Saldo</TableHead>
              <TableHead class="text-right">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableEmpty v-if="loadingAccounts" :colspan="4">A carregar contas correntes...</TableEmpty>
            <TableEmpty v-else-if="!accounts.length" :colspan="4">Sem contas correntes.</TableEmpty>
            <TableRow
              v-for="account in accounts"
              v-else
              :key="account.id"
              :class="{ 'bg-muted': selectedEntityId != null && account.entity_id === selectedEntityId }"
            >
              <TableCell>{{ account.entity?.name ?? '-' }}</TableCell>
              <TableCell>{{ account.entity?.number ?? '-' }}</TableCell>
              <TableCell>{{ formatCurrency(account.balance) }}</TableCell>
              <TableCell class="text-right">
                <Button size="sm" variant="outline" @click="selectAccount(account)">Ver movimentos</Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </div>
    </div>

    <div class="space-y-4">
      <div class="mb-4">
        <label class="mb-1 block text-sm font-medium">Cliente</label>
        <select
          class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          :disabled="loadingClients"
          :value="selectedEntityId ?? ''"
          @change="onClientChange"
        >
          <option value="" disabled>Selecione um cliente</option>
          <option v-for="c in clients" :key="c.id" :value="String(c.id)">
            {{ c.name }}
          </option>
        </select>
        <p
          v-if="!loadingClients && clients.length === 0"
          class="mt-2 text-sm text-muted-foreground"
        >
          Nenhum cliente ativo encontrado. Se na base não existirem clientes, o select fica vazio (não é falha do
          formulário).
        </p>
      </div>

      <CustomerAccountForm
        v-if="canCreateMovement"
        :entity-id="selectedEntityId"
        @created="onMovementCreated"
      />
      <div
        v-else
        class="rounded-md border border-muted bg-muted/30 px-3 py-2 text-sm text-muted-foreground"
      >
        Sem permissão para adicionar movimentos.
      </div>

      <div class="rounded-md border p-4">
        <h2 class="mb-3 text-base font-semibold">
          Movimentos {{ selectedClientLabel ? `— ${selectedClientLabel}` : '' }}
        </h2>

        <p
          v-if="movementError"
          class="mb-3 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
        >
          {{ movementError }}
        </p>

        <div v-if="loadingMovements" class="text-sm text-muted-foreground">A carregar movimentos...</div>
        <div v-else-if="selectedEntityId == null" class="text-sm text-muted-foreground">
          Selecione um cliente para ver os movimentos.
        </div>
        <div v-else-if="!movements.length" class="text-sm text-muted-foreground">
          Sem movimentos registados.
        </div>
        <ul v-else class="space-y-2">
          <li v-for="movement in movements" :key="movement.id" class="rounded-md border px-3 py-2">
            <div class="flex items-center justify-between gap-3">
              <strong>{{ movement.type === 'credit' ? 'Crédito' : 'Débito' }}</strong>
              <span>{{ formatCurrency(movement.amount) }}</span>
            </div>
            <div class="text-xs text-muted-foreground">
              {{ formatDate(movement.date) }} — {{ movement.description || 'Sem descrição' }}
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
