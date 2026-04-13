<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableEmpty,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  listArticlesResult,
  toggleArticleStatus,
  type ArticlesListMeta,
} from '@/modules/settings/articles/services/articleService'
import type { Article } from '@/modules/settings/articles/types/article'

const router = useRouter()

const articles = ref<Article[]>([])
const loading = ref(false)
const togglingId = ref<number | null>(null)
const errorMessage = ref('')
const searchText = ref('')
const searchDebounced = ref('')
const debounceId = ref<number | null>(null)

const filters = reactive({
  perPage: 15,
})

const pagination = reactive<ArticlesListMeta>({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
})

const hasPreviousPage = computed(() => pagination.current_page > 1)
const hasNextPage = computed(() => pagination.current_page < pagination.last_page)

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'EUR',
  }).format(value)
}

function getVatPercentage(article: Article): string {
  if (article.vat?.percentage != null) {
    return `${article.vat.percentage}%`
  }
  if (article.vat_percent != null) {
    return `${article.vat_percent}%`
  }
  return '-'
}

function queueSearch(value: string): void {
  searchText.value = value
  if (debounceId.value != null) {
    window.clearTimeout(debounceId.value)
  }
  debounceId.value = window.setTimeout(() => {
    searchDebounced.value = value.trim()
    pagination.current_page = 1
  }, 300)
}

async function fetchArticles(): Promise<void> {
  loading.value = true
  errorMessage.value = ''
  try {
    const result = await listArticlesResult({
      search: searchDebounced.value || undefined,
      page: pagination.current_page,
      per_page: filters.perPage,
    })
    articles.value = result.data
    pagination.current_page = result.meta.current_page
    pagination.per_page = result.meta.per_page
    pagination.total = result.meta.total
    pagination.last_page = result.meta.last_page
  } catch {
    articles.value = []
    errorMessage.value = 'Não foi possível carregar os artigos.'
  } finally {
    loading.value = false
  }
}

function goToEdit(articleId: number): void {
  void router.push({ name: 'articles.edit', params: { id: articleId } })
}

async function onToggleStatus(article: Article): Promise<void> {
  togglingId.value = article.id
  try {
    await toggleArticleStatus(article.id, article.is_active)
    await fetchArticles()
  } finally {
    togglingId.value = null
  }
}

function goToPage(page: number): void {
  if (page < 1 || page > pagination.last_page) {
    return
  }
  pagination.current_page = page
}

watch(
  () => [pagination.current_page, filters.perPage, searchDebounced.value],
  () => {
    void fetchArticles()
  },
)

onMounted(() => {
  void fetchArticles()
})

onBeforeUnmount(() => {
  if (debounceId.value != null) {
    window.clearTimeout(debounceId.value)
  }
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-2">
        <Input
          :model-value="searchText"
          placeholder="Pesquisar por referência ou nome..."
          class="w-[280px]"
          @update:model-value="queueSearch(String($event))"
        />
        <Select
          :model-value="String(filters.perPage)"
          @update:model-value="
            filters.perPage = Number($event);
            pagination.current_page = 1
          "
        >
          <SelectTrigger class="w-[120px]">
            <SelectValue placeholder="Por página" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="10">10</SelectItem>
            <SelectItem value="15">15</SelectItem>
            <SelectItem value="25">25</SelectItem>
            <SelectItem value="50">50</SelectItem>
          </SelectContent>
        </Select>
      </div>

      <Button variant="outline" @click="router.push('/settings/articles/new')">Criar artigo</Button>
    </div>

    <div v-if="errorMessage" class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive">
      {{ errorMessage }}
    </div>

    <div class="rounded-md border">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Referência</TableHead>
            <TableHead>Foto</TableHead>
            <TableHead>Nome</TableHead>
            <TableHead>Descrição</TableHead>
            <TableHead>Preço</TableHead>
            <TableHead>IVA</TableHead>
            <TableHead>Estado</TableHead>
            <TableHead class="text-right">Ações</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableEmpty v-if="loading" :colspan="8">A carregar artigos...</TableEmpty>
          <TableEmpty v-else-if="!articles.length" :colspan="8">Nenhum artigo encontrado.</TableEmpty>
          <TableRow v-for="article in articles" v-else :key="article.id">
            <TableCell>{{ article.reference }}</TableCell>
            <TableCell>
              <img :src="article.photo_url || '/placeholder.png'" alt="Foto do artigo" class="thumb" />
            </TableCell>
            <TableCell>{{ article.name }}</TableCell>
            <TableCell>{{ article.description || '-' }}</TableCell>
            <TableCell>{{ formatCurrency(article.price) }}</TableCell>
            <TableCell>{{ getVatPercentage(article) }}</TableCell>
            <TableCell>{{ article.is_active ? 'Ativo' : 'Inativo' }}</TableCell>
            <TableCell class="text-right">
              <div class="flex justify-end gap-2">
                <Button size="sm" variant="outline" :disabled="loading" @click="goToEdit(article.id)">Editar</Button>
                <Button
                  size="sm"
                  variant="secondary"
                  :disabled="loading || togglingId === article.id"
                  @click="onToggleStatus(article)"
                >
                  {{ article.is_active ? 'Inativar' : 'Ativar' }}
                </Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <div class="flex items-center justify-between text-sm text-muted-foreground">
      <p>
        Página {{ pagination.current_page }} de {{ pagination.last_page }} · {{ pagination.total }} registos
      </p>
      <div class="flex gap-2">
        <Button variant="outline" size="sm" :disabled="!hasPreviousPage || loading" @click="goToPage(pagination.current_page - 1)">
          Anterior
        </Button>
        <Button variant="outline" size="sm" :disabled="!hasNextPage || loading" @click="goToPage(pagination.current_page + 1)">
          Próxima
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.thumb {
  width: 2.5rem;
  height: 2.5rem;
  object-fit: cover;
  border-radius: 0.375rem;
  border: 1px solid hsl(var(--border));
}
</style>
