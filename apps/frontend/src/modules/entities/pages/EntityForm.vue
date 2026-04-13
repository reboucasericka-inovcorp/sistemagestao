<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
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

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.name === 'entities.new')
const entityId = computed(() => Number(route.params.id))
const submitLoading = ref(false)
const pageLoading = ref(false)
const nifChecking = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const countries = ref<string[]>(['Portugal', 'Espanha', 'França'])

const entitySchema = z
  .object({
    number: z.string().trim().min(1, 'Número é obrigatório'),
    nif: z.string().trim().min(9, 'NIF inválido'),
    name: z.string().trim().min(2, 'Nome é obrigatório'),
    address: z.string().trim().optional(),
    postal_code: z
      .string()
      .trim()
      .regex(/^\d{4}-\d{3}$/, 'Código postal deve seguir o formato XXXX-XXX')
      .or(z.literal('')),
    city: z.string().trim().optional(),
    country: z.string().trim().optional(),
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
  number: '',
  nif: '',
  name: '',
  address: '',
  postal_code: '',
  city: '',
  country: '',
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

const formSchema = toTypedSchema(entitySchema)

const { handleSubmit, resetForm, setFieldError, setFieldValue, values } = useForm<EntityFormData>({
  validationSchema: formSchema,
  initialValues: defaultValues,
})

function normalizePostalCode(raw: string): string {
  const digits = raw.replace(/\D/g, '').slice(0, 7)
  if (digits.length <= 4) {
    return digits
  }
  return `${digits.slice(0, 4)}-${digits.slice(4)}`
}

function setTypeDefaultsFromQuery(): void {
  const clients = route.query.clients === '1'
  const suppliers = route.query.suppliers === '1'
  if (clients && !suppliers) {
    setFieldValue('is_client', true)
    setFieldValue('is_supplier', false)
    return
  }
  if (suppliers && !clients) {
    setFieldValue('is_client', false)
    setFieldValue('is_supplier', true)
  }
}

function applyBackendEntity(payload: Partial<Entity>): void {
  resetForm({
    values: {
      ...defaultValues,
    ...payload,
    postal_code: payload.postal_code ?? '',
    address: payload.address ?? '',
    city: payload.city ?? '',
    country: payload.country ?? '',
    phone: payload.phone ?? '',
    mobile: payload.mobile ?? '',
    website: payload.website ?? '',
    email: payload.email ?? '',
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
    if (viesData.country && !values.country) {
      setFieldValue('country', viesData.country)
    }
  } catch {
    // Integração pode não estar disponível ainda no backend.
  } finally {
    nifChecking.value = false
  }
}

function toPayload(data: EntityFormData): UpsertEntityPayload {
  return {
    ...data,
    nif: data.nif.replace(/\D/g, ''),
    address: data.address || null,
    postal_code: data.postal_code || null,
    city: data.city || null,
    country: data.country || null,
    phone: data.phone || null,
    mobile: data.mobile || null,
    website: data.website || null,
    email: data.email || null,
    notes: data.notes || null,
  }
}

const onSubmit = handleSubmit(async (submittedValues) => {
  feedbackKind.value = ''
  feedbackMessage.value = ''

  submitLoading.value = true
  try {
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createEntity(payload)
      feedbackKind.value = 'success'
      feedbackMessage.value = 'Entidade criada com sucesso.'
      router.push('/entities')
      return
    }
    await updateEntity(entityId.value, payload)
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Entidade atualizada com sucesso.'
  } catch (error: unknown) {
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar a entidade.'
  } finally {
    submitLoading.value = false
  }
})

onMounted(() => {
  void loadEntityIfEditing()
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Nova entidade' : 'Editar entidade' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <form class="form" @submit="onSubmit">
      <FormField v-slot="{ componentField }" name="number">
        <FormItem>
          <FormLabel>Número (incremental)</FormLabel>
          <FormControl>
            <Input
              v-bind="componentField"
              :readonly="!isNew"
              :disabled="pageLoading || submitLoading"
              type="text"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

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
              @update:model-value="setFieldValue('postal_code', normalizePostalCode(String($event)))"
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

      <FormField v-slot="{ componentField }" name="country">
        <FormItem>
          <FormLabel>País</FormLabel>
          <Select
            :model-value="componentField.modelValue"
            :disabled="pageLoading || submitLoading"
            @update:model-value="componentField['onUpdate:modelValue']"
          >
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Selecione" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem v-for="country in countries" :key="country" :value="country">
                {{ country }}
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
        <RouterLink to="/entities">Cancelar</RouterLink>
        <Button :disabled="submitLoading || pageLoading" type="submit">
          {{ submitLoading ? 'A guardar...' : 'Guardar' }}
        </Button>
      </div>
    </form>
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
