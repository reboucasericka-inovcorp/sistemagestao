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
  createCountry,
  getCountryById,
  updateCountry,
  type UpsertCountryPayload,
} from '@/modules/settings/countries/services/countryService'
import type { Country } from '@/modules/settings/countries/types/country'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.name === 'countries.new')
const countryId = computed(() => Number(route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')

const countrySchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  code: z.string().trim().optional(),
  is_active: z.boolean(),
})

type CountryFormData = z.infer<typeof countrySchema>

const defaultValues: CountryFormData = {
  name: '',
  code: '',
  is_active: true,
}

const { resetForm } = useForm<CountryFormData>({
  validationSchema: toTypedSchema(countrySchema),
  initialValues: defaultValues,
})

function applyBackendCountry(payload: Country): void {
  resetForm({
    values: {
      name: payload.name,
      code: payload.code ?? '',
      is_active: payload.is_active,
    },
  })
}

async function loadCountryIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(countryId.value)) {
    return
  }
  const country = await getCountryById(countryId.value)
  applyBackendCountry(country)
}

function toPayload(values: CountryFormData): UpsertCountryPayload {
  return {
    name: values.name,
    code: values.code?.trim() || null,
    is_active: values.is_active,
  }
}

async function onSubmit(submittedValues: CountryFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createCountry(payload)
      await router.push('/settings/countries')
      return
    }
    await updateCountry(countryId.value, payload)
    feedbackKind.value = 'success'
    feedbackMessage.value = 'País atualizado com sucesso.'
  } catch (error: unknown) {
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar o país.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  pageLoading.value = true
  try {
    await loadCountryIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados do país.'
  } finally {
    pageLoading.value = false
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo país' : 'Editar país' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as CountryFormData)">
      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="code">
        <FormItem>
          <FormLabel>Código</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
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
        <RouterLink to="/settings/countries">Cancelar</RouterLink>
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
