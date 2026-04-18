<script setup lang="ts">
import axios from 'axios'
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
import { listBankAccounts, updateBankAccount, type BankAccount } from '@/modules/finance/bank-accounts/bankAccountService'

const router = useRouter()
const rows = ref<BankAccount[]>([])
const loading = ref(false)
const errorMessage = ref('')

async function loadRows(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    rows.value = await listBankAccounts()
  } catch {
    rows.value = []
    errorMessage.value = 'Não foi possível carregar as contas bancárias.'
  } finally {
    loading.value = false
  }
}

function goToCreate(): void {
  void router.push('/bank-accounts/create')
}

function goToEdit(id: number): void {
  void router.push(`/bank-accounts/${id}/edit`)
}

async function toggleStatus(row: BankAccount): Promise<void> {
  try {
    await updateBankAccount(row.id, {
      bank_name: row.bank_name,
      iban: row.iban,
      account_holder: row.account_holder,
      is_active: !row.is_active,
    })
    await loadRows()
  } catch (e: unknown) {
    if (axios.isAxiosError(e) && e.response?.status === 403) {
      errorMessage.value = 'Sem permissão para atualizar contas bancárias.'
      return
    }
    errorMessage.value = 'Não foi possível atualizar o estado da conta bancária.'
  }
}

onMounted(() => {
  void loadRows()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-end">
      <Button @click="goToCreate">Criar Conta Bancária</Button>
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
            <TableHead>Banco</TableHead>
            <TableHead>IBAN</TableHead>
            <TableHead>Titular</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="5">A carregar contas bancárias...</TableEmpty>
          <TableEmpty v-else-if="!rows.length" :colspan="5">Nenhuma conta bancária encontrada.</TableEmpty>
          <TableRow v-for="row in rows" v-else :key="row.id">
            <TableCell>{{ row.bank_name }}</TableCell>
            <TableCell>{{ row.iban }}</TableCell>
            <TableCell>{{ row.account_holder }}</TableCell>
            <TableCell>{{ row.is_active ? 'Ativa' : 'Inativa' }}</TableCell>
            <TableCell class="space-x-2 text-right">
              <Button size="sm" variant="outline" @click="goToEdit(row.id)">Editar</Button>
              <Button size="sm" variant="secondary" @click="toggleStatus(row)">
                {{ row.is_active ? 'Desativar' : 'Ativar' }}
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </div>
</template>
