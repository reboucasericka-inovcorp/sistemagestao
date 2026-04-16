<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm, type SubmissionHandler } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
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
import {
  createWorkOrder,
  getWorkOrderById,
  updateWorkOrder,
} from '@/modules/work-orders/services/workOrderService'
import {
  type ArticleSearchOption,
  type ClientOption,
  getClientOptions,
  searchArticles,
} from '@/modules/proposals/services/proposalService'
import type { UpsertWorkOrderPayload, WorkOrderStatus } from '@/modules/work-orders/types/workOrder'

type WorkOrderItemForm = {
  article_id: number | null
  article_name: string
  quantity: number
  price: number
  total: number
}

type ItemValidationErrors = {
  article?: string
  quantity?: string
  price?: string
}

const route = useRoute()
const router = useRouter()

const isNew = computed(() => route.name === 'work-orders.create')
const workOrderId = computed(() => Number(route.params.id))

const loading = ref(false)
const submitting = ref(false)
const feedbackMessage = ref('')
const feedbackKind = ref<'success' | 'error' | ''>('')
const clientOptions = ref<ClientOption[]>([])
const articleOptionsByItem = ref<Record<number, ArticleSearchOption[]>>({})
const articleSearchDebounceId = ref<number | null>(null)

const items = ref<WorkOrderItemForm[]>([
  {
    article_id: null,
    article_name: '',
    quantity: 1,
    price: 0,
    total: 0,
  },
])

const itemErrors = ref<Record<number, ItemValidationErrors>>({})

const totalAmount = computed(() =>
  items.value.reduce((sum, item) => sum + Number(item.total || 0), 0),
)

const schema = z.object({
  number: z.string().optional(),
  date: z.string().min(1, 'Data é obrigatória'),
  client_id: z.string().min(1, 'Cliente é obrigatório'),
  status: z.enum(['draft', 'in_progress', 'completed']),
  description: z.string().nullable().optional(),
})

type FormData = z.infer<typeof schema>

const defaultValues: FormData = {
  number: '',
  date: '',
  client_id: '',
  status: 'draft',
  description: '',
}

const { handleSubmit, setValues, setErrors } = useForm<FormData>({
  validationSchema: toTypedSchema(schema),
  initialValues: defaultValues,
})

async function loadClients(): Promise<void> {
  clientOptions.value = await getClientOptions()
}

async function hydrateForEdit(): Promise<void> {
  if (isNew.value || Number.isNaN(workOrderId.value)) return

  const workOrder = await getWorkOrderById(workOrderId.value)
  setValues({
    number: workOrder.number ?? '',
    date: workOrder.date ?? '',
    client_id: workOrder.client?.id ? String(workOrder.client.id) : '',
    status: (workOrder.status ?? 'draft') as WorkOrderStatus,
    description: workOrder.description ?? '',
  })

  items.value = (workOrder.items ?? []).length
    ? (workOrder.items ?? []).map((item) => ({
        article_id: item.article_id,
        article_name: item.article ? `${item.article.reference ?? ''} - ${item.article.name}`.trim() : '',
        quantity: Number(item.quantity),
        price: Number(item.price),
        total: Number(item.total),
      }))
    : [
        {
          article_id: null,
          article_name: '',
          quantity: 1,
          price: 0,
          total: 0,
        },
      ]
}

function recalculateItemTotal(index: number): void {
  const target = items.value[index]
  if (!target) return
  target.total = Number((Number(target.quantity || 0) * Number(target.price || 0)).toFixed(2))
}

function addItem(): void {
  items.value.push({
    article_id: null,
    article_name: '',
    quantity: 1,
    price: 0,
    total: 0,
  })
}

function removeItem(index: number): void {
  if (items.value.length === 1) return
  items.value.splice(index, 1)
  delete articleOptionsByItem.value[index]
  itemErrors.value = {}
}

function selectArticle(index: number, article: ArticleSearchOption): void {
  const target = items.value[index]
  if (!target) return
  target.article_id = article.id
  target.article_name = `${article.reference} - ${article.name}`
  if (article.price != null) {
    target.price = Number(article.price)
    recalculateItemTotal(index)
  }
  articleOptionsByItem.value[index] = []
}

function queueArticleSearch(index: number, query: string): void {
  const target = items.value[index]
  if (!target) return
  target.article_name = query
  target.article_id = null

  if (articleSearchDebounceId.value != null) {
    window.clearTimeout(articleSearchDebounceId.value)
  }

  if (!query.trim()) {
    articleOptionsByItem.value[index] = []
    return
  }

  articleSearchDebounceId.value = window.setTimeout(async () => {
    articleOptionsByItem.value[index] = await searchArticles(query.trim())
  }, 300)
}

