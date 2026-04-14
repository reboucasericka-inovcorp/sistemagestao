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

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import {
  checkEntityNif,
  createEntity,
  getEntityById,
  lookupEntityByVies,
  updateEntity,
  type UpsertEntityPayload,
} from '@/modules/entities/services/entityService'
import type { Entity } from '@/modules/entities/types/entity'
import { listCountriesResult } from '@/modules/settings/countries/services/countryService'
import type { Country } from '@/modules/settings/countries/types/country'

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
const isNew = computed(() => {
  if (props.mode) return props.mode === 'create'
  return ['entities.new', 'clients.new', 'suppliers.new'].includes(String(route.name ?? ''))
})
const entityId = computed(() => Number(props.recordId ?? route.params.id))
const submitLoading = ref(false)
const pageLoading = ref(false)
const nifChecking = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const countryOptions = ref<Country[]>([])

const entitySchema = z
  .object({
    nif: z.string().trim().min(9, 'NIF inválido'),
    name: z.string().trim().min(2, 'Nome é obrigatório'),
    address: z.string().trim().optional(),
    postal_code: z
      .string()
      .trim()
      .regex(/^\d{4}-\d{3}$/, 'Código postal deve seguir o formato XXXX-XXX')
      .or(z.literal('')),
    city: z.string().trim().optional(),
    country_id: z.string().optional(),
    phone: z.string().trim().optional(),
    mobile: z.string().trim().optional(),
    website: z.union([z.string().trim().url('Website inválido'), z.literal('')]),
    email: z.union([z.string().trim().email('Email inválido'), z.literal('')]),
    gdpr_consent: z.boolean(),
    is_active: z.boolean(),
    is_client: z.boolean(),
    is_supplier: z.boolean(),
    notes: z.string().trim().optional(),
  })
  .superRefine((data, ctx) => {
    if (!data.is_client && !data.is_supplier) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: 'Selecione pelo menos Cliente ou Fornecedor.',
        path: ['is_client'],
      })
    }
  })

type EntityFormData = z.infer<typeof entitySchema>

const defaultValues: EntityFormData = {
  nif: '',
  name: '',
  address: '',
  postal_code: '',
  city: '',
  country_id: '',
  phone: '',
  mobile: '',
  website: '',
  email: '',
  gdpr_consent: false,
  is_active: true,
  is_client: true,
  is_supplier: false,
  notes: '',
}

const { resetForm, setErrors, setFieldError, setFieldValue, values } = useForm<EntityFormData>({
  validationSchema: toTypedSchema(entitySchema),
  initialValues: defaultValues,
})

function normalizePostalCode(raw: string): string {
  const digits = raw.replace(/\D/g, '').slice(0, 7)
  if (digits.length <= 4) {
    return digits
  }
  return `${digits.slice(0, 4)}-${digits.slice(4)}`
}

function resolveEntityType(formValues: Pick<EntityFormData, 'is_client' | 'is_supplier'>): UpsertEntityPayload['type'] {
  if (formValues.is_client && formValues.is_supplier) {
    return 'both'
  }
  if (formValues.is_client) {
    return 'client'
  }
  return 'supplier'
}

function setTypeDefaultsFromQuery(): void {
  if (route.name === 'clients.new') {
    setFieldValue('is_client', true)
    setFieldValue('is_supplier', false)
    return
  }
  if (route.name === 'suppliers.new') {
    setFieldValue('is_client', false)
    setFieldValue('is_supplier', true)
    return
  }
  setFieldValue('is_client', true)
  setFieldValue('is_supplier', false)
}

