import api from '@/shared/services/api'
import type { Article } from '../types/article'

export type ListArticlesQuery = {
  search?: string
  page?: number
  per_page?: number
}

export type ArticlesListMeta = {
  current_page: number
  per_page: number
  total: number
  last_page: number
}

export type ListArticlesResult = {
  data: Article[]
  meta: ArticlesListMeta
}

function normalizeListArticlesResponse(payload: unknown): ListArticlesResult {
  if (Array.isArray(payload)) {
    return {
      data: payload as Article[],
      meta: {
        current_page: 1,
        per_page: payload.length || 15,
        total: payload.length,
        last_page: 1,
      },
    }
  }

  if (payload && typeof payload === 'object' && 'data' in payload) {
    const paginated = payload as {
      data?: Article[]
      current_page?: number
      per_page?: number
      total?: number
      last_page?: number
    }
    const data = Array.isArray(paginated.data) ? paginated.data : []
    return {
      data,
      meta: {
        current_page: paginated.current_page ?? 1,
        per_page: paginated.per_page ?? (data.length || 15),
        total: paginated.total ?? data.length,
        last_page: paginated.last_page ?? 1,
      },
    }
  }

  return {
    data: [],
    meta: { current_page: 1, per_page: 15, total: 0, last_page: 1 },
  }
}

export async function listArticlesResult(query?: ListArticlesQuery): Promise<ListArticlesResult> {
  const response = await api.get('/articles', {
    params: {
      ...(query?.search ? { search: query.search } : {}),
      ...(query?.page ? { page: query.page } : {}),
      ...(query?.per_page ? { per_page: query.per_page } : {}),
    },
  })
  return normalizeListArticlesResponse(response.data.data)
}

export async function listArticles(query?: ListArticlesQuery): Promise<Article[]> {
  const result = await listArticlesResult(query)
  return result.data
}

export type VatOption = {
  id: number
  name: string
  percentage: number
}

export type UpsertArticlePayload = {
  reference: string
  name: string
  description?: string | null
  price: number
  vat_id: number
  notes?: string | null
  is_active: boolean
  photo?: File | null
}

export async function getArticleById(id: number): Promise<Article> {
  const response = await api.get(`/articles/${id}`)
  return response.data.data as Article
}

function buildArticleFormData(payload: Partial<UpsertArticlePayload>): FormData {
  const formData = new FormData()
  if (payload.reference != null) {
    formData.append('reference', payload.reference)
  }
  if (payload.name != null) {
    formData.append('name', payload.name)
  }
  if (payload.price != null) {
    formData.append('price', String(payload.price))
  }
  if (payload.vat_id != null) {
    formData.append('vat_id', String(payload.vat_id))
  }
  if (payload.is_active != null) {
    formData.append('is_active', payload.is_active ? '1' : '0')
  }

  if (payload.description) {
    formData.append('description', payload.description)
  }
  if (payload.notes) {
    formData.append('notes', payload.notes)
  }
  if (payload.photo) {
    formData.append('photo', payload.photo)
  }

  return formData
}

export async function createArticle(payload: UpsertArticlePayload): Promise<Article> {
  const response = await api.post('/articles', buildArticleFormData(payload), {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return response.data.data as Article
}

export async function updateArticle(id: number, payload: Partial<UpsertArticlePayload>): Promise<Article> {
  const formData = buildArticleFormData(payload)
  formData.append('_method', 'PUT')
  const response = await api.post(`/articles/${id}`, formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  })
  return response.data.data as Article
}

export async function inactivateArticle(id: number): Promise<Article> {
  const response = await api.delete(`/articles/${id}`)
  return response.data.data as Article
}

export async function toggleArticleStatus(id: number, isCurrentlyActive: boolean): Promise<Article> {
  if (isCurrentlyActive) {
    return inactivateArticle(id)
  }
  const response = await api.put(`/articles/${id}`, { is_active: true })
  return response.data.data as Article
}

export async function listVatOptions(): Promise<VatOption[]> {
  const endpoints = ['/vat', '/vats', '/settings/vat']

  for (const endpoint of endpoints) {
    try {
      const response = await api.get(endpoint, { params: { active_only: 1, per_page: 100 } })
      const payload = response.data.data
      if (Array.isArray(payload)) {
        return payload as VatOption[]
      }
      if (payload && Array.isArray(payload.data)) {
        return payload.data as VatOption[]
      }
    } catch {
      // Try next endpoint.
    }
  }

  throw new Error('Endpoint de IVA não disponível.')
}
