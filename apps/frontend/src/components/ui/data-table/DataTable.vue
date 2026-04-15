<script setup lang="ts">
import { computed, ref } from 'vue'
import {
  FlexRender,
  getCoreRowModel,
  getSortedRowModel,
  type ColumnDef,
  type SortingState,
  useVueTable,
} from '@tanstack/vue-table'
import { valueUpdater } from '@/lib/utils'
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const props = withDefaults(
  defineProps<{
    data: Record<string, unknown>[]
    columns: ColumnDef<Record<string, unknown>, unknown>[]
    loading?: boolean
    emptyMessage?: string
  }>(),
  {
    loading: false,
    emptyMessage: 'Nenhum registo encontrado.',
  },
)

const emit = defineEmits<{
  (event: 'rowClick', row: Record<string, unknown>): void
}>()

const sorting = ref<SortingState>([])
const hasRows = computed(() => table.getRowModel().rows.length > 0)

const table = useVueTable({
  get data() {
    return props.data
  },
  get columns() {
    return props.columns
  },
  getCoreRowModel: getCoreRowModel(),
  getSortedRowModel: getSortedRowModel(),
  onSortingChange: (updaterOrValue) => valueUpdater(updaterOrValue, sorting),
  state: {
    get sorting() {
      return sorting.value
    },
  },
})

function onRowClick(row: Record<string, unknown>): void {
  emit('rowClick', row)
}
</script>

<template>
  <div class="rounded-md border">
    <Table>
      <TableHeader>
        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
          <TableHead v-for="header in headerGroup.headers" :key="header.id">
            <button
              v-if="!header.isPlaceholder && header.column.getCanSort()"
              class="inline-flex items-center gap-1"
              type="button"
              @click="header.column.toggleSorting(header.column.getIsSorted() === 'asc')"
            >
              <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
              <span class="text-xs text-muted-foreground">
                {{ header.column.getIsSorted() === 'asc' ? '▲' : header.column.getIsSorted() === 'desc' ? '▼' : '' }}
              </span>
            </button>
            <FlexRender v-else-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
          </TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <TableEmpty v-if="loading" :colspan="columns.length">A carregar registos...</TableEmpty>
        <TableEmpty v-else-if="!hasRows" :colspan="columns.length">{{ emptyMessage }}</TableEmpty>
        <TableRow
          v-for="row in table.getRowModel().rows"
          v-else
          :key="row.id"
          class="cursor-pointer"
          @click="onRowClick(row.original)"
        >
          <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>
  </div>
</template>
