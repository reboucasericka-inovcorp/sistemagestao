<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useApiAction } from '@/composables/useApiAction'
import { useConfirmDialog } from '@/composables/useConfirmDialog'
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
  deleteDigitalFile,
  downloadDigitalFile,
  getDigitalFiles,
  type DigitalFileItem,
  type DigitalFilesListMeta,
} from '../services/digitalFileService'

const files = ref<DigitalFileItem[]>([])
const loading = ref(false)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)
const confirmDialog = useConfirmDialog()

const { loading: deletingLoading, execute } = useApiAction()

const pagination = reactive<DigitalFilesListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)

const categoryMap: Record<string, string> = {
  'supplier-invoice-document': 'Documento',
  'supplier-invoice-payment-proof': 'Comprovativo de Pagamento',
}

function formatDate(value?: string | null): string {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('pt-PT', { dateStyle: 'short', timeStyle: 'short' }).format(date)
}

function queueSearch(value: string): void {
  searchText.value = value
  if (debounceId.value != null) {
    window.clearTimeout(debounceId.value)
  }
  debounceId.value = window.setTimeout(() => {
    searchDebounced.value = value.trim()
    pagination.current_page = 1
    void load(false)
  }, 300)
}

async function load(resetPage = false): Promise<void> {
  if (resetPage) {
    pagination.current_page = 1
  }

  loading.value = true
  errorMessage.value = ''

  try {
    const result = await getDigitalFiles({
      search: searchDebounced.value || undefined,
      page: pagination.current_page,
      per_page: pagination.per_page,
    })
    files.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    files.value = []
    errorMessage.value = 'Não foi possível carregar os ficheiros.'
  } finally {
    loading.value = false
  }
}

async function reload(): Promise<void> {
  await load(false)
}

async function reloadAndReset(): Promise<void> {
  await load(true)
}

function askDelete(id: number): void {
  confirmDialog.open({
    title: 'Confirmar remoção',
    description: 'Tem a certeza que deseja apagar este ficheiro?',
    confirmLabel: 'Apagar',
    onConfirm: async () => {
      await execute(() => deleteDigitalFile(id), {
        successMessage: 'Ficheiro removido com sucesso',
        onSuccess: () => {
          const isLastItemOnPage = files.value.length === 1
          if (isLastItemOnPage && pagination.current_page > 1) {
            pagination.current_page -= 1
          }

          if (pagination.current_page > pagination.last_page) {
            pagination.current_page = Math.max(1, pagination.last_page)
          }

          void load(false)
        },
      })
    },
  })
}

function goToPage(page: number): void {
  if (page < 1 || page > pagination.last_page) return
  pagination.current_page = page
  void load(false)
}

onMounted(() => {
  void load(true)
})

onBeforeUnmount(() => {
  if (debounceId.value != null) {
    window.clearTimeout(debounceId.value)
  }
})

defineExpose({
  reload,
  reloadAndReset,
})
</script>

<template>
  <div class="space-y-4">
    <Input
      :model-value="searchText"
      placeholder="Pesquisar por nome..."
      class="w-[280px]"
      @update:model-value="queueSearch(String($event))"
    />

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nome</TableHead>
            <TableHead>Categoria</TableHead>
            <TableHead>Data</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="4">A carregar ficheiros...</TableEmpty>
          <TableEmpty v-else-if="!files.length" :colspan="4">Sem ficheiros.</TableEmpty>
          <TableRow v-for="file in files" v-else :key="file.id">
            <TableCell>{{ file.name }}</TableCell>
            <TableCell>{{ file.category ? categoryMap[file.category] ?? file.category : '-' }}</TableCell>
            <TableCell>{{ formatDate(file.created_at) }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" @click="downloadDigitalFile(file.id)">Download</Button>
                <Button size="sm" variant="destructive" :disabled="deletingLoading" @click="askDelete(file.id)">
                  {{ deletingLoading ? 'A apagar...' : 'Apagar' }}
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div class="flex items-center justify-between text-sm text-muted-foreground">
      <p>Página {{ pagination.current_page }} de {{ pagination.last_page }} · {{ pagination.total }} registos</p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="!hasPreviousPage || loading" @click="goToPage(pagination.current_page - 1)">
          Anterior
        </Button>
        <Button variant="outline" size="sm" :disabled="!hasNextPage || loading" @click="goToPage(pagination.current_page + 1)">
          Próxima
        </Button>
      </div>
    </div>

  </div>
</template>
