<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
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
  listEntitiesResult,
  toggleEntityStatus,
  type EntitiesListMeta,
} from '@/modules/entities/services/entityService'
import type { Entity } from '@/modules/entities/types/entity'

type TypeFilter = 'all' | 'client' | 'supplier'

const emit = defineEmits<{
  (e: 'create'): void
  (e: 'edit', id: number): void
}>()

const props = withDefaults(
  defineProps<{
    fixedType?: TypeFilter
    createPath?: string
    editRouteName?: 'entities.edit' | 'clients.edit' | 'suppliers.edit'
    useEditModal?: boolean
  }>(),
  {
    fixedType: 'all',
    createPath: '/entities/new',
    editRouteName: 'entities.edit',
    useEditModal: false,
  },
)
const router = useRouter()

const entities = ref<Entity[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  type: 'all' as TypeFilter,
})

const pagination = reactive<EntitiesListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)

function setTypeFilterFromProps(): void {
  filters.type = props.fixedType
}

async function fetchEntities(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listEntitiesResult({
      type: filters.type === 'all' ? undefined : filters.type,
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

async function onToggleStatus(entityId: number): Promise<void> {
  togglingId.value = entityId
  try {
    await toggleEntityStatus(entityId)
    await fetchEntities()
  } finally {
    togglingId.value = null
  }
}

function goToPage(page: number): void {
  if (page < 1 || page > pagination.last_page) {
    return
  }
  pagination.current_page = page
}

watch(
  () => props.fixedType,
  () => {
    setTypeFilterFromProps()
    pagination.current_page = 1
  },
)

watch(
  () => [filters.type, pagination.current_page, pagination.per_page, searchDebounced.value],
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
          :model-value="filters.type"
          :disabled="props.fixedType !== 'all'"
          @update:model-value="filters.type = $event as TypeFilter"
        >
          <SelectTrigger class="w-[200px]">
            <SelectValue placeholder="Filtrar por tipo" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todos</SelectItem>
            <SelectItem value="client">Clientes</SelectItem>
            <SelectItem value="supplier">Fornecedores</SelectItem>
          </SelectContent>
        </Select>
        <Select
          :model-value="String(pagination.per_page)"
          @update:model-value="
            pagination.per_page = Number($event);
            pagination.current_page = 1
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

      <div class="flex gap-2">
        <Button variant="outline" @click="emit('create')">Criar entidade</Button>
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>NIF</TableHead>
            <TableHead>Nome</TableHead>
            <TableHead>Telefone</TableHead>
            <TableHead>Telemóvel</TableHead>
            <TableHead>Website</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="8">A carregar entidades...</TableEmpty>
          <TableEmpty v-else-if="!entities.length" :colspan="8">Nenhuma entidade encontrada.</TableEmpty>
          <TableRow v-for="entity in entities" v-else :key="entity.id">
            <TableCell>{{ entity.nif }}</TableCell>
            <TableCell>{{ entity.name }}</TableCell>
            <TableCell>{{ entity.phone || '-' }}</TableCell>
            <TableCell>{{ entity.mobile || '-' }}</TableCell>
            <TableCell>{{ entity.website || '-' }}</TableCell>
            <TableCell>{{ entity.email || '-' }}</TableCell>
            <TableCell>{{ entity.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" @click="goToEdit(entity.id)">Editar</Button>
                <Button
                  size="sm"
                  variant="secondary"
                  :disabled="togglingId === entity.id"
                  @click="onToggleStatus(entity.id)"
                >
                  {{ entity.is_active ? 'Inativar' : 'Ativar' }}
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div class="flex items-center justify-between text-sm text-muted-foreground">
      <p>
        Página {{ pagination.current_page }} de {{ pagination.last_page }} · {{ pagination.total }} registos
      </p>
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
