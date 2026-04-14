<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
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
import { listLogsResult, type LogsListMeta } from '@/modules/settings/logs/services/logService'
import type { Log, LogAction } from '@/modules/settings/logs/types/log'

const logs = ref<Log[]>([])
const loading = ref(false)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  user: '',
  menu: '',
  action: '' as '' | LogAction,
  dateFrom: '',
  dateTo: '',
  perPage: 15,
})

const pagination = reactive<LogsListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)

const userOptions = computed(() =>
  Array.from(new Set(logs.value.map((item) => item.user_name).filter((value): value is string => Boolean(value)))),
)
const menuOptions = computed(() =>
  Array.from(new Set(logs.value.map((item) => item.menu).filter((value) => value && value !== '-'))),
)
const actionOptions: LogAction[] = ['created', 'updated', 'deleted']

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

async function fetchLogs(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listLogsResult({
      search: searchDebounced.value || undefined,
      user: filters.user || undefined,
      menu: filters.menu || undefined,
      action: filters.action || undefined,
      date_from: filters.dateFrom || undefined,
      date_to: filters.dateTo || undefined,
      page: pagination.current_page,
      per_page: filters.perPage,
    })
    logs.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    logs.value = []
    errorMessage.value = 'Não foi possível carregar os logs.'
  } finally {
    loading.value = false
  }
}

function goToPage(page: number): void {
  if (page < 1 || page > pagination.last_page) {
    return
  }
  pagination.current_page = page
}

watch(
  () => [
    pagination.current_page,
    filters.perPage,
    filters.user,
    filters.menu,
    filters.action,
    filters.dateFrom,
    filters.dateTo,
    searchDebounced.value,
  ],
  () => {
    void fetchLogs()
  },
)

onMounted(() => {
  void fetchLogs()
})

onBeforeUnmount(() => {
  if (debounceId.value != null) {
    window.clearTimeout(debounceId.value)
  }
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center gap-2">
      <Input
        :model-value="searchText"
        placeholder="Pesquisar logs..."
        class="w-[260px]"
        @update:model-value="queueSearch(String($event))"
      />

      <Select
        :model-value="filters.user"
        @update:model-value="
          filters.user = String($event);
          pagination.current_page = 1
        "
      >
        <SelectTrigger class="w-[190px]">
          <SelectValue placeholder="Utilizador" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="">Todos utilizadores</SelectItem>
          <SelectItem v-for="user in userOptions" :key="user" :value="user">{{ user }}</SelectItem>
        </SelectContent>
      </Select>

      <Input
        :model-value="filters.dateFrom"
        type="date"
        class="w-[170px]"
        @update:model-value="
          filters.dateFrom = String($event);
          pagination.current_page = 1
        "
      />

      <Input
        :model-value="filters.dateTo"
        type="date"
        class="w-[170px]"
        @update:model-value="
          filters.dateTo = String($event);
          pagination.current_page = 1
        "
      />

      <Select
        :model-value="filters.menu"
        @update:model-value="
          filters.menu = String($event);
          pagination.current_page = 1
        "
      >
        <SelectTrigger class="w-[170px]">
          <SelectValue placeholder="Menu" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="">Todos menus</SelectItem>
          <SelectItem v-for="menu in menuOptions" :key="menu" :value="menu">{{ menu }}</SelectItem>
        </SelectContent>
      </Select>

      <Select
        :model-value="filters.action"
        @update:model-value="
          filters.action = String($event);
          pagination.current_page = 1
        "
      >
        <SelectTrigger class="w-[170px]">
          <SelectValue placeholder="Ação" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="">Todas ações</SelectItem>
          <SelectItem v-for="action in actionOptions" :key="action" :value="action">{{ action }}</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Data</TableHead>
            <TableHead>Hora</TableHead>
            <TableHead>Utilizador</TableHead>
            <TableHead>Menu</TableHead>
            <TableHead>Ação</TableHead>
            <TableHead>Dispositivo</TableHead>
            <TableHead>IP</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="7">A carregar logs...</TableEmpty>
          <TableEmpty v-else-if="!logs.length" :colspan="7">Sem registos de logs.</TableEmpty>
          <TableRow v-for="log in logs" v-else :key="log.id">
            <TableCell>{{ log.date }}</TableCell>
            <TableCell>{{ log.time }}</TableCell>
            <TableCell>{{ log.user_name || '-' }}</TableCell>
            <TableCell>{{ log.menu }}</TableCell>
            <TableCell>{{ log.action }}</TableCell>
            <TableCell>{{ log.device || '-' }}</TableCell>
            <TableCell>{{ log.ip_address || '-' }}</TableCell>
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
