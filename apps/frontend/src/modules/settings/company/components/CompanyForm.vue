<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  createCompany,
  getCompany,
  updateCompany,
} from '@/modules/settings/company/services/companyService'
import type { Company, UpsertCompanyPayload } from '@/modules/settings/company/types/company'

const pageLoading = ref(false)
const submitLoading = ref(false)
const hasExistingCompany = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const logoPreviewUrl = ref<string | null>(null)
const logoFile = ref<File | null>(null)

const companySchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  address: z.string().trim().optional(),
  postal_code: z
    .string()
    .trim()
    .regex(/^\d{4}-\d{3}$/, 'Código postal deve seguir o formato XXXX-XXX')
    .or(z.literal('')),
  city: z.string().trim().optional(),
  tax_number: z.string().trim().min(1, 'NIF é obrigatório'),
})

type CompanyFormData = z.infer<typeof companySchema>

const defaultValues: CompanyFormData = {
  name: '',
  address: '',
  postal_code: '',
  city: '',
  tax_number: '',
}

const { resetForm, setFieldValue } = useForm<CompanyFormData>({
  validationSchema: toTypedSchema(companySchema),
  initialValues: defaultValues,
})

function normalizePostalCode(raw: string): string {
  const digits = raw.replace(/\D/g, '').slice(0, 7)
  if (digits.length <= 4) {
    return digits
  }
  return `${digits.slice(0, 4)}-${digits.slice(4)}`
}

function applyCompanyData(data: Company): void {
  resetForm({
    values: {
      name: data.name ?? '',
      address: data.address ?? '',
      postal_code: data.postal_code ?? '',
      city: data.city ?? '',
      tax_number: data.tax_number ?? '',
    },
  })
  logoPreviewUrl.value = data.logo_url ?? null
}

function onLogoSelected(event: Event): void {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0] ?? null
  logoFile.value = file

  if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreviewUrl.value)
  }
  logoPreviewUrl.value = file ? URL.createObjectURL(file) : logoPreviewUrl.value
}

function toPayload(values: CompanyFormData): UpsertCompanyPayload {
  return {
    name: values.name,
    address: values.address || null,
    postal_code: values.postal_code || null,
    city: values.city || null,
    tax_number: values.tax_number,
    logo: logoFile.value,
  }
}

async function loadCompany(): Promise<void> {
  pageLoading.value = true
  try {
    const company = await getCompany()
    if (company) {
      hasExistingCompany.value = true
      applyCompanyData(company)
    } else {
      hasExistingCompany.value = false
    }
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar os dados da empresa.'
  } finally {
    pageLoading.value = false
  }
}

async function onSubmit(values: CompanyFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    const payload = toPayload(values)
    const saved = hasExistingCompany.value ? await updateCompany(payload) : await createCompany(payload)
    hasExistingCompany.value = true
    applyCompanyData(saved)
    logoFile.value = null
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Dados da empresa guardados com sucesso.'
  } catch (error: unknown) {
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar os dados da empresa.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(() => {
  void loadCompany()
})

onBeforeUnmount(() => {
  if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreviewUrl.value)
  }
})
</script>

<template>
  <div class="page">
    <h1>Configuração da empresa</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as CompanyFormData)">
      <FormItem>
        <FormLabel>Logo</FormLabel>
        <FormControl>
          <Input type="file" accept="image/*" :disabled="pageLoading || submitLoading" @change="onLogoSelected" />
        </FormControl>
        <FormMessage />
        <img v-if="logoPreviewUrl" :src="logoPreviewUrl" alt="Pré-visualização do logo" class="preview" />
      </FormItem>

      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="address">
        <FormItem>
          <FormLabel>Morada</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
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
              maxlength="8"
              :disabled="pageLoading || submitLoading"
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
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="tax_number">
        <FormItem>
          <FormLabel>NIF</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="footer">
        <Button :disabled="submitLoading || pageLoading" type="submit">
          {{ submitLoading ? 'A guardar...' : 'Guardar' }}
        </Button>
      </div>
    </Form>
  </div>
</template>

<style scoped>
.page {
  margin: 0 auto;
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

.preview {
  margin-top: 0.5rem;
  max-width: 240px;
  border-radius: 0.5rem;
  border: 1px solid hsl(var(--border));
}

.footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.5rem;
}
</style>
