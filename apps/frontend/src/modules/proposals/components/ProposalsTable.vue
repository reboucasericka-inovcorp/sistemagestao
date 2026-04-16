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
import { convertProposal, downloadProposalPdf, getProposals } from '@/modules/proposals/services/proposalService'
import type { Proposal } from '@/modules/proposals/types/proposal'
import { toast } from 'vue-sonner'

const router = useRouter()
const proposals = ref<Proposal[]>([])
const loading = ref(false)
const errorMessage = ref('')
const loadingRowId = ref<number | null>(null)
const convertingId = ref<number | null>(null)
const conversionDialogOpen = ref(false)
const selectedProposalId = ref<number | null>(null)

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

async function loadProposals(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    proposals.value = await getProposals()
  } catch {
    proposals.value = []
    errorMessage.value = 'Não foi possível carregar as propostas.'
  } finally {
    loading.value = false
  }
}

function goToCreate(): void {
  void router.push('/proposals/create')
}

function goToEdit(id: number): void {
  void router.push(`/proposals/${id}/edit`)
}

async function handleDownloadPdf(id: number): Promise<void> {
  loadingRowId.value = id
  try {
    await downloadProposalPdf(id)
    toast.success('PDF descarregado.')
  } catch (error: unknown) {
    const message =
      error instanceof Error && error.message ? error.message : 'Erro ao baixar PDF'
    toast.error(message)
  } finally {
    loadingRowId.value = null
  }
}

function openConvertDialog(id: number, status: Proposal['status']): void {
  if (status === 'closed') {
    return
  }
  selectedProposalId.value = id
  conversionDialogOpen.value = true
}

async function confirmConvertProposal(): Promise<void> {
  const id = selectedProposalId.value
  if (id == null) {
    return
  }

  try {
    convertingId.value = id
    await convertProposal(id)
    conversionDialogOpen.value = false
    selectedProposalId.value = null
    toast.success('Proposta convertida com sucesso.')
    await loadProposals()
  } catch (error: unknown) {
    const message =
      error instanceof Error && error.message ? error.message : 'Erro ao converter proposta'
    toast.error(message)
  } finally {
    convertingId.value = null
  }
}

function onConversionDialogOpenChange(open: boolean): void {
  conversionDialogOpen.value = open
  if (!open) {
    selectedProposalId.value = null
  }
}

onMounted(() => {
  void loadProposals()
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex justify-end">
      <Button variant="outline" @click="goToCreate">Criar Proposta</Button>
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
            <TableHead>Validade</TableHead>
            <TableHead>Cliente</TableHead>
            <TableHead>Valor Total</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="7">A carregar propostas...</TableEmpty>
          <TableEmpty v-else-if="!proposals.length" :colspan="7">Nenhuma proposta encontrada.</TableEmpty>
          <TableRow v-for="proposal in proposals" v-else :key="proposal.id">
            <TableCell>{{ formatDate(proposal.proposal_date) }}</TableCell>
            <TableCell>{{ proposal.number }}</TableCell>
            <TableCell>{{ formatDate(proposal.valid_until) }}</TableCell>
            <TableCell>{{ proposal.client?.name ?? '-' }}</TableCell>
            <TableCell>{{ formatCurrency(proposal.total_amount) }}</TableCell>
            <TableCell>{{ proposal.status === 'closed' ? 'Fechada' : 'Rascunho' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button
                  size="sm"
                  variant="outline"
                  :disabled="loadingRowId === proposal.id"
                  @click="handleDownloadPdf(proposal.id)"
                >
                  <span v-if="loadingRowId === proposal.id">A exportar...</span>
                  <span v-else>PDF</span>
                </Button>
                <Button
                  size="sm"
                  variant="secondary"
                  :disabled="proposal.status === 'closed' || convertingId === proposal.id"
                  @click="openConvertDialog(proposal.id, proposal.status)"
                >
                  <span v-if="convertingId === proposal.id">A converter...</span>
                  <span v-else>Converter</span>
                </Button>
                <Button size="sm" variant="outline" @click="goToEdit(proposal.id)">Editar</Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <Dialog :open="conversionDialogOpen" @update:open="onConversionDialogOpenChange">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Confirmar conversão</DialogTitle>
          <DialogDescription>
            Converter esta proposta em encomenda de cliente?
          </DialogDescription>
        </DialogHeader>

        <DialogFooter>
          <Button variant="outline" @click="conversionDialogOpen = false">Cancelar</Button>
          <Button
            :disabled="selectedProposalId != null && convertingId === selectedProposalId"
            @click="confirmConvertProposal"
          >
            {{
              selectedProposalId != null && convertingId === selectedProposalId
                ? 'A converter...'
                : 'Confirmar'
            }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
