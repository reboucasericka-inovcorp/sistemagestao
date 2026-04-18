<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { useCompany } from '@/core/company/useCompany'
import { updateCompany } from '@/modules/settings/company/services/companyService'
import type { Company, UpsertCompanyPayload } from '@/modules/settings/company/types/company'
import { listCountriesResult } from '@/modules/settings/countries/services/countryService'
import type { Country } from '@/modules/settings/countries/types/country'

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const logoPreviewUrl = ref<string | null>(null)
const logoFile = ref<File | null>(null)
const countryOptions = ref<Country[]>([])
const { company: companyFromStore, loadCompany: ensureCompanyLoaded } = useCompany()

const companySchema = z.object({
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  tax_number: z.string().trim().min(1, 'NIF é obrigatório'),
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
  email: z.string().trim().email('Email inválido').or(z.literal('')),
  website: z.string().trim().url('Website inválido (ex.: https://empresa.pt)').or(z.literal('')),
  is_active: z.boolean(),
})

type CompanyFormData = z.infer<typeof companySchema>

const defaultValues: CompanyFormData = {
  name: '',
  address: '',
  postal_code: '',
  city: '',
  country_id: '',
  phone: '',
  mobile: '',
  email: '',
  website: '',
  is_active: true,
  tax_number: '',
}

const { setValues, setFieldValue, setErrors } = useForm<CompanyFormData>({
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

async function applyCompanyData(data: Company): Promise<void> {
  await nextTick()
  setValues({
    name: data.name ?? '',
    address: data.address ?? '',
    postal_code: data.postal_code ?? '',
    city: data.city ?? '',
    country_id: data.country_id ? String(data.country_id) : '',
    phone: data.phone ?? '',
    mobile: data.mobile ?? '',
    email: data.email ?? '',
    website: data.website ?? '',
    is_active: data.is_active ?? true,
    tax_number: data.tax_number ?? '',
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
    country_id: values.country_id ? Number(values.country_id) : null,
    phone: values.phone || null,
    mobile: values.mobile || null,
    email: values.email || null,
    website: values.website || null,
    is_active: values.is_active,
    tax_number: values.tax_number,
    logo: logoFile.value,
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
    address: errors.address?.[0],
    postal_code: errors.postal_code?.[0],
    city: errors.city?.[0],
    country_id: errors.country_id?.[0],
    phone: errors.phone?.[0],
    mobile: errors.mobile?.[0],
    email: errors.email?.[0],
    website: errors.website?.[0],
    is_active: errors.is_active?.[0],
    tax_number: errors.tax_number?.[0],
  })
}

async function loadCountries(): Promise<void> {
  const countriesResult = await listCountriesResult({ per_page: 500 })
  countryOptions.value = countriesResult.data.filter((country) => country.is_active)
}

async function loadPageData(): Promise<void> {
  pageLoading.value = true
  try {
    const [companyResult, countriesResult] = await Promise.allSettled([
      ensureCompanyLoaded(),
      loadCountries(),
    ])

    if (companyResult.status === 'rejected') {
      throw companyResult.reason
    }

    const raw = companyFromStore.value
    if (raw) {
      const normalized = (raw as Company & { data?: Company })?.data ?? raw
      await applyCompanyData(normalized as Company)
    }

    if (countriesResult.status === 'rejected') {
      feedbackKind.value = 'error'
      feedbackMessage.value =
        'Dados da empresa carregados, mas não foi possível carregar a lista de países.'
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
    setErrors({})
    const payload = toPayload(values)
    const saved = await updateCompany(payload)
    const normalized = (saved as Company & { data?: Company })?.data ?? saved
    companyFromStore.value = normalized
    await applyCompanyData(normalized as Company)
    logoFile.value = null
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Dados da empresa guardados com sucesso.'
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
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

const submitWithValidation = async (submittedValues: CompanyFormData) => {
  await onSubmit(submittedValues)
}

onMounted(() => {
  void (async () => {
    await nextTick()
    await loadPageData()
  })()
})

onBeforeUnmount(() => {
  if (logoPreviewUrl.value && logoPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(logoPreviewUrl.value)
  }
})
</script>

<template>
  <div class="space-y-4">
    <h1>Configuração da empresa</h1>

    <p
      v-if="feedbackMessage"
      :class="feedbackKind === 'error' ? 'text-destructive' : 'text-green-700'"
      class="text-sm"
    >
      {{ feedbackMessage }}
    </p>

    <Form class="grid gap-4" @submit="submitWithValidation">
      <div class="space-y-2">
        <label class="text-sm font-medium leading-none">Logo</label>
        <Input
          type="file"
          accept="image/*"
          :disabled="pageLoading || submitLoading"
          @change="onLogoSelected"
        />
        <img
          v-if="logoPreviewUrl"
          :src="logoPreviewUrl"
          alt="Pré-visualização do logo"
          class="mt-2 max-w-60 rounded-md border border-border"
        />
      </div>

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
            <Textarea v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <FormField v-slot="{ value }" name="postal_code">
          <FormItem>
            <FormLabel>Código Postal</FormLabel>
            <FormControl>
              <Input
                :model-value="value as string"
                maxlength="8"
                :disabled="pageLoading || submitLoading"
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
              <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>

        <FormField v-slot="{ value, handleChange }" name="country_id">
          <FormItem>
            <FormLabel>País</FormLabel>
            <Select
              :model-value="value"
              :disabled="pageLoading || submitLoading"
              @update:model-value="(rawValue) => handleChange(String(rawValue ?? ''))"
            >
              <FormControl>
                <SelectTrigger>
                  <SelectValue placeholder="Selecione" />
                </SelectTrigger>
              </FormControl>
              <SelectContent>
                <SelectItem
                  v-for="country in countryOptions"
                  :key="country.id"
                  :value="String(country.id)"
                >
                  {{ country.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <FormMessage />
          </FormItem>
        </FormField>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <FormField v-slot="{ componentField }" name="phone">
          <FormItem>
            <FormLabel>Telefone</FormLabel>
            <FormControl>
              <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>

        <FormField v-slot="{ componentField }" name="mobile">
          <FormItem>
            <FormLabel>Telemóvel</FormLabel>
            <FormControl>
              <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>

        <FormField v-slot="{ componentField }" name="email">
          <FormItem>
            <FormLabel>Email</FormLabel>
            <FormControl>
              <Input
                v-bind="componentField"
                type="email"
                :disabled="pageLoading || submitLoading"
              />
            </FormControl>
            <FormMessage />
          </FormItem>
        </FormField>
      </div>

      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <FormField v-slot="{ componentField }" name="website">
          <FormItem>
            <FormLabel>Website</FormLabel>
            <FormControl>
              <Input
                v-bind="componentField"
                placeholder="https://empresa.pt"
                :disabled="pageLoading || submitLoading"
              />
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
      </div>

      <FormField v-slot="{ value, handleChange }" name="is_active">
        <FormItem class="flex items-center gap-2">
          <FormControl>
            <Checkbox
              :checked="Boolean(value)"
              :disabled="pageLoading || submitLoading"
              @update:checked="handleChange"
            />
          </FormControl>
          <FormLabel>Ativa</FormLabel>
          <FormMessage />
        </FormItem>
      </FormField>

      <div class="flex items-center justify-end gap-2 pt-2">
        <Button :disabled="submitLoading || pageLoading" type="submit">
          {{ submitLoading ? 'A guardar...' : 'Guardar' }}
        </Button>
      </div>
    </Form>
  </div>
</template>
