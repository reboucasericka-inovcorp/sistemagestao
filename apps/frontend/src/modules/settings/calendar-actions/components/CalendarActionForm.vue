<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  createCalendarAction,
  getCalendarActionById,
  listCalendarTypeOptions,
  updateCalendarAction,
  type CalendarTypeOption,
  type UpsertCalendarActionPayload,
} from '@/modules/settings/calendar-actions/services/calendarActionService'
import type { CalendarAction } from '@/modules/settings/calendar-actions/types/calendarAction'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.name === 'calendar-actions.new')
const calendarActionId = computed(() => Number(route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const calendarTypeOptions = ref<CalendarTypeOption[]>([])

const schema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  calendar_type_id: z.coerce.number().int().positive().optional().nullable(),
  is_active: z.boolean(),
})

type CalendarActionFormData = z.infer<typeof schema>

const defaultValues: CalendarActionFormData = {
  name: '',
  calendar_type_id: null,
  is_active: true,
}

const { resetForm, setFieldValue } = useForm<CalendarActionFormData>({
  validationSchema: toTypedSchema(schema),
  initialValues: defaultValues,
})

function applyBackendCalendarAction(payload: CalendarAction): void {
  resetForm({
    values: {
      name: payload.name,
      calendar_type_id: payload.calendar_type_id ?? null,
      is_active: payload.is_active,
    },
  })
}

async function loadCalendarActionIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(calendarActionId.value)) {
    return
  }
  const calendarAction = await getCalendarActionById(calendarActionId.value)
  applyBackendCalendarAction(calendarAction)
}

function toPayload(values: CalendarActionFormData): UpsertCalendarActionPayload {
  return {
    name: values.name,
    calendar_type_id: values.calendar_type_id || null,
    is_active: values.is_active,
  }
}

async function onSubmit(submittedValues: CalendarActionFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createCalendarAction(payload)
      await router.push('/settings/calendar-actions')
      return
    }
    await updateCalendarAction(calendarActionId.value, payload)
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Ação de calendário atualizada com sucesso.'
  } catch (error: unknown) {
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar a ação de calendário.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  pageLoading.value = true
  try {
    calendarTypeOptions.value = await listCalendarTypeOptions()
    await loadCalendarActionIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados da ação de calendário.'
  } finally {
    pageLoading.value = false
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Nova ação de calendário' : 'Editar ação de calendário' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as CalendarActionFormData)">
      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value }" name="calendar_type_id">
        <FormItem>
          <FormLabel>Tipo de calendário (opcional)</FormLabel>
          <FormControl>
            <Input
              :model-value="value == null ? '' : String(value)"
              type="number"
              min="1"
              :placeholder="calendarTypeOptions.length ? 'Ex: 1 (opcional)' : 'Opcional (sem tipos disponíveis)'"
              :disabled="pageLoading || submitLoading"
              @update:model-value="setFieldValue('calendar_type_id', $event ? Number($event) : null)"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="is_active">
        <FormItem class="check-item">
          <FormControl>
            <Checkbox
              :checked="Boolean(value)"
              :disabled="pageLoading || submitLoading"
              @update:checked="handleChange"
            />
          </FormControl>
          <FormLabel>Ativo</FormLabel>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="footer">
        <RouterLink to="/settings/calendar-actions">Cancelar</RouterLink>
        <Button :disabled="submitLoading || pageLoading" type="submit">
          {{ submitLoading ? 'A guardar...' : 'Guardar' }}
        </Button>
      </div>
    </Form>
  </div>
</template>

<style scoped>
.page {
  max-width: 42rem;
}

h1 {
  margin-top: 0;
  text-align: left;
}

.feedback {
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.success {
  color: #166534;
}

.error {
  color: #b91c1c;
}

.form {
  display: grid;
  gap: 0.9rem;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.footer {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-top: 0.5rem;
}
</style>
