<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useForm } from 'vee-validate'
import { toTypedSchema } from '@vee-validate/zod'
import { z } from 'zod'
import { Button } from '@/components/ui/button'
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import {
  createSupplierOrder,
  getSupplierOrderById,
  updateSupplierOrder,
} from '@/modules/supplier-orders/services/supplierOrderService'
import {
  getSupplierOptions,
  searchArticles,
} from '@/modules/proposals/services/proposalService'
import type {
  ArticleSearchOption,
  SupplierOption,
} from '@/modules/proposals/services/proposalService'
import type { UpsertSupplierOrderPayload } from '@/modules/supplier-orders/types/supplierOrder'

type SupplierItem = {
  article_id: number | null
  article_name: string
  quantity: number
  cost_price: number
  total: number
}

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.params.id === undefined)
const orderId = computed(() => Number(route.params.id))
const loading = ref(false)
const submitting = ref(false)
const feedback = ref('')
const supplierOptions = ref<SupplierOption[]>([])
const items = ref<SupplierItem[]>([
  { article_id: null, article_name: '', quantity: 1, cost_price: 0, total: 0 },
])
const articleOptions = ref<Record<number, ArticleSearchOption[]>>({})
const articleSearchDebounceId = ref<number | null>(null)

const schema = z.object({
  number: z.string().optional(),
  order_date: z.string().min(1, 'Data é obrigatória'),
  supplier_id: z.string().min(1, 'Fornecedor é obrigatório'),
  status: z.enum(['draft', 'confirmed']),
})

type FormData = z.infer<typeof schema>

const totalAmount = computed(() =>
  items.value.reduce((sum, item) => sum + Number(item.total || 0), 0),
)

const { setValues, setErrors } = useForm<FormData>({
  validationSchema: toTypedSchema(schema),
  initialValues: {
    number: '',
    order_date: new Date().toISOString().slice(0, 10),
    supplier_id: '',
    status: 'draft',
  },
})

async function hydrate(): Promise<void> {
  if (isNew.value) {
    return
  }

  const order = await getSupplierOrderById(orderId.value)
  setValues({
    number: order.number ?? '',
    order_date: order.order_date ?? '',
    supplier_id: order.supplier?.id ? String(order.supplier.id) : '',
    status: order.status ?? 'draft',
  })
  items.value = (order.items ?? []).length
    ? (order.items ?? []).map((item) => ({
        article_id: item.article_id,
        article_name: item.article?.name ?? '',
        quantity: Number(item.quantity),
        cost_price: Number(item.cost_price),
        total: Number(item.total),
      }))
    : [{ article_id: null, article_name: '', quantity: 1, cost_price: 0, total: 0 }]
}

function recalculateItemTotal(index: number): void {
  const current = items.value[index]
  if (!current) return
  current.total = Number(current.quantity || 0) * Number(current.cost_price || 0)
}

function addItem(): void {
  items.value.push({ article_id: null, article_name: '', quantity: 1, cost_price: 0, total: 0 })
}

function removeItem(index: number): void {
  if (items.value.length <= 1) return
  items.value.splice(index, 1)
}

async function queueArticleSearch(index: number, query: string): Promise<void> {
  if (articleSearchDebounceId.value != null) {
    window.clearTimeout(articleSearchDebounceId.value)
  }

  if (!query.trim()) {
    articleOptions.value[index] = []
    return
  }

  articleSearchDebounceId.value = window.setTimeout(async () => {
    try {
      articleOptions.value[index] = await searchArticles(query.trim())
    } catch {
      articleOptions.value[index] = []
    }
  }, 300)
}

function selectArticle(index: number, option: ArticleSearchOption): void {
  const item = items.value[index]
  if (!item) return
  item.article_id = option.id
  item.article_name = `${option.reference ?? ''} - ${option.name}`.trim()
  item.cost_price = Number(option.price ?? item.cost_price ?? 0)
  recalculateItemTotal(index)
  articleOptions.value[index] = []
}

const onSubmit = async (values: FormData) => {
  if (items.value.some((item) => !item.article_id)) {
    feedback.value = 'Todos os itens precisam de artigo.'
    return
  }

  submitting.value = true
  feedback.value = ''
  try {
    setErrors({})
    const payload: UpsertSupplierOrderPayload = {
      order_date: values.order_date,
      supplier_id: Number(values.supplier_id),
      status: values.status,
      items: items.value.map((item) => ({
        article_id: Number(item.article_id),
        quantity: Number(item.quantity),
        cost_price: Number(item.cost_price),
        total: Number(item.total),
      })),
    }

    if (isNew.value) {
      await createSupplierOrder(payload)
      feedback.value = 'Encomenda criada com sucesso.'
      await router.push('/supplier-orders')
      return
    }

    await updateSupplierOrder(orderId.value, payload)
    feedback.value = 'Encomenda atualizada com sucesso.'
    await hydrate()
  } catch {
    feedback.value = 'Não foi possível guardar a encomenda.'
  } finally {
    submitting.value = false
  }
}

const submitWithValidation = async (submittedValues: FormData) => {
  await onSubmit(submittedValues)
}

