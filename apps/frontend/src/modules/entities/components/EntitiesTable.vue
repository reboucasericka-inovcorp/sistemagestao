<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { DataTable } from '@/components/ui/data-table'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  listEntitiesResult,
  type EntitiesListMeta,
} from '@/modules/entities/services/entityService'
import type { Entity } from '@/modules/entities/types/entity'

type EntityFilter = 'all' | 'is_client' | 'is_supplier'

const emit = defineEmits<{
  (e: 'create'): void
  (e: 'edit', id: number): void
}>()

const props = withDefaults(
  defineProps<{
    fixedFilter?: EntityFilter
    editRouteName?: 'entities.edit' | 'clients.edit' | 'suppliers.edit'
    useEditModal?: boolean
    showCreateAction?: boolean
  }>(),
  {
    fixedFilter: 'all',
    editRouteName: 'entities.edit',
    useEditModal: false,
    showCreateAction: true,
  },
)
const router = useRouter()

const entities = ref<Entity[]>([])
const loading = ref(false)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  mode: 'all' as EntityFilter,
})

const pagination = reactive<EntitiesListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)
const tableColumns: ColumnDef<Record<string, unknown>, unknown>[] = [
  { accessorKey: 'nif', header: 'NIF' },
  { accessorKey: 'name', header: 'Nome' },
  { accessorKey: 'phone', header: 'Telefone' },
  { accessorKey: 'mobile', header: 'Telemóvel' },
  { accessorKey: 'website', header: 'Website' },
  { accessorKey: 'email', header: 'Email' },
]
const tableRows = computed<Record<string, unknown>[]>(() =>
  entities.value.map((entity) => ({
    id: entity.id,
    nif: entity.nif,
    name: entity.name,
    phone: entity.phone || '-',
    mobile: entity.mobile || '-',
    website: entity.website || '-',
    email: entity.email || '-',
  })),
)

function setTypeFilterFromProps(): void {
  filters.mode = props.fixedFilter
}

async function fetchEntities(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listEntitiesResult({
      is_client: filters.mode === 'is_client' ? true : undefined,
      is_supplier: filters.mode === 'is_supplier' ? true : undefined,
      page: pagination.current_page,
      per_page: pagination.per_page,
      search: searchDebounced.value || undefined,
    })

    entities.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    entities.value = []
    errorMessage.value = 'Não foi possível carregar as entidades.'
  } finally {
    loading.value = false
  }
}

function queueSearch(value: string): void {
  searchText.value = value
  if (debounceId.value != null) {
    window.clearTimeout(debounceId.value)
  }
  debounceId.value = window.setTimeout(() => {
    searchDebounced.value = value.trim()
    pagination.current_page = 1
  }, 300)
}

function goToEdit(entityId: number): void {
  if (props.useEditModal) {
    emit('edit', entityId)
    return
  }
  void router.push({ name: props.editRouteName, params: { id: entityId } })
}

function onRowClick(row: Record<string, unknown>): void {
  const id = Number(row.id)
  if (Number.isNaN(id)) {
    return
  }
  goToEdit(id)
}

function goToPage(page: number): void {
  if (page < 1 || page > pagination.last_page) {
    return
  }
  pagination.current_page = page
}

watch(
  () => props.fixedFilter,
  () => {
    setTypeFilterFromProps()
    pagination.current_page = 1
  },
)

watch(
  () => [filters.mode, pagination.current_page, pagination.per_page, searchDebounced.value],
  () => {
    void fetchEntities()
  },
)

onMounted(() => {
  setTypeFilterFromProps()
  void fetchEntities()
})

onBeforeUnmount(() => {
  if (debounceId.value != null) {
    window.clearTimeout(debounceId.value)
  }
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <Input
          :model-value="searchText"
          placeholder="Pesquisar por NIF ou nome..."
          class="w-[280px]"
          @update:model-value="queueSearch(String($event))"
        />
        <Select
          :model-value="filters.mode"
          :disabled="props.fixedFilter !== 'all'"
          @update:model-value="filters.mode = $event as EntityFilter"
        >
          <SelectTrigger class="w-[200px]">
            <SelectValue placeholder="Filtrar por tipo" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todos</SelectItem>
            <SelectItem value="is_client">Clientes</SelectItem>
            <SelectItem value="is_supplier">Fornecedores</SelectItem>
          </SelectContent>
        </Select>
        <Select
          :model-value="String(pagination.per_page)"
          @update:model-value="
            ((pagination.per_page = Number($event)), (pagination.current_page = 1))
          "
        >
          <SelectTrigger class="w-[120px]">
            <SelectValue placeholder="Por página" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="10">10</SelectItem>
            <SelectItem value="15">15</SelectItem>
            <SelectItem value="25">25</SelectItem>
            <SelectItem value="50">50</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <div v-if="props.showCreateAction" class="flex gap-2">
        <Button variant="outline" @click="emit('create')">Criar novo(a)</Button>
      </div>
    </div>

    <div
      v-if="errorMessage"
      class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
    >
      {{ errorMessage }}
    </div>

    <DataTable
      :data="tableRows"
      :columns="tableColumns"
      :loading="loading"
      empty-message="Nenhuma entidade encontrada."
      @row-click="onRowClick"
    />

    <div class="flex items-center justify-between text-sm text-muted-foreground">
      <p>
        Página {{ pagination.current_page }} de {{ pagination.last_page }} ·
        {{ pagination.total }} registos
      </p>
      <div class="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="!hasPreviousPage || loading"
          @click="goToPage(pagination.current_page - 1)"
        >
          Anterior
        </Button>
        <Button
          variant="outline"
          size="sm"
          :disabled="!hasNextPage || loading"
          @click="goToPage(pagination.current_page + 1)"
        >
          Próxima
        </Button>
      </div>
    </div>
  </div>
</template>
