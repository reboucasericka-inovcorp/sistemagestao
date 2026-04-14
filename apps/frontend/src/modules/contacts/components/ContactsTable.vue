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
import { listEntities } from '@/modules/entities/services/entityService'
import { listContactRoles, type ContactRole } from '@/modules/contacts/services/contactRoleService'
import {
  listContactsResult,
  toggleContactStatus,
  type ContactsListMeta,
} from '@/modules/contacts/services/contactService'
import type { Contact } from '@/modules/contacts/types/contact'

const router = useRouter()
const emit = defineEmits<{
  (e: 'create'): void
}>()

const contacts = ref<Contact[]>([])
const entities = ref<Array<{ id: number; name: string }>>([])
const roles = ref<ContactRole[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  entityId: 0,
  roleId: 0,
})

const pagination = reactive<ContactsListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)

const tableContacts = computed(() => {
  if (!filters.roleId) {
    return contacts.value
  }
  return contacts.value.filter((contact) => (contact.contact_function_id ?? 0) === filters.roleId)
})

function getNameParts(contact: Contact): { firstName: string; lastName: string } {
  const fullName = (contact.name ?? '').trim()
  if (!fullName) {
    return { firstName: '-', lastName: '-' }
  }
  const parts = fullName.split(/\s+/)
  if (parts.length === 1) {
    return { firstName: parts[0], lastName: '-' }
  }
  return {
    firstName: parts[0],
    lastName: parts.slice(1).join(' '),
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

async function loadFilterData(): Promise<void> {
  const [entitiesList, rolesList] = await Promise.all([listEntities({ active_only: true }), listContactRoles()])
  entities.value = entitiesList.map((entity) => ({ id: entity.id, name: entity.name }))
  roles.value = rolesList
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

async function onToggleStatus(contact: Contact): Promise<void> {
  togglingId.value = contact.id
  try {
    await toggleContactStatus(contact.id, contact.is_active)
    await fetchContacts()
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
  () => [pagination.current_page, pagination.per_page, searchDebounced.value, filters.entityId],
  () => {
    void fetchContacts()
  },
)

watch(
  () => filters.roleId,
  () => {
    pagination.current_page = 1
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
          :model-value="filters.roleId ? String(filters.roleId) : '0'"
          @update:model-value="
            filters.roleId = Number($event);
            pagination.current_page = 1
          "
        >
          <SelectTrigger class="w-[200px]">
            <SelectValue placeholder="Filtrar por função" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="0">Todas as funções</SelectItem>
            <SelectItem v-for="role in roles" :key="role.id" :value="String(role.id)">
              {{ role.name }}
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

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nome</TableHead>
            <TableHead>Apelido</TableHead>
            <TableHead>Função</TableHead>
            <TableHead>Entidade</TableHead>
            <TableHead>Telefone</TableHead>
            <TableHead>Telemóvel</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="9">A carregar contactos...</TableEmpty>
          <TableEmpty v-else-if="!tableContacts.length" :colspan="9">Nenhum contacto encontrado.</TableEmpty>
          <TableRow v-for="contact in tableContacts" v-else :key="contact.id">
            <TableCell>{{ getNameParts(contact).firstName }}</TableCell>
            <TableCell>{{ getNameParts(contact).lastName }}</TableCell>
            <TableCell>{{ contact.contactFunction?.name || '-' }}</TableCell>
            <TableCell>{{ contact.entity?.name || '-' }}</TableCell>
            <TableCell>{{ contact.phone || '-' }}</TableCell>
            <TableCell>{{ contact.mobile || '-' }}</TableCell>
            <TableCell>{{ contact.email || '-' }}</TableCell>
            <TableCell>{{ contact.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" :disabled="loading" @click="goToEdit(contact.id)">Editar</Button>
                <Button
                  size="sm"
                  variant="secondary"
                  :disabled="loading || togglingId === contact.id"
                  @click="onToggleStatus(contact)"
                >
                  {{ contact.is_active ? 'Inativar' : 'Ativar' }}
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