function validateItems(): boolean {
  if (items.value.length === 0) {
    itemErrors.value = {}
    feedbackKind.value = 'error'
    feedbackMessage.value = 'A OT deve conter pelo menos uma linha.'
    return false
  }

  const nextErrors: Record<number, ItemValidationErrors> = {}

  items.value.forEach((item, index) => {
    const rowError: ItemValidationErrors = {}
    if (!item.article_id) rowError.article = 'Selecione um artigo.'
    if (Number(item.quantity) <= 0) rowError.quantity = 'Quantidade deve ser maior que zero.'
    if (Number(item.price) < 0) rowError.price = 'Preço não pode ser negativo.'

    if (Object.keys(rowError).length > 0) nextErrors[index] = rowError
  })

  itemErrors.value = nextErrors
  return Object.keys(nextErrors).length === 0
}

function toPayload(values: FormData): UpsertWorkOrderPayload {
  return {
    date: values.date,
    client_id: Number(values.client_id),
    status: values.status,
    description: values.description ?? '',
    items: items.value
      .filter((item) => item.article_id != null)
      .map((item) => ({
        article_id: Number(item.article_id),
        quantity: Number(item.quantity),
        price: Number(item.price),
        total: Number(item.total),
      })),
  }
}

function applyLaravelValidationErrors(error: unknown): void {
  if (typeof error !== 'object' || !error || !('response' in error)) return

  const errors = (error as { response?: { data?: { errors?: Record<string, string[] | undefined> } } })
    .response?.data?.errors
  if (!errors) return

  setErrors({
    date: errors.date?.[0],
    client_id: errors.client_id?.[0],
    status: errors.status?.[0],
    description: errors.description?.[0],
  })
}

const onSubmit: SubmissionHandler<FormData> = async (values) => {
  feedbackKind.value = ''
  feedbackMessage.value = ''
  submitting.value = true

  try {
    setErrors({})
    if (!validateItems()) {
      feedbackKind.value = 'error'
      if (!feedbackMessage.value) {
        feedbackMessage.value = 'Existem linhas inválidas na OT.'
      }
      return
    }

    const payload = toPayload(values)
    if (!payload.items?.length) {
      feedbackKind.value = 'error'
      feedbackMessage.value = 'Selecione pelo menos um artigo (pesquise e escolha um resultado da lista).'
      return
    }

    if (isNew.value) {
      await createWorkOrder(payload)
      feedbackKind.value = 'success'
      feedbackMessage.value = 'Ordem de trabalho criada com sucesso.'
    } else {
      await updateWorkOrder(workOrderId.value, payload)
      feedbackKind.value = 'success'
      feedbackMessage.value = 'Ordem de trabalho atualizada com sucesso.'
    }

    window.setTimeout(() => {
      void router.push('/work-orders')
    }, 400)
  } catch (error: unknown) {
    applyLaravelValidationErrors(error)
    feedbackKind.value = 'error'
    feedbackMessage.value = 'Não foi possível guardar a ordem de trabalho.'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await loadClients()
    if (isNew.value) {
      setValues({
        ...defaultValues,
        date: new Date().toISOString().slice(0, 10),
      })
    } else {
      await hydrateForEdit()
    }
  } finally {
    loading.value = false
  }
})

onBeforeUnmount(() => {
  if (articleSearchDebounceId.value != null) {
    window.clearTimeout(articleSearchDebounceId.value)
  }
})
</script>

