<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { listRolesResult, toggleRoleStatus, type RolesListMeta } from '@/modules/access-management/roles/services/roleService'
import type { AccessRole } from '@/modules/access-management/roles/types/role'

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
const roles = ref<AccessRole[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({ perPage: 15 })
const pagination = reactive<RolesListMeta>({ current_page: 1, per_page: 15, total: 0, last_page: 1 })
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

async function fetchRoles(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listRolesResult({
      search: searchDebounced.value || undefined,
      page: pagination.current_page,
      per_page: filters.perPage,
    })
    roles.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    roles.value = []
    errorMessage.value = 'Não foi possível carregar os grupos de permissões.'
  } finally {
    loading.value = false
  }
}

function goToEdit(roleId: number): void {
  if (props.useEditModal) {
    emit('edit', roleId)
    return
  }
  void router.push({ name: 'roles.edit', params: { id: roleId } })
}

async function onToggleStatus(role: AccessRole): Promise<void> {
  togglingId.value = role.id
  try {
    await toggleRoleStatus(role.id, role.is_active)
    await fetchRoles()
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

watch(() => [pagination.current_page, filters.perPage, searchDebounced.value], () => void fetchRoles())
onMounted(() => void fetchRoles())
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
          placeholder="Pesquisar por nome..."
          class="w-[280px]"
          @update:model-value="queueSearch(String($event))"
        />
        <Select
          :model-value="String(filters.perPage)"
          @update:model-value="
            filters.perPage = Number($event);
            pagination.current_page = 1
          "
        >
          <SelectTrigger class="w-[120px]"><SelectValue placeholder="Por página" /></SelectTrigger>
          <SelectContent>
            <SelectItem value="10">10</SelectItem>
            <SelectItem value="15">15</SelectItem>
            <SelectItem value="25">25</SelectItem>
            <SelectItem value="50">50</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <Button variant="outline" @click="emit('create')">Criar grupo</Button>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nome</TableHead>
            <TableHead>Permissões</TableHead>
            <TableHead>Utilizadores</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="5">A carregar grupos...</TableEmpty>
          <TableEmpty v-else-if="!roles.length" :colspan="5">Nenhum grupo encontrado.</TableEmpty>
          <TableRow v-for="role in roles" v-else :key="role.id">
            <TableCell>{{ role.name }}</TableCell>
            <TableCell>{{ role.permissions.length }}</TableCell>
            <TableCell>{{ role.users_count }}</TableCell>
            <TableCell>{{ role.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" :disabled="loading" @click="goToEdit(role.id)">Editar</Button>
                <Button size="sm" variant="secondary" :disabled="loading || togglingId === role.id" @click="onToggleStatus(role)">
                  {{ role.is_active ? 'Inativar' : 'Ativar' }}
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