onMounted(async () => {
  loading.value = true
  try {
    supplierOptions.value = await getSupplierOptions()
    await hydrate()
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
    <h1>{{ isNew ? 'Nova Encomenda de Fornecedor' : 'Encomenda de Fornecedor' }}</h1>
    <p v-if="feedback" class="feedback">{{ feedback }}</p>

    <Form class="form" @submit="submitWithValidation">
      <FormField v-slot="{ value }" name="number">
        <FormItem>
          <FormLabel>Número</FormLabel>
          <FormControl>
            <Input :model-value="value" readonly disabled />
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="order_date">
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

      <FormField v-slot="{ value, handleChange }" name="supplier_id">
        <FormItem>
          <FormLabel>Fornecedor</FormLabel>
          <FormControl>
            <select
              class="native-select"
              :value="String(value ?? '')"
              :disabled="loading || submitting"
              @change="handleChange(($event.target as HTMLSelectElement).value)"
            >
              <option value="">Selecione</option>
              <option v-for="supplier in supplierOptions" :key="supplier.id" :value="String(supplier.id)">
                {{ supplier.name }}
              </option>
            </select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <FormField v-slot="{ value, handleChange }" name="status">
        <FormItem>
          <FormLabel>Estado</FormLabel>
          <FormControl>
            <select
              class="native-select"
              :value="String(value ?? 'draft')"
              :disabled="loading || submitting"
              @change="handleChange(($event.target as HTMLSelectElement).value)"
            >
              <option value="draft">Rascunho</option>
              <option value="confirmed">Confirmada</option>
            </select>
          </FormControl>
          <FormMessage />
        </FormItem>
      </FormField>

      <section class="items-section">
        <div class="items-header">
          <h3>Linhas</h3>
          <Button type="button" variant="outline" size="sm" @click="addItem">Adicionar linha</Button>
        </div>

        <div v-for="(item, index) in items" :key="index" class="item-row">
          <div class="item-field">
            <label>Artigo</label>
            <Input
              :model-value="item.article_name"
              placeholder="Pesquisar artigo"
              :disabled="loading || submitting"
              @update:model-value="(value) => { item.article_name = String(value); item.article_id = null; queueArticleSearch(index, String(value)); }"
            />
            <ul v-if="articleOptions[index]?.length" class="article-results">
              <li
                v-for="article in articleOptions[index]"
                :key="article.id"
                @click="selectArticle(index, article)"
              >
                {{ article.name }} <span v-if="article.reference">({{ article.reference }})</span>
              </li>
            </ul>
          </div>

          <div class="item-field">
            <label>Quantidade</label>
            <Input
              :model-value="String(item.quantity)"
              type="number"
              min="0"
              step="1"
              :disabled="loading || submitting"
              @update:model-value="
                (value) => {
                  const parsed = Number(value)
                  item.quantity = Number.isFinite(parsed) ? Math.max(0, Math.round(parsed)) : 0
                  recalculateItemTotal(index)
                }
              "
            />
          </div>

          <div class="item-field">
            <label>Custo</label>
            <Input
              :model-value="String(item.cost_price)"
              type="number"
              min="0"
              step="0.01"
              :disabled="loading || submitting"
              @update:model-value="(value) => { item.cost_price = Number(value); recalculateItemTotal(index) }"
            />
          </div>

          <div class="item-field">
            <label>Total</label>
            <Input :model-value="item.total.toFixed(2)" readonly disabled />
          </div>

          <Button type="button" variant="ghost" size="sm" :disabled="items.length <= 1" @click="removeItem(index)">
            Remover
          </Button>
        </div>
      </section>

      <div class="total-line">Total: {{ totalAmount.toFixed(2) }} €</div>

      <div class="footer">
        <Button type="button" variant="outline" @click="router.push('/supplier-orders')">Voltar</Button>
        <Button type="submit" :disabled="loading || submitting">
          {{ submitting ? 'A guardar...' : isNew ? 'Criar' : 'Guardar' }}
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
.form { display: grid; gap: 0.9rem; }
.feedback { margin-bottom: 1rem; }
.footer { display: flex; gap: 0.8rem; }
.native-select {
  width: 100%;
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  min-height: 2.5rem;
  padding: 0 0.75rem;
  background: hsl(var(--background));
}
.items-section {
  border: 1px solid hsl(var(--border));
  border-radius: 0.5rem;
  padding: 0.9rem;
  display: grid;
  gap: 0.8rem;
}
.items-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.item-row {
  display: grid;
  grid-template-columns: minmax(16rem, 2.2fr) minmax(7rem, 0.7fr) minmax(8rem, 0.8fr) minmax(
      8rem,
      0.8fr
    ) auto;
  gap: 0.6rem;
  align-items: end;
}
.item-field {
  display: grid;
  gap: 0.3rem;
  position: relative;
}
.article-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 10;
  border: 1px solid hsl(var(--border));
  background: hsl(var(--background));
  border-radius: 0.5rem;
  list-style: none;
  margin: 0.25rem 0 0;
  padding: 0.25rem 0;
  max-height: 10rem;
  overflow: auto;
}
.article-results li {
  padding: 0.4rem 0.6rem;
  cursor: pointer;
}
.article-results li:hover {
  background: hsl(var(--muted));
}
.total-line {
  font-weight: 600;
  text-align: right;
}

@media (max-width: 1200px) {
  .item-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .item-row {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
