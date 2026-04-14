<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import {
  listRolesResult,
  toggleRoleStatus,
  type RolesListMeta,
} from '@/modules/access-management/roles/services/roleService'
import type { PermissionGroup } from '@/modules/access-management/permissions/types/permissionGroup'

const router = useRouter()
const emit = defineEmits<{
  (e: 'create'): void
}>()
const groups = ref<PermissionGroup[]>([])
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

async function fetchPermissionGroups(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listRolesResult({
      search: searchDebounced.value || undefined,
      page: pagination.current_page,
      per_page: filters.perPage,
    })
    groups.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    groups.value = []
    errorMessage.value = 'Não foi possível carregar os grupos de permissões.'
  } finally {
    loading.value = false
  }
}

function goToEdit(groupId: number): void {
  void router.push({ name: 'permissions.edit', params: { id: groupId } })
}

async function onToggleStatus(group: PermissionGroup): Promise<void> {
  togglingId.value = group.id
  try {
    await toggleRoleStatus(group.id, group.is_active)
    await fetchPermissionGroups()
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

watch(() => [pagination.current_page, filters.perPage, searchDebounced.value], () => void fetchPermissionGroups())
onMounted(() => void fetchPermissionGroups())
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
          placeholder="Pesquisar por nome do grupo..."
          class="w-[300px]"
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
            <TableHead>Nome do Grupo</TableHead>
            <TableHead>Utilizadores Relacionados</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="4">A carregar grupos...</TableEmpty>
          <TableEmpty v-else-if="!groups.length" :colspan="4">Nenhum grupo encontrado.</TableEmpty>
          <template v-else>
            <TableRow v-for="group in groups" :key="group.id">
              <TableCell>{{ group.name }}</TableCell>
              <TableCell>{{ group.users_count }}</TableCell>
              <TableCell>{{ group.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
              <TableCell class="text-right">
                <div class="flex justify-end gap-2">
                  <Button size="sm" variant="outline" :disabled="loading" @click="goToEdit(group.id)">Editar</Button>
                  <Button size="sm" variant="secondary" :disabled="loading || togglingId === group.id" @click="onToggleStatus(group)">
                    {{ group.is_active ? 'Inativar' : 'Ativar' }}
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </template>
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
