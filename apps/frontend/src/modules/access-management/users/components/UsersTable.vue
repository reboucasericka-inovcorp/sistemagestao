<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { listRoles } from '@/modules/access-management/roles/services/roleService'
import type { AccessRole } from '@/modules/access-management/roles/types/role'
import { listUsersResult, toggleUserStatus, type UsersListMeta } from '@/modules/access-management/users/services/userService'
import type { AccessUser } from '@/modules/access-management/users/types/accessUser'

const router = useRouter()
const emit = defineEmits<{
  (e: 'create'): void
  (e: 'edit', id: number): void
}>()
const props = withDefaults(
  defineProps<{
    useEditModal?: boolean
  }>(),
  { useEditModal: false },
)
const users = ref<AccessUser[]>([])
const roles = ref<AccessRole[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({ roleId: 'all', perPage: 15 })
const pagination = reactive<UsersListMeta>({ current_page: 1, per_page: 15, total: 0, last_page: 1 })
const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)

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

async function fetchUsers(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listUsersResult({
      search: searchDebounced.value || undefined,
      role: filters.roleId !== 'all' ? Number(filters.roleId) : undefined,
      page: pagination.current_page,
      per_page: filters.perPage,
    })
    users.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    users.value = []
    errorMessage.value = 'Não foi possível carregar os utilizadores.'
  } finally {
    loading.value = false
  }
}

async function fetchRoles(): Promise<void> {
  try {
    roles.value = await listRoles()
  } catch {
    roles.value = []
  }
}

function goToEdit(userId: number): void {
  if (props.useEditModal) {
    emit('edit', userId)
    return
  }
  void router.push({ name: 'users.edit', params: { id: userId } })
}

async function onToggleStatus(user: AccessUser): Promise<void> {
  togglingId.value = user.id
  try {
    await toggleUserStatus(user.id, user.is_active)
    await fetchUsers()
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

watch(() => [pagination.current_page, filters.perPage, filters.roleId, searchDebounced.value], () => void fetchUsers())
onMounted(async () => {
  await fetchRoles()
  await fetchUsers()
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
          placeholder="Pesquisar por nome, email ou telefone..."
          class="w-[280px]"
          @update:model-value="queueSearch(String($event))"
        />
        <Select
          :model-value="filters.roleId"
          @update:model-value="
            filters.roleId = String($event);
            pagination.current_page = 1
          "
        >
          <SelectTrigger class="w-[240px]"><SelectValue placeholder="Filtrar por Grupo de Permissões" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="all">Todos os Grupos de Permissões</SelectItem>
            <SelectItem v-for="role in roles" :key="role.id" :value="String(role.id)">{{ role.name }}</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Button variant="outline" @click="emit('create')">Criar utilizador</Button>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nome</TableHead>
            <TableHead>Email</TableHead>
            <TableHead>Telemóvel</TableHead>
            <TableHead>Grupo de Permissões</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="6">A carregar utilizadores...</TableEmpty>
          <TableEmpty v-else-if="!users.length" :colspan="6">Nenhum utilizador encontrado.</TableEmpty>
          <TableRow v-for="user in users" v-else :key="user.id">
            <TableCell>{{ user.name }}</TableCell>
            <TableCell>{{ user.email }}</TableCell>
            <TableCell>{{ user.phone || '-' }}</TableCell>
            <TableCell>{{ user.role?.name || '-' }}</TableCell>
            <TableCell>{{ user.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" :disabled="loading" @click="goToEdit(user.id)">Editar</Button>
                <Button size="sm" variant="secondary" :disabled="loading || togglingId === user.id" @click="onToggleStatus(user)">
                  {{ user.is_active ? 'Inativar' : 'Ativar' }}
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
