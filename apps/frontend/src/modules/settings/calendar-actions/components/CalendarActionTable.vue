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
  listCalendarActionsResult,
  toggleCalendarActionStatus,
  type CalendarActionsListMeta,
} from '@/modules/settings/calendar-actions/services/calendarActionService'
import type { CalendarAction } from '@/modules/settings/calendar-actions/types/calendarAction'

const router = useRouter()

const calendarActions = ref<CalendarAction[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  perPage: 15,
})

const pagination = reactive<CalendarActionsListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

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

async function fetchCalendarActions(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listCalendarActionsResult({
      search: searchDebounced.value || undefined,
      page: pagination.current_page,
      per_page: filters.perPage,
    })
    calendarActions.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    calendarActions.value = []
    errorMessage.value = 'Não foi possível carregar as ações de calendário.'
  } finally {
    loading.value = false
  }
}

function goToEdit(calendarActionId: number): void {
  void router.push({ name: 'calendar-actions.edit', params: { id: calendarActionId } })
}

async function onToggleStatus(calendarAction: CalendarAction): Promise<void> {
  togglingId.value = calendarAction.id
  try {
    await toggleCalendarActionStatus(calendarAction.id, calendarAction.is_active)
    await fetchCalendarActions()
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
  () => [pagination.current_page, filters.perPage, searchDebounced.value],
  () => {
    void fetchCalendarActions()
  },
)

onMounted(() => {
  void fetchCalendarActions()
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

      <Button variant="outline" @click="router.push('/settings/calendar-actions/new')">Criar ação</Button>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nome</TableHead>
            <TableHead>Tipo calendário</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="4">A carregar ações...</TableEmpty>
          <TableEmpty v-else-if="!calendarActions.length" :colspan="4">Nenhum registo encontrado.</TableEmpty>
          <TableRow v-for="calendarAction in calendarActions" v-else :key="calendarAction.id">
            <TableCell>{{ calendarAction.name }}</TableCell>
            <TableCell>{{ calendarAction.calendar_type?.name || calendarAction.calendar_type_id || '-' }}</TableCell>
            <TableCell>{{ calendarAction.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" :disabled="loading" @click="goToEdit(calendarAction.id)">Editar</Button>
                <Button
                  size="sm"
                  variant="secondary"
                  :disabled="loading || togglingId === calendarAction.id"
                  @click="onToggleStatus(calendarAction)"
                >
                  {{ calendarAction.is_active ? 'Inativar' : 'Ativar' }}
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