function applyBackendEntity(payload: Entity): void {
  resetForm({
    values: {
      ...defaultValues,
      nif: payload.nif ?? '',
      name: payload.name ?? '',
      address: payload.address ?? '',
      postal_code: payload.postal_code ?? '',
      city: payload.city ?? '',
      country_id: payload.country?.id ? String(payload.country.id) : '',
      phone: payload.phone ?? '',
      mobile: payload.mobile ?? '',
      website: payload.website ?? '',
      email: payload.email ?? '',
      gdpr_consent: Boolean(payload.gdpr_consent),
      is_active: payload.is_active ?? true,
      is_client: payload.type === 'client' || payload.type === 'both',
      is_supplier: payload.type === 'supplier' || payload.type === 'both',
      notes: payload.notes ?? '',
    },
  })
}

async function loadEntityIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(entityId.value)) {
    setTypeDefaultsFromQuery()
    return
  }

  pageLoading.value = true
  try {
    const data = await getEntityById(entityId.value)
    applyBackendEntity(data)
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar a entidade.'
  } finally {
    pageLoading.value = false
  }
}

function resetForCreate(): void {
  resetForm({ values: { ...defaultValues } })
  setTypeDefaultsFromQuery()
}

async function onNifBlur(): Promise<void> {
  const nifDigits = (values.nif ?? '').replace(/\D/g, '')
  if (!nifDigits) {
    return
  }

  setFieldValue('nif', nifDigits)
  nifChecking.value = true
  setFieldError('nif', undefined)
  try {
    const nifCheck = await checkEntityNif(nifDigits)
    if (!nifCheck.available && isNew.value) {
      setFieldError('nif', 'NIF já está em uso.')
      return
    }
  } catch {
    // Endpoint pode ainda não existir. Não bloqueia o formulário.
  }

  try {
    const viesData = await lookupEntityByVies(nifDigits)
    if (viesData.name && !values.name) {
      setFieldValue('name', viesData.name)
    }
    if (viesData.address && !values.address) {
      setFieldValue('address', viesData.address)
    }
    if (viesData.city && !values.city) {
      setFieldValue('city', viesData.city)
    }
  } catch {
    // Integração pode não estar disponível ainda no backend.
  } finally {
    nifChecking.value = false
  }
}

function toPayload(data: EntityFormData): UpsertEntityPayload {
  return {
    type: resolveEntityType(data),
    nif: data.nif.replace(/\D/g, ''),
    name: data.name.trim(),
    address: data.address || null,
    postal_code: data.postal_code || null,
    city: data.city || null,
    country_id: data.country_id ? Number(data.country_id) : null,
    phone: data.phone || null,
    mobile: data.mobile || null,
    website: data.website || null,
    email: data.email || null,
    gdpr_consent: data.gdpr_consent,
    is_active: data.is_active,
    notes: data.notes || null,
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
    nif: errors.nif?.[0],
    name: errors.name?.[0],
    address: errors.address?.[0],
    postal_code: errors.postal_code?.[0],
    city: errors.city?.[0],
    country_id: errors.country_id?.[0],
    phone: errors.phone?.[0],
    mobile: errors.mobile?.[0],
    website: errors.website?.[0],
    email: errors.email?.[0],
    gdpr_consent: errors.gdpr_consent?.[0],
    notes: errors.notes?.[0],
    is_active: errors.is_active?.[0],
    is_client: errors.type?.[0],
  })
}

async function onSubmit(submittedValues: EntityFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''

  submitLoading.value = true
  try {
    setErrors({})
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createEntity(payload)
      const returnPath =
        route.name === 'clients.new'
          ? '/clients'
          : route.name === 'suppliers.new'
            ? '/suppliers'
            : '/entities'
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push(returnPath)
      }
      return
    }
    await updateEntity(entityId.value, payload)
    if (props.mode === 'edit') {
      emit('success')
      return
    }
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Entidade atualizada com sucesso.'
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar a entidade.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  pageLoading.value = true
  try {
    const countriesResult = await listCountriesResult({ per_page: 100 })
    countryOptions.value = countriesResult.data.filter((country) => country.is_active)
    await loadEntityIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados da entidade.'
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
      resetForCreate()
      return
    }
    await loadEntityIfEditing()
  },
)
</script>

