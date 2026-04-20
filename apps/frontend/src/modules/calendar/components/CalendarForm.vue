<script setup lang="ts">
import { computed, watch } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import type {
  CalendarActionOption,
  CalendarEntityOption,
  CalendarEventItem,
  CalendarTypeOption,
  UpsertCalendarEventPayload,
} from '@/modules/calendar/types/calendar'

const props = defineProps<{
  loading?: boolean
  event?: CalendarEventItem | null
  entities: CalendarEntityOption[]
  types: CalendarTypeOption[]
  actions: CalendarActionOption[]
  defaultDate?: string
}>()

const emit = defineEmits<{
  (e: 'cancel'): void
  (e: 'submit', value: { id?: number; payload: UpsertCalendarEventPayload }): void
  (e: 'delete', id: number): void
}>()

const formSchema = z.object({
  date: z.string().min(1, 'Data é obrigatória'),
  time: z.string().min(1, 'Hora é obrigatória'),
  duration: z.coerce.number().int().positive('Duração deve ser maior que zero'),
  entity_id: z.coerce.number().int().positive('Entidade é obrigatória'),
  type_id: z.coerce.number().int().positive().nullable().optional(),
  action_id: z.coerce.number().int().positive().nullable().optional(),
  description: z.string().optional(),
  is_active: z.boolean(),
})

type FormData = z.infer<typeof formSchema>

const { resetForm, setFieldValue, values } = useForm<FormData>({
  validationSchema: toTypedSchema(formSchema),
  initialValues: {
    date: props.defaultDate ?? '',
    time: '09:00',
    duration: 30,
    entity_id: 0,
    type_id: null,
    action_id: null,
    description: '',
    is_active: true,
  },
})

const isEditing = computed(() => Boolean(props.event?.id))

watch(
  () => props.event,
  (eventValue) => {
    if (!eventValue) {
      resetForm({
        values: {
          date: props.defaultDate ?? '',
          time: '09:00',
          duration: 30,
          entity_id: 0,
          type_id: null,
          action_id: null,
          description: '',
          is_active: true,
        },
      })
      return
    }
    resetForm({
      values: {
        date: eventValue.date,
        time: eventValue.time,
        duration: eventValue.duration,
        entity_id: eventValue.entity_id,
        type_id: eventValue.type_id ?? null,
        action_id: eventValue.action_id ?? null,
        description: eventValue.description ?? '',
        is_active: eventValue.is_active,
      },
    })
  },
  { immediate: true },
)

watch(
  [() => values.action_id, () => props.actions],
  ([actionId]) => {
    if (!actionId) {
      return
    }
    const selectedAction = props.actions.find((action) => action.id === Number(actionId))
    if (selectedAction?.calendar_type_id) {
      setFieldValue('type_id', Number(selectedAction.calendar_type_id))
    }
  },
  { immediate: true },
)

function toPayload(formValues: FormData): UpsertCalendarEventPayload {
  const entityId = Number(formValues.entity_id)
  const typeId = formValues.type_id != null ? Number(formValues.type_id) : null
  const actionId = formValues.action_id != null ? Number(formValues.action_id) : null

  return {
    date: formValues.date,
    time: formValues.time,
    duration: Number(formValues.duration),
    entity_id: Number.isFinite(entityId) && entityId > 0 ? entityId : 0,
    type_id: typeId && Number.isFinite(typeId) ? typeId : null,
    action_id: actionId && Number.isFinite(actionId) ? actionId : null,
    description: formValues.description?.trim() ? formValues.description.trim() : null,
    is_active: Boolean(formValues.is_active),
  }
}

function submit(): void {
  const payload = toPayload({
    date: String(values.date ?? ''),
    time: String(values.time ?? ''),
    duration: Number(values.duration ?? 0),
    entity_id: Number(values.entity_id ?? 0),
    type_id: values.type_id != null ? Number(values.type_id) : null,
    action_id: values.action_id != null ? Number(values.action_id) : null,
    description: values.description != null ? String(values.description) : '',
    is_active: values.is_active === true,
  })
  console.log('calendar payload', payload)

  emit('submit', {
    id: props.event?.id,
    payload,
  })
}

function handleDelete(): void {
  const eventId = props.event?.id
  if (!eventId) return

  emit('delete', eventId)
}
</script>

<template>
  <div class="space-y-3">
    <h2 class="text-lg font-semibold">{{ isEditing ? 'Editar evento' : 'Novo evento' }}</h2>

    <Form class="grid gap-3" @submit="submit">
      <FormField v-slot="{ componentField }" name="date">
        <FormItem>
          <FormLabel>Data</FormLabel>
          <FormControl><Input type="date" v-bind="componentField" :disabled="loading" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="time">
        <FormItem>
          <FormLabel>Hora</FormLabel>
          <FormControl><Input type="time" v-bind="componentField" :disabled="loading" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="duration">
        <FormItem>
          <FormLabel>Duração (minutos)</FormLabel>
          <FormControl>
            <Input
              type="number"
              min="1"
              step="1"
              :model-value="String(value ?? '')"
              :disabled="loading"
              @update:model-value="(rawValue) => handleChange(Number(rawValue))"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="entity_id">
        <FormItem>
          <FormLabel>Entidade</FormLabel>
          <Select
            :model-value="String(value || '')"
            :disabled="loading"
            @update:model-value="(selected) => handleChange(Number(selected))"
          >
            <FormControl><SelectTrigger><SelectValue placeholder="Selecione a entidade" /></SelectTrigger></FormControl>
            <SelectContent>
              <SelectItem v-for="entity in props.entities" :key="entity.id" :value="String(entity.id)">{{ entity.name }}</SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="type_id">
        <FormItem>
          <FormLabel>Tipo</FormLabel>
          <Select
            :model-value="value ? String(value) : ''"
            :disabled="loading"
            @update:model-value="(selected) => handleChange(selected ? Number(selected) : null)"
          >
            <FormControl><SelectTrigger><SelectValue placeholder="Selecione o tipo" /></SelectTrigger></FormControl>
            <SelectContent>
              <SelectItem v-for="type in props.types" :key="type.id" :value="String(type.id)">{{ type.name }}</SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="action_id">
        <FormItem>
          <FormLabel>Ação</FormLabel>
          <Select
            :model-value="value ? String(value) : ''"
            :disabled="loading"
            @update:model-value="(selected) => handleChange(selected ? Number(selected) : null)"
          >
            <FormControl><SelectTrigger><SelectValue placeholder="Selecione a ação" /></SelectTrigger></FormControl>
            <SelectContent>
              <SelectItem v-for="action in props.actions" :key="action.id" :value="String(action.id)">{{ action.name }}</SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="description">
        <FormItem>
          <FormLabel>Descrição</FormLabel>
          <FormControl><Textarea v-bind="componentField" :disabled="loading" /></FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="is_active">
        <FormItem class="flex items-center gap-2">
          <FormControl><Checkbox :checked="Boolean(value)" :disabled="loading" @update:checked="handleChange" /></FormControl>
          <FormLabel>Ativo</FormLabel>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="flex items-center justify-end gap-2">
        <Button type="button" variant="outline" :disabled="loading" @click="emit('cancel')">Cancelar</Button>
        <Button v-if="isEditing && props.event?.id" type="button" variant="destructive" :disabled="loading" @click="handleDelete">
          Apagar
        </Button>
        <Button type="submit" :disabled="loading">{{ loading ? 'A guardar...' : 'Guardar' }}</Button>
      </div>
    </Form>
  </div>
</template>
