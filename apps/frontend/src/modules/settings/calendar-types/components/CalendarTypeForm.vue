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
  createCalendarType,
  getCalendarTypeById,
  updateCalendarType,
  type UpsertCalendarTypePayload,
} from '@/modules/settings/calendar-types/services/calendarTypeService'
import type { CalendarType } from '@/modules/settings/calendar-types/types/calendarType'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.name === 'calendar-types.new')
const calendarTypeId = computed(() => Number(route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const hexPattern = /^#[0-9A-Fa-f]{6}$/

const calendarTypeSchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  color: z
    .string()
    .trim()
    .optional()
    .refine((value) => !value || hexPattern.test(value), 'Cor deve estar no formato #RRGGBB'),
  is_active: z.boolean(),
})

type CalendarTypeFormData = z.infer<typeof calendarTypeSchema>

const defaultValues: CalendarTypeFormData = {
  name: '',
  color: '',
  is_active: true,
}

const { resetForm } = useForm<CalendarTypeFormData>({
  validationSchema: toTypedSchema(calendarTypeSchema),
  initialValues: defaultValues,
})

function applyBackendCalendarType(payload: CalendarType): void {
  resetForm({
    values: {
      name: payload.name,
      color: payload.color ?? '',
      is_active: payload.is_active,
    },
  })
}

async function loadCalendarTypeIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(calendarTypeId.value)) {
    return
  }
  const calendarType = await getCalendarTypeById(calendarTypeId.value)
  applyBackendCalendarType(calendarType)
}

function toPayload(values: CalendarTypeFormData): UpsertCalendarTypePayload {
  return {
    name: values.name,
    color: values.color?.trim() || null,
    is_active: values.is_active,
  }
}

async function onSubmit(submittedValues: CalendarTypeFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createCalendarType(payload)
      await router.push('/settings/calendar-types')
      return
    }
    await updateCalendarType(calendarTypeId.value, payload)
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Tipo de calendário atualizado com sucesso.'
  } catch (error: unknown) {
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar o tipo de calendário.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  pageLoading.value = true
  try {
    await loadCalendarTypeIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados do tipo de calendário.'
  } finally {
    pageLoading.value = false
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo tipo de calendário' : 'Editar tipo de calendário' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as CalendarTypeFormData)">
      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="color">
        <FormItem>
          <FormLabel>Cor (hex)</FormLabel>
          <FormControl>
            <Input v-bind="componentField" placeholder="#FF0000" :disabled="pageLoading || submitLoading" />
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
        <RouterLink to="/settings/calendar-types">Cancelar</RouterLink>
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
