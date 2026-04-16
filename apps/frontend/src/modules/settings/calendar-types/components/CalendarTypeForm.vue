<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
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
const props = withDefaults(
  defineProps<{
    mode?: 'create' | 'edit'
    recordId?: number | null
    open?: boolean
  }>(),
  { mode: undefined, recordId: null, open: undefined },
)
const emit = defineEmits<{
  (e: 'success'): void
  (e: 'cancel'): void
}>()
const isNew = computed(() =>
  props.mode ? props.mode === 'create' : route.name === 'calendar-types.new',
)
const calendarTypeId = computed(() => Number(props.recordId ?? route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const hexPattern = /^#[0-9A-Fa-f]{6}$/
const defaultPreviewColor = '#000000'

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

const { setValues, setErrors, values } = useForm<CalendarTypeFormData>({
  validationSchema: toTypedSchema(calendarTypeSchema),
  initialValues: defaultValues,
})

async function applyBackendCalendarType(payload: CalendarType): Promise<void> {
  await nextTick()
  setValues({
    name: payload.name ?? '',
    color: payload.color ?? '',
    is_active: payload.is_active ?? true,
  })
  console.log('FORM VALUES:', values)
}

async function loadCalendarTypeIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(calendarTypeId.value)) {
    return
  }
  const calendarType = await getCalendarTypeById(calendarTypeId.value)
  console.log('RAW API RESULT:', calendarType)
  const normalized = (calendarType as CalendarType & { data?: CalendarType })?.data ?? calendarType
  console.log('NORMALIZED:', normalized)
  await applyBackendCalendarType(normalized as CalendarType)
}

function toPayload(values: CalendarTypeFormData): UpsertCalendarTypePayload {
  return {
    name: values.name,
    color: values.color?.trim() || null,
    is_active: values.is_active,
  }
}

function applyLaravelValidationErrors(error: unknown): void {
  if (typeof error !== 'object' || !error || !('response' in error)) {
    return
  }
  const errors = (
    error as { response?: { data?: { errors?: Record<string, string[] | undefined> } } }
  ).response?.data?.errors
  if (!errors) {
    return
  }
  setErrors({
    name: errors.name?.[0],
    color: errors.color?.[0],
    is_active: errors.is_active?.[0],
  })
}

async function onSubmit(submittedValues: CalendarTypeFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createCalendarType(payload)
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push('/settings/calendar-types')
      }
      return
    }
    await updateCalendarType(calendarTypeId.value, payload)
    if (props.mode === 'edit') {
      emit('success')
      return
    }
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Tipo de calendário atualizado com sucesso.'
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
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

const submitWithValidation = async (submittedValues: CalendarTypeFormData) => {
  await onSubmit(submittedValues)
}

onMounted(async () => {
  await nextTick()
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

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return
    feedbackKind.value = ''
    feedbackMessage.value = ''
    if (isNew.value) {
      await nextTick()
      setValues(defaultValues)
      console.log('FORM VALUES:', values)
      return
    }
    await loadCalendarTypeIfEditing()
  },
  { immediate: true },
)
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo tipo de calendário' : 'Editar tipo de calendário' }}</h1>

    <p
      v-if="feedbackMessage"
      :class="feedbackKind === 'error' ? 'error' : 'success'"
      class="feedback"
    >
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="submitWithValidation">
      <FormField v-slot="{ value, handleChange }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input
              :model-value="value"
              :disabled="pageLoading || submitLoading"
              @update:model-value="handleChange"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="color">
        <FormItem>
          <FormLabel>Cor (hex)</FormLabel>
          <div class="color-row">
            <FormControl>
              <Input
                :model-value="value"
                placeholder="#FF0000"
                :disabled="pageLoading || submitLoading"
                class="flex-1"
                @update:model-value="handleChange"
              />
            </FormControl>
            <Input
              type="color"
              :model-value="hexPattern.test(value ?? '') ? String(value) : defaultPreviewColor"
              :disabled="pageLoading || submitLoading"
              class="color-picker"
              @update:model-value="(value) => handleChange(String(value))"
            />
            <span
              class="color-preview"
              :style="{
                backgroundColor: hexPattern.test(value ?? '') ? String(value) : 'transparent',
              }"
              :title="hexPattern.test(value ?? '') ? String(value) : 'Cor inválida'"
            />
          </div>
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
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')"
          >Cancelar</Button
        >
        <RouterLink v-else to="/settings/calendar-types">Cancelar</RouterLink>
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

.color-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.color-picker {
  width: 3rem;
  padding: 0.25rem;
}

.color-preview {
  width: 1.5rem;
  height: 1.5rem;
  border: 1px solid hsl(var(--border));
  border-radius: 0.25rem;
}
</style>
