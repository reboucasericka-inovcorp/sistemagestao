<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { createVat, getVatById, updateVat, type UpsertVatPayload } from '@/modules/settings/vat/services/vatService'
import type { Vat } from '@/modules/settings/vat/types/vat'

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
const isNew = computed(() => (props.mode ? props.mode === 'create' : route.name === 'vat.new'))
const vatId = computed(() => Number(props.recordId ?? route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const vatSchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  rate: z.coerce.number().min(0, 'Taxa inválida').max(100, 'Máximo 100%'),
  is_active: z.boolean(),
})

type VatFormData = z.infer<typeof vatSchema>

const defaultValues: VatFormData = {
  name: '',
  rate: 0,
  is_active: true,
}

const { resetForm, setErrors, values } = useForm<VatFormData>({
  validationSchema: toTypedSchema(vatSchema),
  initialValues: defaultValues,
})

function normalizeRate(rawValue: unknown): number {
  const sanitized = String(rawValue ?? '')
    .replace('%', '')
    .replace(',', '.')
    .trim()

  const numeric = Number(sanitized)
  return Number.isFinite(numeric) ? numeric : 0
}

function applyBackendVat(payload: Vat): void {
  resetForm({
    values: {
      name: payload.name,
      rate: payload.rate,
      is_active: payload.is_active,
    },
  })
}

async function loadVatIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(vatId.value)) {
    return
  }
  const vat = await getVatById(vatId.value)
  applyBackendVat(vat)
}

function toPayload(values: VatFormData): UpsertVatPayload {
  const normalizedRate = normalizeRate(values.rate)

  return {
    name: values.name,
    rate: normalizedRate,
    is_active: values.is_active ?? true,
  }
}

function applyLaravelValidationErrors(error: unknown): void {
  if (typeof error !== 'object' || !error || !('response' in error)) {
    return
  }

  const errors = (error as { response?: { data?: { errors?: Record<string, string[] | undefined> } } }).response?.data
    ?.errors
  if (!errors) {
    return
  }

  setErrors({
    name: errors.name?.[0],
    rate: errors.rate?.[0],
    is_active: errors.is_active?.[0],
  })
}

async function onSubmit(submittedValues: VatFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    setErrors({})
    const payload = toPayload({
      ...submittedValues,
      rate: normalizeRate(submittedValues.rate ?? values.rate),
    })
    console.log('vat payload', payload)
    if (isNew.value) {
      await createVat(payload)
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push('/settings/vat')
      }
      return
    }
    await updateVat(vatId.value, payload)
    if (props.mode === 'edit') {
      emit('success')
      return
    }
    feedbackKind.value = 'success'
    feedbackMessage.value = 'IVA atualizado com sucesso.'
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar o IVA.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  pageLoading.value = true
  try {
    await loadVatIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados do IVA.'
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
      resetForm({ values: { ...defaultValues } })
      return
    }
    await loadVatIfEditing()
  },
)
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo IVA' : 'Editar IVA' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as VatFormData)">
      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="rate">
        <FormItem>
          <FormLabel>Taxa (%)</FormLabel>
          <FormControl>
            <Input
              :model-value="String(value ?? '')"
              type="number"
              step="0.01"
              min="0"
              max="100"
              :disabled="pageLoading || submitLoading"
              @update:model-value="(rawValue) => handleChange(normalizeRate(rawValue))"
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
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')">Cancelar</Button>
        <RouterLink v-else to="/settings/vat">Cancelar</RouterLink>
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
