<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import timeGridPlugin from '@fullcalendar/timegrid'
import interactionPlugin, { type DateClickArg } from '@fullcalendar/interaction'
import type { EventClickArg } from '@fullcalendar/core'
import ptBrLocale from '@fullcalendar/core/locales/pt-br'

import { Button } from '@/components/ui/button'
import CalendarFilters from '@/modules/calendar/components/CalendarFilters.vue'
import CalendarForm from '@/modules/calendar/components/CalendarForm.vue'

import {
  createEvent,
  deleteEvent,
  listActionOptions,
  listEntityOptions,
  listEvents,
  listTypeOptions,
  listUserOptions,
  updateEvent,
} from '@/modules/calendar/services/calendarService'

import type {
  CalendarActionOption,
  CalendarEntityOption,
  CalendarEventItem,
  CalendarFilters as CalendarFiltersType,
  CalendarTypeOption,
  CalendarUserOption,
  UpsertCalendarEventPayload,
} from '@/modules/calendar/types/calendar'

const events = ref<CalendarEventItem[]>([])
const entities = ref<CalendarEntityOption[]>([])
const users = ref<CalendarUserOption[]>([])
const types = ref<CalendarTypeOption[]>([])
const actions = ref<CalendarActionOption[]>([])

const loading = ref(false)
const saving = ref(false)
const errorMessage = ref('')

const filters = reactive<CalendarFiltersType>({})
const modalOpen = ref(false)
const selectedDate = ref('')
const editingEvent = ref<CalendarEventItem | null>(null)

const calendarEvents = computed(() =>
  events.value.map((eventItem) => ({
    id: String(eventItem.id),
    title: eventItem.title,
    start: eventItem.start,
    end: eventItem.end,
    color: eventItem.color || '#2563eb',
  })),
)

function openCreateModal(dateValue: string): void {
  selectedDate.value = dateValue
  editingEvent.value = null
  modalOpen.value = true
}

function openEditModal(eventId: string): void {
  const found = events.value.find((eventItem) => String(eventItem.id) === eventId) ?? null

  if (!found) return

  editingEvent.value = found
  selectedDate.value = found.date
  modalOpen.value = true
}

function handleDateClick(arg: DateClickArg): void {
  openCreateModal(arg.dateStr)
}

function handleEventClick(arg: EventClickArg): void {
  openEditModal(arg.event.id)
}

function closeModal(): void {
  modalOpen.value = false
}

async function fetchEvents(): Promise<void> {
  loading.value = true
  errorMessage.value = ''

  try {
    events.value = await listEvents(filters)
  } catch {
    events.value = []
    errorMessage.value = 'Não foi possível carregar os eventos do calendário.'
  } finally {
    loading.value = false
  }
}

async function fetchOptions(): Promise<void> {
  const [entitiesResult, usersResult, typesResult, actionsResult] = await Promise.allSettled([
    listEntityOptions(),
    listUserOptions(),
    listTypeOptions(),
    listActionOptions(),
  ])

  entities.value = entitiesResult.status === 'fulfilled' ? entitiesResult.value : []
  users.value = usersResult.status === 'fulfilled' ? usersResult.value : []
  types.value = typesResult.status === 'fulfilled' ? typesResult.value : []
  actions.value = actionsResult.status === 'fulfilled' ? actionsResult.value : []

  if (
    entitiesResult.status === 'rejected' ||
    usersResult.status === 'rejected' ||
    typesResult.status === 'rejected' ||
    actionsResult.status === 'rejected'
  ) {
    errorMessage.value =
      'Algumas listas do calendário não foram carregadas. Verifique permissões da API.'
  }
}

async function onSubmitForm(data: {
  id?: number
  payload: UpsertCalendarEventPayload
}): Promise<void> {
  saving.value = true
  errorMessage.value = ''

  try {
    if (data.id) {
      await updateEvent(data.id, data.payload)
    } else {
      await createEvent(data.payload)
    }

    await fetchEvents()
    closeModal()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || 'Erro ao guardar evento'

    // 👉 FECHA MESMO COM ERRO
    closeModal()
  } finally {
    saving.value = false
  }
}

async function onDeleteEvent(id: number): Promise<void> {
  saving.value = true
  errorMessage.value = ''

  try {
    await deleteEvent(id)
    closeModal()
    await fetchEvents()
  } catch {
    errorMessage.value = 'Não foi possível inativar o evento.'
  } finally {
    saving.value = false
  }
}

function onFiltersChange(nextFilters: CalendarFiltersType): void {
  filters.user_id = nextFilters.user_id
  filters.entity_id = nextFilters.entity_id
  void fetchEvents()
}

const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay',
  },
  events: calendarEvents.value,
  height: 700,
  contentHeight: 'auto',
  locales: [ptBrLocale],
  locale: 'pt-br',
  dateClick: handleDateClick,
  eventClick: handleEventClick,
}))

onMounted(async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    await fetchOptions()
    await fetchEvents()
  } catch {
    errorMessage.value = 'Não foi possível carregar o módulo de calendário.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
      <h1 class="text-left text-2xl font-semibold">Calendário</h1>

      <Button
        variant="outline"
        :disabled="loading"
        @click="openCreateModal(new Date().toISOString().slice(0, 10))"
      >
        Novo evento
      </Button>
    </div>

    <CalendarFilters
      :users="users"
      :entities="entities"
      :loading="loading"
      @change="onFiltersChange"
    />

    <p
      v-if="errorMessage"
      class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
    >
      {{ errorMessage }}
    </p>

    <div class="rounded-md border bg-background p-2">
      <FullCalendar :options="calendarOptions" />
    </div>

    <div
      v-if="modalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
    >
      <div class="w-full max-w-2xl rounded-md border bg-background p-4 shadow-lg">
        <CalendarForm
          :loading="saving"
          :event="editingEvent"
          :default-date="selectedDate"
          :entities="entities"
          :types="types"
          :actions="actions"
          @cancel="closeModal"
          @submit="onSubmitForm"
          @delete="onDeleteEvent"
        />
      </div>
    </div>
  </div>
</template>
