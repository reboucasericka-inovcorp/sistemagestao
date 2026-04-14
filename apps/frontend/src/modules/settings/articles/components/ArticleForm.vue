<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
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
  createArticle,
  getArticleById,
  listVatOptions,
  updateArticle,
  type UpsertArticlePayload,
  type VatOption,
} from '@/modules/settings/articles/services/articleService'
import type { Article } from '@/modules/settings/articles/types/article'

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
const isNew = computed(() => (props.mode ? props.mode === 'create' : route.name === 'articles.new'))
const articleId = computed(() => Number(props.recordId ?? route.params.id))

const pageLoading = ref(false)
const submitLoading = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const photoPreviewUrl = ref<string | null>(null)
const photoFile = ref<File | null>(null)
const vatOptions = ref<VatOption[]>([])

const articleSchema = z.object({
  reference: z.string().trim().min(1, 'Referência é obrigatória'),
  name: z.string().trim().min(1, 'Nome é obrigatório'),
  description: z.string().trim().optional(),
  price: z.string().trim().refine((value) => !Number.isNaN(Number(value)) && Number(value) >= 0, {
    message: 'Preço deve ser maior ou igual a zero',
  }),
  vat_id: z.coerce.number().int().positive('IVA é obrigatório'),
  notes: z.string().trim().optional(),
  is_active: z.boolean(),
})

type ArticleFormData = z.infer<typeof articleSchema>

const defaultValues: ArticleFormData = {
  reference: '',
  name: '',
  description: '',
  price: '0.00',
  vat_id: 0,
  notes: '',
  is_active: true,
}

const { resetForm, setFieldValue, setErrors } = useForm<ArticleFormData>({
  validationSchema: toTypedSchema(articleSchema),
  initialValues: defaultValues,
})

function applyBackendArticle(payload: Article): void {
  resetForm({
    values: {
      reference: payload.reference,
      name: payload.name,
      description: payload.description ?? '',
      price: payload.price,
      vat_id: payload.vat_id ?? 0,
      notes: payload.notes ?? '',
      is_active: payload.is_active,
    },
  })
  if (payload.photo_url) {
    photoPreviewUrl.value = payload.photo_url
  }
}

async function loadArticleIfEditing(): Promise<void> {
  if (isNew.value || Number.isNaN(articleId.value)) {
    return
  }
  const article = await getArticleById(articleId.value)
  applyBackendArticle(article)
}

function onPhotoSelected(event: Event): void {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0] ?? null
  photoFile.value = file
  if (photoPreviewUrl.value && photoPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreviewUrl.value)
  }
  photoPreviewUrl.value = file ? URL.createObjectURL(file) : null
}

function toPayload(values: ArticleFormData): UpsertArticlePayload {
  return {
    reference: values.reference,
    name: values.name,
    description: values.description || null,
    price: values.price,
    vat_id: values.vat_id,
    notes: values.notes || null,
    is_active: values.is_active,
    photo: photoFile.value,
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
    reference: errors.reference?.[0],
    name: errors.name?.[0],
    description: errors.description?.[0],
    price: errors.price?.[0],
    vat_id: errors.vat_id?.[0],
    notes: errors.notes?.[0],
    is_active: errors.is_active?.[0],
  })
}

async function onSubmit(submittedValues: ArticleFormData): Promise<void> {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitLoading.value = true
  try {
    setErrors({})
    const payload = toPayload(submittedValues)
    if (isNew.value) {
      await createArticle(payload)
      feedbackKind.value = 'success'
      feedbackMessage.value = 'Artigo criado com sucesso.'
      if (props.mode === 'create') {
        emit('success')
      } else {
        await router.push('/settings/articles')
      }
      return
    }
    await updateArticle(articleId.value, payload)
    if (props.mode === 'edit') {
      emit('success')
      return
    }
    feedbackKind.value = 'success'
    feedbackMessage.value = 'Artigo atualizado com sucesso.'
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
    const maybeMessage =
      typeof error === 'object' && error && 'response' in error
        ? (error as { response?: { data?: { message?: string } } }).response?.data?.message
        : undefined
    feedbackKind.value = 'error'
    feedbackMessage.value = maybeMessage ?? 'Não foi possível guardar o artigo.'
  } finally {
    submitLoading.value = false
  }
}

onMounted(async () => {
  pageLoading.value = true
  try {
    vatOptions.value = await listVatOptions()
    await loadArticleIfEditing()
  } catch {
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível carregar dados do formulário (ex: IVA).'
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
      photoFile.value = null
      photoPreviewUrl.value = null
      return
    }
    await loadArticleIfEditing()
  },
)

onBeforeUnmount(() => {
  if (photoPreviewUrl.value && photoPreviewUrl.value.startsWith('blob:')) {
    URL.revokeObjectURL(photoPreviewUrl.value)
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Novo artigo' : 'Editar artigo' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="(values) => onSubmit(values as ArticleFormData)">
      <FormField v-slot="{ componentField }" name="reference">
        <FormItem>
          <FormLabel>Referência</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="name">
        <FormItem>
          <FormLabel>Nome</FormLabel>
          <FormControl>
            <Input v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="description">
        <FormItem>
          <FormLabel>Descrição</FormLabel>
          <FormControl>
            <Textarea v-bind="componentField" :disabled="pageLoading || submitLoading" />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value }" name="price">
        <FormItem>
          <FormLabel>Preço</FormLabel>
          <FormControl>
            <Input
              :model-value="String(value ?? '0.00')"
              type="number"
              step="0.01"
              min="0"
              :disabled="pageLoading || submitLoading"
              @update:model-value="setFieldValue('price', String($event ?? '0'))"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ componentField }" name="vat_id">
        <FormItem>
          <FormLabel>IVA</FormLabel>
          <Select
            :model-value="String(componentField.modelValue || '')"
            :disabled="pageLoading || submitLoading"
            @update:model-value="(value) => setFieldValue('vat_id', Number(value))"
          >
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Selecione o IVA" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem v-for="vat in vatOptions" :key="vat.id" :value="String(vat.id)">
                {{ vat.name }} ({{ vat.rate }}%)
              </SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormItem>
        <FormLabel>Foto</FormLabel>
        <FormControl>
          <Input type="file" accept="image/*" :disabled="pageLoading || submitLoading" @change="onPhotoSelected" />
        </FormControl>
        <FormMessage />
        <img v-if="photoPreviewUrl" :src="photoPreviewUrl" alt="Pré-visualização da foto" class="preview" />
      </FormItem>

      <FormField v-slot="{ componentField }" name="notes">
        <FormItem>
          <FormLabel>Observações</FormLabel>
          <FormControl>
            <Textarea v-bind="componentField" :disabled="pageLoading || submitLoading" />
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
        <RouterLink v-else to="/settings/articles">Cancelar</RouterLink>
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

.preview {
  margin-top: 0.5rem;
  max-width: 220px;
  border-radius: 0.5rem;
  border: 1px solid hsl(var(--border));
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
