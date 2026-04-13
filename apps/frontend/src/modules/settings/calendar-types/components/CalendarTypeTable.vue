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
  listCalendarTypesResult,
  toggleCalendarTypeStatus,
  type CalendarTypesListMeta,
} from '@/modules/settings/calendar-types/services/calendarTypeService'
import type { CalendarType } from '@/modules/settings/calendar-types/types/calendarType'

const router = useRouter()

const calendarTypes = ref<CalendarType[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  perPage: 15,
})

const pagination = reactive<CalendarTypesListMeta>({
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

async function fetchCalendarTypes(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listCalendarTypesResult({
      search: searchDebounced.value || undefined,
      page: pagination.current_page,
      per_page: filters.perPage,
    })
    calendarTypes.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    calendarTypes.value = []
    errorMessage.value = 'Não foi possível carregar os tipos de calendário.'
  } finally {
    loading.value = false
  }
}

function goToEdit(calendarTypeId: number): void {
  void router.push({ name: 'calendar-types.edit', params: { id: calendarTypeId } })
}

async function onToggleStatus(calendarType: CalendarType): Promise<void> {
  togglingId.value = calendarType.id
  try {
    await toggleCalendarTypeStatus(calendarType.id, calendarType.is_active)
    await fetchCalendarTypes()
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
    void fetchCalendarTypes()
  },
)

onMounted(() => {
  void fetchCalendarTypes()
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

      <Button variant="outline" @click="router.push('/settings/calendar-types/new')">Criar tipo</Button>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Nome</TableHead>
            <TableHead>Cor</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="4">A carregar tipos...</TableEmpty>
          <TableEmpty v-else-if="!calendarTypes.length" :colspan="4">Nenhum registo encontrado.</TableEmpty>
          <TableRow v-for="calendarType in calendarTypes" v-else :key="calendarType.id">
            <TableCell>{{ calendarType.name }}</TableCell>
            <TableCell>{{ calendarType.color || '-' }}</TableCell>
            <TableCell>{{ calendarType.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" :disabled="loading" @click="goToEdit(calendarType.id)">Editar</Button>
                <Button
                  size="sm"
                  variant="secondary"
                  :disabled="loading || togglingId === calendarType.id"
                  @click="onToggleStatus(calendarType)"
                >
                  {{ calendarType.is_active ? 'Inativar' : 'Ativar' }}
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
