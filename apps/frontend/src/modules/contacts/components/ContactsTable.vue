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
import { listEntities } from '@/modules/entities/services/entityService'
import {
  listContactsResult,
  type ContactsListMeta,
} from '@/modules/contacts/services/contactService'
import type { Contact } from '@/modules/contacts/types/contact'

const router = useRouter()
const emit = defineEmits<{
  (e: 'create'): void
}>()

const contacts = ref<Contact[]>([])
const entities = ref<Array<{ id: number; name: string }>>([])
const loading = ref(false)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  entityId: 0,
})

const pagination = reactive<ContactsListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)

const tableColumns: ColumnDef<Record<string, unknown>, unknown>[] = [
  { accessorKey: 'first_name', header: 'Nome' },
  { accessorKey: 'last_name', header: 'Apelido' },
  { accessorKey: 'function_name', header: 'Função' },
  { accessorKey: 'entity_name', header: 'Entidade' },
  { accessorKey: 'phone', header: 'Telefone' },
  { accessorKey: 'mobile', header: 'Telemóvel' },
  { accessorKey: 'email', header: 'Email' },
]

const tableRows = computed<Record<string, unknown>[]>(() =>
  contacts.value.map((contact) => ({
    id: contact.id,
    first_name: contact.first_name || '-',
    last_name: contact.last_name || '-',
    function_name: contact.function?.name || '-',
    entity_name: contact.entity?.name || '-',
    phone: contact.phone || '-',
    mobile: contact.mobile || '-',
    email: contact.email || '-',
    is_active: contact.is_active,
  })),
)

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

async function loadFilterData(): Promise<void> {
  const entitiesList = await listEntities({ active_only: true })
  entities.value = entitiesList.map((entity) => ({ id: entity.id, name: entity.name }))
}

async function fetchContacts(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listContactsResult({
      page: pagination.current_page,
      per_page: pagination.per_page,
      search: searchDebounced.value || undefined,
      entity_id: filters.entityId || undefined,
      is_active: true,
    })
    contacts.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    contacts.value = []
    errorMessage.value = 'Não foi possível carregar os contactos.'
  } finally {
    loading.value = false
  }
}

function goToEdit(contactId: number): void {
  void router.push({ name: 'contacts.edit', params: { id: contactId } })
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
  () => [pagination.current_page, pagination.per_page, searchDebounced.value, filters.entityId],
  () => {
    void fetchContacts()
  },
)

onMounted(async () => {
  await loadFilterData()
  await fetchContacts()
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
          placeholder="Pesquisar por nome, email ou entidade..."
          class="w-[280px]"
          @update:model-value="queueSearch(String($event))"
        />
        <Select
          :model-value="filters.entityId ? String(filters.entityId) : '0'"
          @update:model-value="
            filters.entityId = Number($event);
            pagination.current_page = 1
          "
        >
          <SelectTrigger class="w-[220px]">
            <SelectValue placeholder="Filtrar por entidade" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="0">Todas as entidades</SelectItem>
            <SelectItem v-for="entity in entities" :key="entity.id" :value="String(entity.id)">
              {{ entity.name }}
            </SelectItem>
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

      <Button variant="outline" @click="emit('create')">Criar contacto</Button>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <DataTable
      :data="tableRows"
      :columns="tableColumns"
      :loading="loading"
      empty-message="Nenhum contacto encontrado."
      @row-click="onRowClick"
    />

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