<template>
  <div :class="['page', { 'page--modal': Boolean(props.mode) }]">
    <h1>{{ isNew ? 'Nova entidade' : 'Editar entidade' }}</h1>

    <p
      v-if="feedbackMessage"
      :class="feedbackKind === 'error' ? 'error' : 'success'"
      class="feedback"
    >
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as EntityFormData)">
      <div class="number-hint">
        <label>Número (incremental)</label>
        <Input value="Será gerado automaticamente após salvar" disabled />
      </div>

      <FormField v-slot="{ componentField }" name="nif">
        <FormItem>
          <FormLabel>NIF</FormLabel>
          <FormControl>
            <Input
              v-bind="componentField"
              :disabled="pageLoading || submitLoading || nifChecking"
              type="text"
              maxlength="20"
              @blur="onNifBlur"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" type="text" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="address">
        <FormItem>
          <FormLabel>Morada</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" type="text" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value }" name="postal_code">
        <FormItem>
          <FormLabel>Código Postal</FormLabel>
          <FormControl>
            <Input
              :model-value="value"
              :disabled="pageLoading || submitLoading"
              type="text"
              maxlength="8"
              @update:model-value="
                setFieldValue('postal_code', normalizePostalCode(String($event)))
              "
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="city">
        <FormItem>
          <FormLabel>Localidade</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" type="text" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="country_id">
        <FormItem>
          <FormLabel>País</FormLabel>
          <Select
            :model-value="String(value ?? '')"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(selectedValue) => handleChange(String(selectedValue ?? ''))"
          >
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Selecione" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem v-for="country in countryOptions" :key="country.id" :value="String(country.id)">
                {{ country.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="phone">
        <FormItem>
          <FormLabel>Telefone</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" type="text" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="mobile">
        <FormItem>
          <FormLabel>Telemóvel</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" type="text" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="website">
        <FormItem>
          <FormLabel>Website</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" type="url" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="email">
        <FormItem>
          <FormLabel>Email</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" type="email" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="notes">
        <FormItem>
          <FormLabel>Observações</FormLabel>
          <FormControl>
            <Textarea v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="checks">
        <FormField v-slot="{ value, handleChange }" name="is_client">
          <FormItem class="check-item">
            <FormControl>
              <Checkbox
                :checked="Boolean(value)"
                :disabled="pageLoading || submitLoading"
                @update:checked="handleChange"
              />
            </FormControl>
            <FormLabel>Cliente</FormLabel>
            <FormMessage />
          </FormItem>
        </FormField>

        <FormField v-slot="{ value, handleChange }" name="is_supplier">
          <FormItem class="check-item">
            <FormControl>
              <Checkbox
                :checked="Boolean(value)"
                :disabled="pageLoading || submitLoading"
                @update:checked="handleChange"
              />
            </FormControl>
            <FormLabel>Fornecedor</FormLabel>
            <FormMessage />
          </FormItem>
        </FormField>

        <FormField v-slot="{ value, handleChange }" name="gdpr_consent">
          <FormItem class="check-item">
            <FormControl>
              <Checkbox
                :checked="Boolean(value)"
                :disabled="pageLoading || submitLoading"
                @update:checked="handleChange"
              />
            </FormControl>
            <FormLabel>Consentimento RGPD</FormLabel>
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
      </div>

      <div class="footer">
        <Button v-if="props.mode" type="button" variant="outline" @click="emit('cancel')">Cancelar</Button>
        <RouterLink v-else to="/entities">Cancelar</RouterLink>
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

.page--modal {
  max-width: none;
  width: 100%;
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

.number-hint {
  display: grid;
  gap: 0.35rem;
}

.number-hint label {
  font-size: 0.875rem;
  font-weight: 500;
}

.checks {
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  padding: 0.75rem;
  display: grid;
  gap: 0.7rem;
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