<template>
  <div class="page">
    <h1>{{ isNew ? 'Criar ordem de trabalho' : 'Editar ordem de trabalho' }}</h1>

    <p v-if="feedbackMessage" :class="feedbackKind === 'error' ? 'error' : 'success'" class="feedback toast-like">
      {{ feedbackMessage }}
    </p>

    <Form class="form" @submit="handleSubmit(onSubmit)">
      <FormField v-slot="{ value }" name="number">
        <FormItem>
          <FormLabel>Número</FormLabel>
          <FormControl>
            <Input :model-value="value" readonly disabled />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="date">
        <FormItem>
          <FormLabel>Data</FormLabel>
          <FormControl>
            <Input
              :model-value="value"
              type="date"
              :disabled="loading || submitting"
              @update:model-value="handleChange"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="client_id">
        <FormItem>
          <FormLabel>Cliente</FormLabel>
          <Select :model-value="String(value ?? '')" :disabled="loading || submitting" @update:model-value="handleChange">
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Selecione o cliente" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem v-for="client in clientOptions" :key="client.id" :value="String(client.id)">
                {{ client.name }}
              </SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="status">
        <FormItem>
          <FormLabel>Estado</FormLabel>
          <Select :model-value="String(value ?? 'draft')" :disabled="loading || submitting" @update:model-value="handleChange">
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Selecione o estado" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              <SelectItem value="draft">Rascunho</SelectItem>
              <SelectItem value="in_progress">Em Curso</SelectItem>
              <SelectItem value="completed">Concluída</SelectItem>
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="description">
        <FormItem>
          <FormLabel>Descrição</FormLabel>
          <FormControl>
            <Textarea
              :model-value="value ?? ''"
              :disabled="loading || submitting"
              placeholder="Descrição dos trabalhos a executar..."
              @update:model-value="handleChange"
            />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <section class="items-section">
        <div class="items-header">
          <h2>Linhas da OT</h2>
          <Button type="button" variant="outline" size="sm" @click="addItem">Adicionar linha</Button>
        </div>

        <div
          v-for="(item, index) in items"
          :key="index"
          :class="['item-row', { 'item-row--error': itemErrors[index] }]"
        >
          <div class="item-grid">
            <FormItem>
              <FormLabel>Artigo (pesquisa)</FormLabel>
              <FormControl>
                <Input
                  :model-value="item.article_name"
                  placeholder="Pesquisar por referência ou nome..."
                  :disabled="loading || submitting"
                  @update:model-value="queueArticleSearch(index, String($event ?? ''))"
                />
              </FormControl>
              <div v-if="articleOptionsByItem[index]?.length" class="autocomplete-list">
                <button
                  v-for="article in articleOptionsByItem[index]"
                  :key="article.id"
                  type="button"
                  class="autocomplete-item"
                  @click="selectArticle(index, article)"
                >
                  {{ article.reference }} - {{ article.name }}
                </button>
              </div>
              <p v-if="itemErrors[index]?.article" class="row-error">{{ itemErrors[index]?.article }}</p>
            </FormItem>

            <FormItem>
              <FormLabel>Quantidade</FormLabel>
              <FormControl>
                <Input
                  type="number"
                  min="1"
                  step="1"
                  :model-value="String(item.quantity)"
                  :disabled="loading || submitting"
                  @update:model-value="
                    item.quantity = Number($event ?? 0);
                    recalculateItemTotal(index)
                  "
                />
              </FormControl>
              <p v-if="itemErrors[index]?.quantity" class="row-error">{{ itemErrors[index]?.quantity }}</p>
            </FormItem>

            <FormItem>
              <FormLabel>Preço</FormLabel>
              <FormControl>
                <Input
                  type="number"
                  min="0"
                  step="0.01"
                  :model-value="String(item.price)"
                  :disabled="loading || submitting"
                  @update:model-value="
                    item.price = Number($event ?? 0);
                    recalculateItemTotal(index)
                  "
                />
              </FormControl>
              <p v-if="itemErrors[index]?.price" class="row-error">{{ itemErrors[index]?.price }}</p>
            </FormItem>

            <FormItem>
              <FormLabel>Total linha</FormLabel>
              <FormControl>
                <Input :model-value="item.total.toFixed(2)" readonly disabled />
              </FormControl>
            </FormItem>
          </div>

          <div class="item-actions">
            <Button
              type="button"
              variant="destructive"
              size="sm"
              :disabled="loading || submitting || items.length === 1"
              @click="removeItem(index)"
            >
              Remover
            </Button>
          </div>
        </div>

        <div class="global-total">Total global: € {{ totalAmount.toFixed(2) }}</div>
      </section>

      <div class="footer">
        <Button type="button" variant="outline" @click="router.push('/work-orders')">Cancelar</Button>
        <Button type="submit" :disabled="loading || submitting">
          {{ submitting ? 'A guardar...' : 'Guardar' }}
        </Button>
      </div>
    </Form>
  </div>
</template>

<style scoped>
.page {
  width: 100%;
  max-width: 110rem;
}

h1 {
  margin-top: 0;
  text-align: left;
}

.form {
  display: grid;
  gap: 0.9rem;
}

.feedback {
  margin-bottom: 1rem;
}

.toast-like {
  position: fixed;
  top: 1rem;
  right: 1rem;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  border: 1px solid hsl(var(--border));
  background: hsl(var(--background));
}

.success {
  color: #166534;
}

.error {
  color: #b91c1c;
}

.footer {
  display: flex;
  gap: 0.8rem;
  align-items: center;
}

.items-section {
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  padding: 0.75rem;
  display: grid;
  gap: 0.75rem;
}

.items-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.items-header h2 {
  margin: 0;
  font-size: 1rem;
}

.item-row {
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  padding: 0.75rem;
  display: grid;
  gap: 0.6rem;
}

.item-row--error {
  border-color: #dc2626;
  background: rgba(220, 38, 38, 0.04);
}

.item-grid {
  display: grid;
  gap: 0.6rem;
  grid-template-columns:
    minmax(16rem, 2.2fr)
    minmax(7rem, 0.85fr)
    minmax(8rem, 0.85fr)
    minmax(8rem, 0.8fr);
}

.item-actions {
  display: flex;
  justify-content: flex-end;
}

.autocomplete-list {
  margin-top: 0.25rem;
  border: 1px solid hsl(var(--border));
  border-radius: 0.375rem;
  background: hsl(var(--background));
  max-height: 10rem;
  overflow-y: auto;
}

.autocomplete-item {
  display: block;
  width: 100%;
  padding: 0.45rem 0.6rem;
  text-align: left;
  font-size: 0.85rem;
}

.autocomplete-item:hover {
  background: hsl(var(--muted));
}

.global-total {
  font-weight: 600;
  text-align: right;
}

.row-error {
  margin-top: 0.3rem;
  font-size: 0.75rem;
  color: #b91c1c;
}

@media (max-width: 1200px) {
  .item-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .item-grid {
    grid-template-columns: 1fr;
  }
}
</style>
